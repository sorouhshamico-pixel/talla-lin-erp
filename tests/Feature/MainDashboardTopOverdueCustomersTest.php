<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Services\FinancialDashboardSummaryService;
use Carbon\Carbon;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MainDashboardTopOverdueCustomersTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_financial_dashboard_summary_service_returns_top_overdue_customers(): void
    {
        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $firstCustomer = $this->createCustomer([
            'name' => 'عميل متأخر أكبر',
            'phone' => '0579853231',
        ]);

        $secondCustomer = $this->createCustomer([
            'name' => 'عميل متأخر أصغر',
            'phone' => '0579853232',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $firstCustomer->id,
            'invoice_number' => 'SI-TOP-CUSTOMER-001',
            'remaining_amount' => 5000,
            'grand_total' => 5000,
            'subtotal' => 5000,
            'paid_amount' => 0,
            'due_at' => '2026-05-01 09:00:00',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $firstCustomer->id,
            'invoice_number' => 'SI-TOP-CUSTOMER-002',
            'remaining_amount' => 1500,
            'grand_total' => 1500,
            'subtotal' => 1500,
            'paid_amount' => 0,
            'due_at' => '2026-06-01 09:00:00',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $secondCustomer->id,
            'invoice_number' => 'SI-TOP-CUSTOMER-003',
            'remaining_amount' => 2000,
            'grand_total' => 2000,
            'subtotal' => 2000,
            'paid_amount' => 0,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $firstCustomer->id,
            'invoice_number' => 'SI-TOP-CUSTOMER-NOT-DUE',
            'remaining_amount' => 9000,
            'grand_total' => 9000,
            'subtotal' => 9000,
            'paid_amount' => 0,
            'due_at' => '2026-07-20 09:00:00',
        ]);

        $rows = app(FinancialDashboardSummaryService::class)->topOverdueCustomers(request(), 5);

        $this->assertCount(2, $rows);
        $this->assertSame($firstCustomer->id, $rows[0]['customer_id']);
        $this->assertSame('عميل متأخر أكبر', $rows[0]['customer_name']);
        $this->assertSame(2, $rows[0]['invoice_count']);
        $this->assertSame(6500.00, $rows[0]['overdue_total']);
        $this->assertSame('2026-05-01', $rows[0]['oldest_due_at']);
        $this->assertSame(66, $rows[0]['max_days_overdue']);

        $this->assertSame($secondCustomer->id, $rows[1]['customer_id']);
        $this->assertSame(2000.00, $rows[1]['overdue_total']);
    }

    public function test_main_dashboard_displays_top_overdue_customers_widget(): void
    {
        if (! Route::has('dashboard')) {
            $this->markTestSkipped('dashboard route does not exist.');
        }

        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer([
            'name' => 'عميل ظاهر في ودجت المتأخرات',
            'phone' => '0579853233',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $customer->id,
            'invoice_number' => 'SI-TOP-CUSTOMER-DASHBOARD',
            'remaining_amount' => 5000,
            'grand_total' => 5000,
            'subtotal' => 5000,
            'paid_amount' => 0,
            'due_at' => '2026-05-01 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('data-testid="main-dashboard-top-overdue-customers"', false);
        $response->assertSee('أكبر العملاء المتأخرين');
        $response->assertSee('عميل ظاهر في ودجت المتأخرات');
        $response->assertSee('5,000.00 ريال');
        $response->assertSee('2026-05-01');
        $response->assertSee('66 يوم');
        $response->assertSee('data-testid="main-dashboard-top-overdue-customer-link-' . $customer->id . '"', false);
        $response->assertSee(route('reports.customer-sales-invoice-aging.drilldown', ['customer_id' => $customer->id]), false);
        $response->assertSee('data-testid="main-dashboard-top-overdue-customers-more-link"', false);
    }

    public function test_main_dashboard_displays_top_overdue_customers_empty_state(): void
    {
        if (! Route::has('dashboard')) {
            $this->markTestSkipped('dashboard route does not exist.');
        }

        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('data-testid="main-dashboard-top-overdue-customers-empty"', false);
        $response->assertSee('لا توجد فواتير عملاء متأخرة حاليًا.');
    }

    private function createCustomer(array $overrides = []): Customer
    {
        $columns = Schema::getColumnListing('customers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'عميل اختبار ودجت المتأخرات',
            'phone' => '0579853200',
            'email' => uniqid('main-dashboard-top-overdue-customer-') . '@example.com',
            'city' => 'الرياض',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return Customer::unguarded(fn () => Customer::query()->create(array_intersect_key($data, array_flip($columns))));
    }

    private function createSalesInvoice(array $overrides = []): SalesInvoice
    {
        $columns = Schema::getColumnListing('sales_invoices');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'branch_id' => (int) DB::table('branches')->value('id'),
            'customer_id' => null,
            'user_id' => (int) DB::table('users')->value('id'),
            'invoice_number' => uniqid('SI-TOP-OVERDUE-CUSTOMER-'),
            'status' => 'issued',
            'payment_status' => 'partial',
            'currency' => 'SAR',
            'subtotal' => 1000,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 1000,
            'paid_amount' => 0,
            'remaining_amount' => 1000,
            'issued_at' => '2026-07-01 09:00:00',
            'due_at' => '2026-07-01 09:00:00',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return SalesInvoice::unguarded(fn () => SalesInvoice::query()->create(array_intersect_key($data, array_flip($columns))));
    }
}
