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

class MainDashboardTopOverdueSuppliersTest extends TestCase
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

    public function test_financial_dashboard_summary_service_returns_top_overdue_suppliers(): void
    {
        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $firstSupplier = $this->createSupplier([
            'name' => 'مورد متأخر أكبر',
            'phone' => '0579853331',
        ]);

        $secondSupplier = $this->createSupplier([
            'name' => 'مورد متأخر أصغر',
            'phone' => '0579853332',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $firstSupplier->id,
            'invoice_number' => 'PI-TOP-SUPPLIER-001',
            'remaining_amount' => 7000,
            'grand_total' => 7000,
            'subtotal' => 7000,
            'paid_amount' => 0,
            'due_at' => '2026-05-01 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $firstSupplier->id,
            'invoice_number' => 'PI-TOP-SUPPLIER-002',
            'remaining_amount' => 2500,
            'grand_total' => 2500,
            'subtotal' => 2500,
            'paid_amount' => 0,
            'due_at' => '2026-06-01 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $secondSupplier->id,
            'invoice_number' => 'PI-TOP-SUPPLIER-003',
            'remaining_amount' => 3000,
            'grand_total' => 3000,
            'subtotal' => 3000,
            'paid_amount' => 0,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $firstSupplier->id,
            'invoice_number' => 'PI-TOP-SUPPLIER-NOT-DUE',
            'remaining_amount' => 9000,
            'grand_total' => 9000,
            'subtotal' => 9000,
            'paid_amount' => 0,
            'due_at' => '2026-07-20 09:00:00',
        ]);

        $rows = app(FinancialDashboardSummaryService::class)->topOverdueSuppliers(request(), 5);

        $this->assertCount(2, $rows);
        $this->assertSame($firstSupplier->id, $rows[0]['supplier_id']);
        $this->assertSame('مورد متأخر أكبر', $rows[0]['supplier_name']);
        $this->assertSame(2, $rows[0]['invoice_count']);
        $this->assertSame(9500.00, $rows[0]['overdue_total']);
        $this->assertSame('2026-05-01', $rows[0]['oldest_due_at']);
        $this->assertSame(66, $rows[0]['max_days_overdue']);

        $this->assertSame($secondSupplier->id, $rows[1]['supplier_id']);
        $this->assertSame(3000.00, $rows[1]['overdue_total']);
    }

    public function test_main_dashboard_displays_top_overdue_suppliers_widget(): void
    {
        if (! Route::has('dashboard')) {
            $this->markTestSkipped('dashboard route does not exist.');
        }

        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $supplier = $this->createSupplier([
            'name' => 'مورد ظاهر في ودجت المتأخرات',
            'phone' => '0579853333',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PI-TOP-SUPPLIER-DASHBOARD',
            'remaining_amount' => 7000,
            'grand_total' => 7000,
            'subtotal' => 7000,
            'paid_amount' => 0,
            'due_at' => '2026-05-01 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('data-testid="main-dashboard-top-overdue-suppliers"', false);
        $response->assertSee('أكبر الموردين المتأخرين');
        $response->assertSee('مورد ظاهر في ودجت المتأخرات');
        $response->assertSee('7,000.00 ريال');
        $response->assertSee('2026-05-01');
        $response->assertSee('66 يوم');
        $response->assertSee('data-testid="main-dashboard-top-overdue-supplier-link-' . $supplier->id . '"', false);
        $response->assertSee(route('reports.supplier-purchase-invoice-aging.drilldown', ['supplier_id' => $supplier->id]), false);
        $response->assertSee('data-testid="main-dashboard-top-overdue-suppliers-more-link"', false);
    }

    public function test_main_dashboard_displays_top_overdue_suppliers_empty_state(): void
    {
        if (! Route::has('dashboard')) {
            $this->markTestSkipped('dashboard route does not exist.');
        }

        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('data-testid="main-dashboard-top-overdue-suppliers-empty"', false);
        $response->assertSee('لا توجد فواتير موردين متأخرة حاليًا.');
    }

    private function createSupplier(array $overrides = []): Supplier
    {
        $columns = Schema::getColumnListing('suppliers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'مورد اختبار ودجت المتأخرات',
            'phone' => '0579853300',
            'email' => uniqid('main-dashboard-top-overdue-supplier-') . '@example.com',
            'city' => 'الرياض',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return Supplier::unguarded(fn () => Supplier::query()->create(array_intersect_key($data, array_flip($columns))));
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
            'invoice_number' => uniqid('PI-TOP-OVERDUE-SUPPLIER-'),
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
