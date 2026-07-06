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

class SalesInvoiceAgingReportFilterTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 1180;

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

    public function test_sales_invoice_aging_report_displays_filter_controls(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-aging.index'));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-aging-report-filters-card"', false);
        $response->assertSee('data-testid="sales-invoice-aging-customer-filter"', false);
        $response->assertSee('data-testid="sales-invoice-aging-payment-status-filter"', false);
        $response->assertSee('data-testid="sales-invoice-aging-apply-filters-button"', false);
        $response->assertSee('data-testid="sales-invoice-aging-reset-filters-link"', false);
        $response->assertSee('كل العملاء');
        $response->assertSee('كل الحالات');
    }

    public function test_sales_invoice_aging_report_filters_by_customer_and_payment_status(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'customer_id'));
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'payment_status'));

        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $selectedCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل فلتر أعمار الذمم',
            'phone' => '0579831181',
            'email' => 'sales-invoice-aging-filter-selected@example.com',
        ]);

        $otherCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل مستبعد من أعمار الذمم',
            'phone' => '0579831182',
            'email' => 'sales-invoice-aging-filter-other@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-AGING-FILTER-IN',
            'payment_status' => 'partial',
            'grand_total' => 3000,
            'paid_amount' => 1000,
            'remaining_amount' => 2000,
            'due_at' => '2026-06-20 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-AGING-FILTER-UNPAID-OUT',
            'payment_status' => 'unpaid',
            'grand_total' => 1600,
            'paid_amount' => 0,
            'remaining_amount' => 1600,
            'due_at' => '2026-06-20 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $otherCustomer->id, [
            'invoice_number' => 'SI-AGING-FILTER-CUSTOMER-OUT',
            'payment_status' => 'partial',
            'grand_total' => 1800,
            'paid_amount' => 400,
            'remaining_amount' => 1400,
            'due_at' => '2026-06-20 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-AGING-FILTER-PAID-OUT',
            'payment_status' => 'paid',
            'grand_total' => 900,
            'paid_amount' => 900,
            'remaining_amount' => 0,
            'due_at' => '2026-06-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-aging.index', [
            'customer_id' => $selectedCustomer->id,
            'payment_status' => 'partial',
        ]));

        $response->assertOk();
        $response->assertSee('SI-AGING-FILTER-IN');
        $response->assertDontSee('SI-AGING-FILTER-UNPAID-OUT');
        $response->assertDontSee('SI-AGING-FILTER-CUSTOMER-OUT');
        $response->assertDontSee('SI-AGING-FILTER-PAID-OUT');

        $response->assertSee('2,000.00 ريال');
        $response->assertDontSee('1,600.00 ريال');
        $response->assertDontSee('1,400.00 ريال');

        $response->assertSee('value="' . $selectedCustomer->id . '" selected', false);
        $response->assertSee('value="partial" selected', false);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل فلتر أعمار الذمم ' . $this->customerSequence,
            'phone' => '057983' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'sales-invoice-aging-filter-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-AGING-FILTER-' . uniqid(),
            'status' => 'issued',
            'payment_status' => 'partial',
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
