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

class CustomerOutstandingSalesInvoiceSummaryPageTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 460;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_customer_show_displays_outstanding_sales_invoice_summary(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'remaining_amount'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل ملخص المبالغ المتبقية',
            'phone' => '0579800661',
            'email' => 'customer-outstanding-summary@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUST-OUTSTANDING-001',
            'grand_total' => 1000,
            'paid_amount' => 250,
            'remaining_amount' => 750,
            'payment_status' => 'partial',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUST-OUTSTANDING-002',
            'grand_total' => 500,
            'paid_amount' => 0,
            'remaining_amount' => 500,
            'payment_status' => 'unpaid',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUST-OUTSTANDING-PAID',
            'grand_total' => 300,
            'paid_amount' => 300,
            'remaining_amount' => 0,
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('data-testid="customer-outstanding-sales-invoice-summary-card"', false);
        $response->assertSee('ملخص فواتير العميل ذات المبالغ المتبقية');
        $response->assertSee('data-testid="customer-outstanding-sales-invoice-summary-count"', false);
        $response->assertSee('>2<', false);
        $response->assertSee('1,250.00 ريال');
        $response->assertSee('يحتاج متابعة');
        $response->assertSee('data-testid="customer-outstanding-sales-invoice-summary-link"', false);
        $response->assertSee('customer_id=' . $customer->id, false);
        $response->assertSee('collection_status=outstanding', false);
    }

    public function test_sales_invoices_index_can_filter_customer_outstanding_invoices(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل فلترة المتبقي',
            'phone' => '0579800662',
            'email' => 'customer-outstanding-filter@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUST-OUTSTANDING-IN',
            'grand_total' => 1400,
            'paid_amount' => 400,
            'remaining_amount' => 1000,
            'payment_status' => 'partial',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUST-OUTSTANDING-EXCLUDED',
            'grand_total' => 900,
            'paid_amount' => 900,
            'remaining_amount' => 0,
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.index', [
            'customer_id' => $customer->id,
            'collection_status' => 'outstanding',
        ]));

        $response->assertOk();
        $response->assertSee('SI-CUST-OUTSTANDING-IN');
        $response->assertDontSee('SI-CUST-OUTSTANDING-EXCLUDED');
    }

    public function test_customer_show_displays_zero_outstanding_summary_when_all_invoices_are_paid(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل بدون مبالغ متبقية',
            'phone' => '0579800663',
            'email' => 'customer-no-outstanding@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUST-NO-OUTSTANDING',
            'grand_total' => 700,
            'paid_amount' => 700,
            'remaining_amount' => 0,
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('data-testid="customer-outstanding-sales-invoice-summary-card"', false);
        $response->assertSee('>0<', false);
        $response->assertSee('0.00 ريال');
        $response->assertSee('لا توجد مبالغ متبقية');
        $response->assertSee('collection_status=outstanding', false);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل ملخص المتبقي ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'customer-outstanding-summary-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-CUST-OUT-' . uniqid(),
            'status' => 'issued',
            'payment_status' => 'unpaid',
            'currency' => 'SAR',
            'subtotal' => 500,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 500,
            'paid_amount' => 0,
            'remaining_amount' => 500,
            'issued_at' => '2026-07-05 09:00:00',
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
