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

class SalesInvoiceCustomerFilterUiTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 550;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_sales_invoices_index_displays_customer_filter(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل فلتر صفحة الفواتير',
            'phone' => '0579800751',
            'email' => 'sales-invoice-customer-filter@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.index'));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-filters-card"', false);
        $response->assertSee('data-testid="sales-invoice-customer-filter"', false);
        $response->assertSee('كل العملاء');
        $response->assertSee('عميل فلتر صفحة الفواتير');
    }

    public function test_sales_invoices_index_filters_by_customer_from_ui_parameter(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'customer_id'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $selectedCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل محدد في فلتر الفواتير',
            'phone' => '0579800752',
            'email' => 'sales-invoice-selected-customer@example.com',
        ]);

        $hiddenCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل مخفي من فلتر الفواتير',
            'phone' => '0579800753',
            'email' => 'sales-invoice-hidden-customer@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-CUSTOMER-FILTER-IN',
            'grand_total' => 1250,
        ]);

        $this->createSalesInvoice($companyId, $branchId, $hiddenCustomer->id, [
            'invoice_number' => 'SI-CUSTOMER-FILTER-OUT',
            'grand_total' => 2250,
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.index', [
            'customer_id' => $selectedCustomer->id,
        ]));

        $response->assertOk();
        $response->assertSee('SI-CUSTOMER-FILTER-IN');
        $response->assertDontSee('SI-CUSTOMER-FILTER-OUT');
        $response->assertSee('value="' . $selectedCustomer->id . '" selected', false);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل فلتر فواتير المبيعات ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'sales-invoice-customer-filter-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-CUST-FILTER-' . uniqid(),
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
