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

class CustomerSalesInvoiceSummaryPageTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 400;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_customer_show_displays_sales_invoice_summary_and_filtered_link(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'customer_id'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل ملخص فواتير المبيعات',
            'phone' => '0579800601',
            'email' => 'customer-sales-summary@example.com',
        ]);

        $otherCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل خارج ملخص فواتير المبيعات',
            'phone' => '0579800602',
            'email' => 'customer-sales-summary-other@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUST-SUM-001',
            'grand_total' => 1000,
            'paid_amount' => 400,
            'remaining_amount' => 600,
            'payment_status' => 'partial',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUST-SUM-002',
            'grand_total' => 500,
            'paid_amount' => 500,
            'remaining_amount' => 0,
            'payment_status' => 'paid',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $otherCustomer->id, [
            'invoice_number' => 'SI-CUST-SUM-OUT',
            'grand_total' => 9999,
            'paid_amount' => 0,
            'remaining_amount' => 9999,
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('data-testid="customer-sales-invoice-summary-card"', false);
        $response->assertSee('ملخص فواتير مبيعات العميل');
        $response->assertSee('data-testid="customer-sales-invoice-summary-count"', false);
        $response->assertSee('>2<', false);
        $response->assertSee('1,500.00 ريال');
        $response->assertSee('900.00 ريال');
        $response->assertSee('600.00 ريال');
        $response->assertSee('data-testid="customer-sales-invoice-summary-link"', false);
        $response->assertSee('customer_id=' . $customer->id, false);
    }

    public function test_sales_invoices_index_can_filter_by_customer_from_customer_page_link(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل فلترة فواتير المبيعات',
            'phone' => '0579800603',
            'email' => 'customer-sales-filter@example.com',
        ]);

        $otherCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل لا تظهر فواتيره في الفلتر',
            'phone' => '0579800604',
            'email' => 'customer-sales-filter-other@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUST-FILTER-IN',
            'grand_total' => 1250,
            'paid_amount' => 250,
            'remaining_amount' => 1000,
        ]);

        $this->createSalesInvoice($companyId, $branchId, $otherCustomer->id, [
            'invoice_number' => 'SI-CUST-FILTER-OUT',
            'grand_total' => 2250,
            'paid_amount' => 0,
            'remaining_amount' => 2250,
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.index', [
            'customer_id' => $customer->id,
        ]));

        $response->assertOk();
        $response->assertSee('SI-CUST-FILTER-IN');
        $response->assertDontSee('SI-CUST-FILTER-OUT');
    }

    public function test_customer_show_displays_zero_summary_when_customer_has_no_sales_invoices(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل بدون فواتير مبيعات',
            'phone' => '0579800605',
            'email' => 'customer-no-sales-invoices@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('data-testid="customer-sales-invoice-summary-card"', false);
        $response->assertSee('عميل بدون فواتير مبيعات');
        $response->assertSee('>0<', false);
        $response->assertSee('0.00 ريال');
        $response->assertSee('customer_id=' . $customer->id, false);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل ملخص فواتير المبيعات ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'customer-sales-summary-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-CUST-SUM-' . uniqid(),
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
