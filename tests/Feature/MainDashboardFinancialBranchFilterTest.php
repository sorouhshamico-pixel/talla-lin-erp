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

class MainDashboardFinancialBranchFilterTest extends TestCase
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

    public function test_financial_dashboard_summary_service_filters_summary_and_top_overdue_by_branch(): void
    {
        [$firstBranchId, $secondBranchId] = $this->branchIds();

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer(['name' => 'عميل فلتر الفرع']);
        $supplier = $this->createSupplier(['name' => 'مورد فلتر الفرع']);

        $this->createSalesInvoice([
            'branch_id' => $firstBranchId,
            'customer_id' => $customer->id,
            'remaining_amount' => 5000,
            'grand_total' => 5000,
            'subtotal' => 5000,
            'due_at' => '2026-05-01 09:00:00',
        ]);

        $this->createSalesInvoice([
            'branch_id' => $secondBranchId,
            'customer_id' => $customer->id,
            'remaining_amount' => 9000,
            'grand_total' => 9000,
            'subtotal' => 9000,
            'due_at' => '2026-05-01 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'branch_id' => $firstBranchId,
            'supplier_id' => $supplier->id,
            'remaining_amount' => 3000,
            'grand_total' => 3000,
            'subtotal' => 3000,
            'due_at' => '2026-05-01 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'branch_id' => $secondBranchId,
            'supplier_id' => $supplier->id,
            'remaining_amount' => 8000,
            'grand_total' => 8000,
            'subtotal' => 8000,
            'due_at' => '2026-05-01 09:00:00',
        ]);

        $request = Request::create('/dashboard', 'GET', [
            'branch_id' => $firstBranchId,
        ]);

        $service = app(FinancialDashboardSummaryService::class);

        $summary = $service->summary($request);

        $this->assertSame(1, $summary['customers_count']);
        $this->assertSame(1, $summary['suppliers_count']);
        $this->assertSame(1, $summary['customer_open_invoice_count']);
        $this->assertSame(1, $summary['supplier_open_invoice_count']);
        $this->assertSame(5000.00, $summary['expected_inflows']);
        $this->assertSame(3000.00, $summary['expected_outflows']);
        $this->assertSame(2000.00, $summary['net_expected_cash']);

        $customers = $service->topOverdueCustomers($request, 5);
        $suppliers = $service->topOverdueSuppliers($request, 5);

        $this->assertCount(1, $customers);
        $this->assertSame(5000.00, $customers[0]['overdue_total']);

        $this->assertCount(1, $suppliers);
        $this->assertSame(3000.00, $suppliers[0]['overdue_total']);
    }

    public function test_main_dashboard_displays_branch_filter_and_preserves_branch_on_actions(): void
    {
        if (! Route::has('dashboard')) {
            $this->markTestSkipped('dashboard route does not exist.');
        }

        [$branchId] = $this->branchIds();

        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('dashboard', [
            'branch_id' => $branchId,
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="main-dashboard-financial-branch-filter"', false);
        $response->assertSee('data-testid="main-dashboard-financial-branch-select"', false);
        $response->assertSee('data-testid="main-dashboard-financial-branch-apply"', false);
        $response->assertSee('data-testid="main-dashboard-financial-branch-reset"', false);
        $response->assertSee(route('dashboard.financial-summary.export', ['branch_id' => $branchId]), false);
        $response->assertSee(route('dashboard.financial-summary.print', ['branch_id' => $branchId]), false);
        $response->assertSee(route('dashboard.top-overdue-customers.export', ['branch_id' => $branchId]), false);
        $response->assertSee(route('dashboard.top-overdue-suppliers.export', ['branch_id' => $branchId]), false);
        $response->assertSee(route('dashboard.top-overdue.print', ['branch_id' => $branchId]), false);
    }

    private function branchIds(): array
    {
        $ids = DB::table('branches')->orderBy('id')->pluck('id')->all();

        while (count($ids) < 2) {
            $columns = Schema::getColumnListing('branches');

            $data = [
                'company_id' => (int) DB::table('companies')->value('id'),
                'name' => 'فرع اختبار لوحة التحكم ' . (count($ids) + 1),
                'code' => 'BR-' . uniqid(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $ids[] = DB::table('branches')->insertGetId(array_intersect_key($data, array_flip($columns)));
        }

        return array_slice($ids, 0, 2);
    }

    private function createCustomer(array $overrides = []): Customer
    {
        $columns = Schema::getColumnListing('customers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'عميل اختبار فلتر فرع لوحة التحكم',
            'phone' => '0579853600',
            'email' => uniqid('main-dashboard-branch-customer-') . '@example.com',
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
            'name' => 'مورد اختبار فلتر فرع لوحة التحكم',
            'phone' => '0579853601',
            'email' => uniqid('main-dashboard-branch-supplier-') . '@example.com',
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
            'invoice_number' => uniqid('SI-MAIN-BRANCH-'),
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
            'invoice_number' => uniqid('PI-MAIN-BRANCH-'),
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
