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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MainDashboardFinancialSummaryTest extends TestCase
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

    public function test_financial_dashboard_summary_service_calculates_core_values(): void
    {
        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer([
            'name' => 'عميل كروت الصفحة الرئيسية',
            'phone' => '0579852831',
        ]);

        $supplier = $this->createSupplier([
            'name' => 'مورد كروت الصفحة الرئيسية',
            'phone' => '0579852832',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $customer->id,
            'invoice_number' => 'SI-MAIN-DASHBOARD-001',
            'remaining_amount' => 5000,
            'grand_total' => 5000,
            'subtotal' => 5000,
            'paid_amount' => 0,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PI-MAIN-DASHBOARD-001',
            'remaining_amount' => 1800,
            'grand_total' => 1800,
            'subtotal' => 1800,
            'paid_amount' => 0,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $summary = app(FinancialDashboardSummaryService::class)->summary(request());

        $this->assertSame(1, $summary['customers_count']);
        $this->assertSame(1, $summary['suppliers_count']);
        $this->assertSame(1, $summary['customer_open_invoice_count']);
        $this->assertSame(1, $summary['supplier_open_invoice_count']);
        $this->assertSame(5000.00, $summary['expected_inflows']);
        $this->assertSame(1800.00, $summary['expected_outflows']);
        $this->assertSame(3200.00, $summary['net_expected_cash']);
        $this->assertSame('صافي تدفق نقدي متوقع لصالح الشركة', $summary['position_label']);
    }

    public function test_main_dashboard_displays_financial_summary_cards_when_dashboard_route_exists(): void
    {
        if (! Route::has('dashboard')) {
            $this->markTestSkipped('dashboard route does not exist.');
        }

        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('data-testid="main-dashboard-financial-summary"', false);
        $response->assertSee('الملخص المالي السريع');
        $response->assertSee('ذمم العملاء المفتوحة');
        $response->assertSee('التزامات الموردين المفتوحة');
        $response->assertSee('صافي التدفق النقدي المتوقع');
        $response->assertSee('data-testid="main-dashboard-cash-flow-link"', false);
        $response->assertSee('data-testid="main-dashboard-aging-link"', false);
        $response->assertSee('data-testid="main-dashboard-reports-link"', false);
    }

    private function createCustomer(array $overrides = []): Customer
    {
        $columns = Schema::getColumnListing('customers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'عميل اختبار كروت الصفحة الرئيسية',
            'phone' => '0579852800',
            'email' => uniqid('main-dashboard-customer-') . '@example.com',
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
            'name' => 'مورد اختبار كروت الصفحة الرئيسية',
            'phone' => '0579852801',
            'email' => uniqid('main-dashboard-supplier-') . '@example.com',
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
            'invoice_number' => uniqid('SI-MAIN-DASHBOARD-'),
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
            'invoice_number' => uniqid('PI-MAIN-DASHBOARD-'),
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
