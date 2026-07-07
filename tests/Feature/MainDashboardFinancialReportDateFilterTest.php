<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\Supplier;
use App\Models\User;
use App\Services\FinancialDashboardSummaryService;
use Carbon\Carbon;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MainDashboardFinancialReportDateFilterTest extends TestCase
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

    public function test_financial_dashboard_summary_uses_as_of_date_for_overdue_calculations(): void
    {
        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer(['name' => 'عميل تاريخ التقرير']);
        $supplier = $this->createSupplier(['name' => 'مورد تاريخ التقرير']);

        $this->createSalesInvoice([
            'customer_id' => $customer->id,
            'remaining_amount' => 1000,
            'grand_total' => 1000,
            'subtotal' => 1000,
            'due_at' => '2026-06-01 09:00:00',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $customer->id,
            'remaining_amount' => 4000,
            'grand_total' => 4000,
            'subtotal' => 4000,
            'due_at' => '2026-07-10 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $supplier->id,
            'remaining_amount' => 2000,
            'grand_total' => 2000,
            'subtotal' => 2000,
            'due_at' => '2026-06-01 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $supplier->id,
            'remaining_amount' => 6000,
            'grand_total' => 6000,
            'subtotal' => 6000,
            'due_at' => '2026-07-10 09:00:00',
        ]);

        $service = app(FinancialDashboardSummaryService::class);

        $defaultSummary = $service->summary(Request::create('/dashboard', 'GET'));

        $this->assertSame(5000.00, $defaultSummary['expected_inflows']);
        $this->assertSame(8000.00, $defaultSummary['expected_outflows']);
        $this->assertSame(1000.00, $defaultSummary['overdue_inflows']);
        $this->assertSame(2000.00, $defaultSummary['overdue_outflows']);

        $futureSummary = $service->summary(Request::create('/dashboard', 'GET', [
            'as_of_date' => '2026-07-20',
        ]));

        $this->assertSame(5000.00, $futureSummary['expected_inflows']);
        $this->assertSame(8000.00, $futureSummary['expected_outflows']);
        $this->assertSame(5000.00, $futureSummary['overdue_inflows']);
        $this->assertSame(8000.00, $futureSummary['overdue_outflows']);
    }

    public function test_top_overdue_widgets_use_as_of_date(): void
    {
        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer(['name' => 'عميل ودجت تاريخ التقرير']);
        $supplier = $this->createSupplier(['name' => 'مورد ودجت تاريخ التقرير']);

        $this->createSalesInvoice([
            'customer_id' => $customer->id,
            'remaining_amount' => 4000,
            'grand_total' => 4000,
            'subtotal' => 4000,
            'due_at' => '2026-07-10 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $supplier->id,
            'remaining_amount' => 6000,
            'grand_total' => 6000,
            'subtotal' => 6000,
            'due_at' => '2026-07-10 09:00:00',
        ]);

        $service = app(FinancialDashboardSummaryService::class);

        $this->assertSame([], $service->topOverdueCustomers(Request::create('/dashboard', 'GET'), 5));
        $this->assertSame([], $service->topOverdueSuppliers(Request::create('/dashboard', 'GET'), 5));

        $request = Request::create('/dashboard', 'GET', [
            'as_of_date' => '2026-07-20',
        ]);

        $customers = $service->topOverdueCustomers($request, 5);
        $suppliers = $service->topOverdueSuppliers($request, 5);

        $this->assertCount(1, $customers);
        $this->assertSame('عميل ودجت تاريخ التقرير', $customers[0]['customer_name']);
        $this->assertSame(4000.00, $customers[0]['overdue_total']);
        $this->assertSame(10, $customers[0]['max_days_overdue']);

        $this->assertCount(1, $suppliers);
        $this->assertSame('مورد ودجت تاريخ التقرير', $suppliers[0]['supplier_name']);
        $this->assertSame(6000.00, $suppliers[0]['overdue_total']);
        $this->assertSame(10, $suppliers[0]['max_days_overdue']);
    }

    public function test_main_dashboard_displays_report_date_filter_and_preserves_as_of_date_on_actions(): void
    {
        if (! Route::has('dashboard')) {
            $this->markTestSkipped('dashboard route does not exist.');
        }

        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('dashboard', [
            'as_of_date' => '2026-07-20',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="main-dashboard-financial-as-of-date-input"', false);
        $response->assertSee('value="2026-07-20"', false);
        $response->assertSee(route('dashboard.financial-summary.export', ['as_of_date' => '2026-07-20']), false);
        $response->assertSee(route('dashboard.financial-summary.print', ['as_of_date' => '2026-07-20']), false);
        $response->assertSee(route('dashboard.top-overdue-customers.export', ['as_of_date' => '2026-07-20']), false);
        $response->assertSee(route('dashboard.top-overdue-suppliers.export', ['as_of_date' => '2026-07-20']), false);
        $response->assertSee(route('dashboard.top-overdue.print', ['as_of_date' => '2026-07-20']), false);
    }

    private function createCustomer(array $overrides = []): Customer
    {
        $columns = Schema::getColumnListing('customers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'عميل اختبار تاريخ تقرير لوحة التحكم',
            'phone' => '0579853900',
            'email' => uniqid('dashboard-as-of-customer-') . '@example.com',
            'city' => 'الرياض',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return Customer::unguarded(fn () => Customer::query()->create(array_intersect_key($data, array_flip($columns))));
    }

    private function createSupplier(array $overrides = []): Supplier
    {
        $columns = Schema::getColumnListing('suppliers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'مورد اختبار تاريخ تقرير لوحة التحكم',
            'phone' => '0579853901',
            'email' => uniqid('dashboard-as-of-supplier-') . '@example.com',
            'city' => 'الرياض',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return Supplier::unguarded(fn () => Supplier::query()->create(array_intersect_key($data, array_flip($columns))));
    }

    private function createSalesInvoice(array $overrides = []): SalesInvoice
    {
        $columns = Schema::getColumnListing('sales_invoices');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'branch_id' => (int) DB::table('branches')->value('id'),
            'customer_id' => null,
            'user_id' => (int) DB::table('users')->value('id'),
            'invoice_number' => uniqid('SI-DASHBOARD-AS-OF-'),
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

    private function createPurchaseInvoice(array $overrides = []): PurchaseInvoice
    {
        $columns = Schema::getColumnListing('purchase_invoices');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'branch_id' => (int) DB::table('branches')->value('id'),
            'warehouse_id' => (int) DB::table('warehouses')->value('id'),
            'supplier_id' => null,
            'user_id' => (int) DB::table('users')->value('id'),
            'invoice_number' => uniqid('PI-DASHBOARD-AS-OF-'),
            'status' => $this->validPurchaseInvoiceStatus(),
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

        return PurchaseInvoice::unguarded(fn () => PurchaseInvoice::query()->create(array_intersect_key($data, array_flip($columns))));
    }

    private function validPurchaseInvoiceStatus(): string
    {
        foreach (glob(database_path('migrations/*.php')) as $file) {
            $migration = file_get_contents($file);

            if (! str_contains($migration, 'purchase_invoices')) {
                continue;
            }

            if (preg_match('/enum\s*\(\s*[\'"]status[\'"]\s*,\s*\[([^\]]+)\]/is', $migration, $matches)) {
                preg_match_all('/[\'"]([^\'"]+)[\'"]/', $matches[1], $values);

                if (! empty($values[1][0])) {
                    return $values[1][0];
                }
            }
        }

        return 'draft';
    }
}
