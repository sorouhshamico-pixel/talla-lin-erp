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

class SalesInvoiceCollectionNoteRescheduleTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 1120;

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

    public function test_user_can_reschedule_open_collection_note(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoice_collection_notes', 'follow_up_at'));

        $user = User::query()->firstOrFail();
        $invoice = $this->createInvoiceForReschedule();

        $note = $invoice->collectionNotes()->create([
            'user_id' => $user->id,
            'note' => 'متابعة تحصيل قابلة لإعادة الجدولة.',
            'follow_up_at' => '2026-07-06',
        ]);

        $response = $this->actingAs($user)->post(route('sales-invoices.collection-notes.reschedule', [$invoice, $note]), [
            'follow_up_at' => '2026-07-20',
        ]);

        $response->assertRedirect(route('sales-invoices.show', $invoice));

        $this->assertSame('2026-07-20', $note->fresh()->follow_up_at->format('Y-m-d'));

        $showResponse = $this->actingAs($user)->get(route('sales-invoices.show', $invoice));

        $showResponse->assertOk();
        $showResponse->assertSee('data-testid="sales-invoice-collection-note-reschedule-form"', false);
        $showResponse->assertSee('data-testid="sales-invoice-collection-note-reschedule-input"', false);
        $showResponse->assertSee('value="2026-07-20"', false);
        $showResponse->assertSee('تمت إعادة جدولة متابعة التحصيل بنجاح.');
    }

    public function test_rescheduled_future_note_is_excluded_from_due_follow_up_report(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $invoice = $this->createInvoiceForReschedule([
            'invoice_number' => 'SI-RESCHEDULED-FOLLOW-UP-OUT',
            'remaining_amount' => 1500,
            'grand_total' => 2000,
            'paid_amount' => 500,
        ]);

        $openInvoice = $this->createInvoiceForReschedule([
            'invoice_number' => 'SI-DUE-FOLLOW-UP-IN',
            'remaining_amount' => 900,
            'grand_total' => 1200,
            'paid_amount' => 300,
        ]);

        $rescheduledNote = $invoice->collectionNotes()->create([
            'user_id' => $user->id,
            'note' => 'متابعة تمت إعادة جدولتها للمستقبل.',
            'follow_up_at' => '2026-07-06',
        ]);

        $openInvoice->collectionNotes()->create([
            'user_id' => $user->id,
            'note' => 'متابعة مستحقة لا تزال ظاهرة.',
            'follow_up_at' => '2026-07-06',
        ]);

        $this->actingAs($user)->post(route('sales-invoices.collection-notes.reschedule', [$invoice, $rescheduledNote]), [
            'follow_up_at' => '2026-07-20',
        ])->assertRedirect(route('sales-invoices.show', $invoice));

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-collection-follow-ups.index'));

        $response->assertOk();
        $response->assertSee('SI-DUE-FOLLOW-UP-IN');
        $response->assertSee('متابعة مستحقة لا تزال ظاهرة.');
        $response->assertDontSee('SI-RESCHEDULED-FOLLOW-UP-OUT');
        $response->assertDontSee('متابعة تمت إعادة جدولتها للمستقبل.');

        $exportResponse = $this->actingAs($user)->get(route('reports.sales-invoice-collection-follow-ups.export'));

        $exportResponse->assertOk();

        $content = $exportResponse->streamedContent();

        $this->assertStringContainsString('SI-DUE-FOLLOW-UP-IN', $content);
        $this->assertStringContainsString('متابعة مستحقة لا تزال ظاهرة.', $content);
        $this->assertStringNotContainsString('SI-RESCHEDULED-FOLLOW-UP-OUT', $content);
        $this->assertStringNotContainsString('متابعة تمت إعادة جدولتها للمستقبل.', $content);
    }

    public function test_completed_collection_note_cannot_be_rescheduled(): void
    {
        $user = User::query()->firstOrFail();

        $invoice = $this->createInvoiceForReschedule();

        $note = $invoice->collectionNotes()->create([
            'user_id' => $user->id,
            'note' => 'متابعة مكتملة لا يمكن إعادة جدولتها.',
            'follow_up_at' => '2026-07-06',
            'completed_at' => now(),
            'completed_by_user_id' => $user->id,
            'completion_note' => 'تم الإغلاق.',
        ]);

        $response = $this->actingAs($user)->post(route('sales-invoices.collection-notes.reschedule', [$invoice, $note]), [
            'follow_up_at' => '2026-07-20',
        ]);

        $response->assertNotFound();

        $this->assertSame('2026-07-06', $note->fresh()->follow_up_at->format('Y-m-d'));
    }

    public function test_collection_note_reschedule_route_rejects_note_from_another_invoice(): void
    {
        $user = User::query()->firstOrFail();

        $invoice = $this->createInvoiceForReschedule([
            'invoice_number' => 'SI-RESCHEDULE-OWNER',
        ]);

        $otherInvoice = $this->createInvoiceForReschedule([
            'invoice_number' => 'SI-RESCHEDULE-OTHER',
        ]);

        $note = $otherInvoice->collectionNotes()->create([
            'user_id' => $user->id,
            'note' => 'ملاحظة لفاتورة أخرى.',
            'follow_up_at' => '2026-07-06',
        ]);

        $response = $this->actingAs($user)->post(route('sales-invoices.collection-notes.reschedule', [$invoice, $note]), [
            'follow_up_at' => '2026-07-20',
        ]);

        $response->assertNotFound();

        $this->assertSame('2026-07-06', $note->fresh()->follow_up_at->format('Y-m-d'));
    }

    private function createInvoiceForReschedule(array $invoiceOverrides = []): SalesInvoice
    {
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل إعادة جدولة متابعة التحصيل',
            'email' => 'collection-note-reschedule-' . uniqid() . '@example.com',
        ]);

        return $this->createSalesInvoice($companyId, $branchId, $customer->id, array_merge([
            'invoice_number' => 'SI-COLLECTION-RESCHEDULE-' . uniqid(),
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
            'name' => 'عميل إعادة جدولة متابعة التحصيل ' . $this->customerSequence,
            'phone' => '057982' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'collection-note-reschedule-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-COLLECTION-RESCHEDULE-' . uniqid(),
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
