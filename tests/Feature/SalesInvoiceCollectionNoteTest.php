<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SalesInvoiceCollectionNoteTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 940;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_sales_invoice_show_displays_collection_notes_form_and_empty_state(): void
    {
        $this->assertTrue(Schema::hasTable('sales_invoice_collection_notes'));

        $user = User::query()->firstOrFail();
        $invoice = $this->createInvoiceForCollectionNote();

        $response = $this->actingAs($user)->get(route('sales-invoices.show', $invoice));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-collection-notes-card"', false);
        $response->assertSee('ملاحظات متابعة التحصيل');
        $response->assertSee('data-testid="sales-invoice-collection-note-form"', false);
        $response->assertSee('data-testid="sales-invoice-collection-note-input"', false);
        $response->assertSee('data-testid="sales-invoice-collection-follow-up-input"', false);
        $response->assertSee('لا توجد ملاحظات تحصيل بعد.');
    }

    public function test_user_can_store_sales_invoice_collection_note(): void
    {
        $user = User::query()->firstOrFail();
        $invoice = $this->createInvoiceForCollectionNote();

        $response = $this->actingAs($user)->post(route('sales-invoices.collection-notes.store', $invoice), [
            'note' => 'تم التواصل مع العميل وسيتم السداد الأسبوع القادم.',
            'follow_up_at' => '2026-07-15',
        ]);

        $response->assertRedirect(route('sales-invoices.show', $invoice));

        $this->assertDatabaseHas('sales_invoice_collection_notes', [
            'sales_invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'note' => 'تم التواصل مع العميل وسيتم السداد الأسبوع القادم.',
        ]);

        $showResponse = $this->actingAs($user)->get(route('sales-invoices.show', $invoice));

        $showResponse->assertOk();
        $showResponse->assertSee('تم التواصل مع العميل وسيتم السداد الأسبوع القادم.');
        $showResponse->assertSee('2026-07-15');
        $showResponse->assertSee('data-testid="sales-invoice-collection-note-row"', false);
    }

    public function test_collection_note_requires_note_text(): void
    {
        $user = User::query()->firstOrFail();
        $invoice = $this->createInvoiceForCollectionNote();

        $response = $this->actingAs($user)->from(route('sales-invoices.show', $invoice))->post(route('sales-invoices.collection-notes.store', $invoice), [
            'note' => '',
            'follow_up_at' => '2026-07-15',
        ]);

        $response->assertRedirect(route('sales-invoices.show', $invoice));
        $response->assertSessionHasErrors('note');

        $this->assertDatabaseCount('sales_invoice_collection_notes', 0);
    }

    private function createInvoiceForCollectionNote(): SalesInvoice
    {
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل ملاحظات تحصيل الفاتورة',
            'phone' => '0579801141',
            'email' => 'sales-invoice-collection-note@example.com',
        ]);

        return $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-COLLECTION-NOTE',
            'payment_status' => 'partial',
            'grand_total' => 2000,
            'paid_amount' => 500,
            'remaining_amount' => 1500,
            'due_at' => '2026-07-01 09:00:00',
        ]);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل ملاحظات تحصيل ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'sales-invoice-collection-note-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-COLLECTION-NOTE-' . uniqid(),
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
