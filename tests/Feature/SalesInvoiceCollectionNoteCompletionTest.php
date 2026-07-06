<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SalesInvoiceCollectionNoteCompletionTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 1090;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-06 10:00:00');

        $this->seed(InitialSetupSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_user_can_mark_collection_note_as_completed(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoice_collection_notes', 'completed_at'));
        $this->assertTrue(Schema::hasColumn('sales_invoice_collection_notes', 'completed_by_user_id'));
        $this->assertTrue(Schema::hasColumn('sales_invoice_collection_notes', 'completion_note'));

        $user = User::query()->firstOrFail();

        $invoice = $this->createInvoiceForCompletion();

        $note = $invoice->collectionNotes()->create([
            'user_id' => $user->id,
            'note' => 'متابعة تحصيل يجب إغلاقها.',
            'follow_up_at' => '2026-07-06',
        ]);

        $response = $this->actingAs($user)->post(route('sales-invoices.collection-notes.complete', [$invoice, $note]), [
            'completion_note' => 'تم التواصل وتحديث حالة المتابعة.',
        ]);

        $response->assertRedirect(route('sales-invoices.show', $invoice));

        $this->assertDatabaseHas('sales_invoice_collection_notes', [
            'id' => $note->id,
            'sales_invoice_id' => $invoice->id,
            'completed_by_user_id' => $user->id,
            'completion_note' => 'تم التواصل وتحديث حالة المتابعة.',
        ]);

        $this->assertNotNull($note->fresh()->completed_at);

        $showResponse = $this->actingAs($user)->get(route('sales-invoices.show', $invoice));

        $showResponse->assertOk();
        $showResponse->assertSee('data-testid="sales-invoice-collection-note-completed-status"', false);
        $showResponse->assertSee('مكتملة بتاريخ 2026-07-06');
        $showResponse->assertSee('تم التواصل وتحديث حالة المتابعة.');
    }

    public function test_completed_collection_notes_are_excluded_from_due_follow_up_report(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $invoice = $this->createInvoiceForCompletion([
            'invoice_number' => 'SI-COMPLETED-FOLLOW-UP-OUT',
            'remaining_amount' => 1500,
            'grand_total' => 2000,
            'paid_amount' => 500,
        ]);

        $openInvoice = $this->createInvoiceForCompletion([
            'invoice_number' => 'SI-OPEN-FOLLOW-UP-IN',
            'remaining_amount' => 900,
            'grand_total' => 1200,
            'paid_amount' => 300,
        ]);

        $invoice->collectionNotes()->create([
            'user_id' => $user->id,
            'note' => 'متابعة مكتملة لا تظهر في التقرير.',
            'follow_up_at' => '2026-07-06',
            'completed_at' => now(),
            'completed_by_user_id' => $user->id,
            'completion_note' => 'أغلقت المتابعة.',
        ]);

        $openInvoice->collectionNotes()->create([
            'user_id' => $user->id,
            'note' => 'متابعة مفتوحة تظهر في التقرير.',
            'follow_up_at' => '2026-07-06',
        ]);

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-collection-follow-ups.index'));

        $response->assertOk();
        $response->assertSee('SI-OPEN-FOLLOW-UP-IN');
        $response->assertSee('متابعة مفتوحة تظهر في التقرير.');
        $response->assertDontSee('SI-COMPLETED-FOLLOW-UP-OUT');
        $response->assertDontSee('متابعة مكتملة لا تظهر في التقرير.');

        $exportResponse = $this->actingAs($user)->get(route('reports.sales-invoice-collection-follow-ups.export'));

        $exportResponse->assertOk();

        $content = $exportResponse->streamedContent();

        $this->assertStringContainsString('SI-OPEN-FOLLOW-UP-IN', $content);
        $this->assertStringContainsString('متابعة مفتوحة تظهر في التقرير.', $content);
        $this->assertStringNotContainsString('SI-COMPLETED-FOLLOW-UP-OUT', $content);
        $this->assertStringNotContainsString('متابعة مكتملة لا تظهر في التقرير.', $content);
    }

    public function test_collection_note_complete_route_rejects_note_from_another_invoice(): void
    {
        $user = User::query()->firstOrFail();

        $invoice = $this->createInvoiceForCompletion([
            'invoice_number' => 'SI-COMPLETE-OWNER',
        ]);

        $otherInvoice = $this->createInvoiceForCompletion([
            'invoice_number' => 'SI-COMPLETE-OTHER',
        ]);

        $note = $otherInvoice->collectionNotes()->create([
            'user_id' => $user->id,
            'note' => 'ملاحظة لفاتورة أخرى.',
            'follow_up_at' => '2026-07-06',
        ]);

        $response = $this->actingAs($user)->post(route('sales-invoices.collection-notes.complete', [$invoice, $note]), [
            'completion_note' => 'محاولة خاطئة.',
        ]);

        $response->assertNotFound();

        $this->assertNull($note->fresh()->completed_at);
    }

    private function createInvoiceForCompletion(array $invoiceOverrides = []): SalesInvoice
    {
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $this->customerSequence++;

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل إكمال متابعة التحصيل',
            'phone' => '057981' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'collection-note-completion-' . uniqid() . '@example.com',
        ]);

        return $this->createSalesInvoice($companyId, $branchId, $customer->id, array_merge([
            'invoice_number' => 'SI-COLLECTION-COMPLETE-' . uniqid(),
            'payment_status' => 'partial',
            'grand_total' => 2000,
            'paid_amount' => 500,
            'remaining_amount' => 1500,
            'due_at' => '2026-07-01 09:00:00',
        ], $invoiceOverrides));
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل إكمال متابعة التحصيل ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'collection-note-completion-' . $this->customerSequence . '@example.com',
            'city' => 'الرياض',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $data = array_merge($data, $overrides);
        $data = array_intersect_key($data, array_flip($columns));

        return Customer::unguarded(fn () => Customer::query()->create($data));
    }

    private function createSalesInvoice(int $companyId, int $branchId, int $customerId, array $overrides = []): SalesInvoice
    {
        $columns = Schema::getColumnListing('sales_invoices');

        $data = [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'customer_id' => $customerId,
            'user_id' => (int) DB::table('users')->value('id'),
            'invoice_number' => 'SI-COLLECTION-COMPLETE-' . uniqid(),
            'status' => 'issued',
            'payment_status' => 'unpaid',
            'currency' => 'SAR',
            'subtotal' => 500,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 500,
            'paid_amount' => 0,
            'remaining_amount' => 500,
            'issued_at' => '2026-07-01 09:00:00',
            'due_at' => '2026-07-20 09:00:00',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $data = array_merge($data, $overrides);
        $data = array_intersect_key($data, array_flip($columns));

        return SalesInvoice::unguarded(fn () => SalesInvoice::query()->create($data));
    }
}
