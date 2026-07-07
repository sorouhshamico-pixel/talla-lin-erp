<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AgingDrilldownReportDatePresetTest extends TestCase
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

    public function test_customer_aging_drilldown_displays_report_date_preset_links(): void
    {
        if (! Route::has('reports.customer-sales-invoice-aging.drilldown')) {
            $this->markTestSkipped('customer aging drilldown route does not exist.');
        }

        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.drilldown'));

        $response->assertOk();
        $response->assertSee('data-testid="customer-aging-drilldown-date-presets"', false);
        $response->assertSee('data-testid="customer-aging-drilldown-date-preset-today"', false);
        $response->assertSee('data-testid="customer-aging-drilldown-date-preset-month-end"', false);
        $response->assertSee('data-testid="customer-aging-drilldown-date-preset-previous-month-end"', false);
        $response->assertSee('data-testid="customer-aging-drilldown-date-preset-quarter-end"', false);

        $response->assertSee(route('reports.customer-sales-invoice-aging.drilldown', ['as_of_date' => '2026-07-06']), false);
        $response->assertSee(route('reports.customer-sales-invoice-aging.drilldown', ['as_of_date' => '2026-07-31']), false);
        $response->assertSee(route('reports.customer-sales-invoice-aging.drilldown', ['as_of_date' => '2026-06-30']), false);
        $response->assertSee(route('reports.customer-sales-invoice-aging.drilldown', ['as_of_date' => '2026-09-30']), false);
    }

    public function test_supplier_aging_drilldown_displays_report_date_preset_links(): void
    {
        if (! Route::has('reports.supplier-purchase-invoice-aging.drilldown')) {
            $this->markTestSkipped('supplier aging drilldown route does not exist.');
        }

        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.drilldown'));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-aging-drilldown-date-presets"', false);
        $response->assertSee('data-testid="supplier-aging-drilldown-date-preset-today"', false);
        $response->assertSee('data-testid="supplier-aging-drilldown-date-preset-month-end"', false);
        $response->assertSee('data-testid="supplier-aging-drilldown-date-preset-previous-month-end"', false);
        $response->assertSee('data-testid="supplier-aging-drilldown-date-preset-quarter-end"', false);

        $response->assertSee(route('reports.supplier-purchase-invoice-aging.drilldown', ['as_of_date' => '2026-07-06']), false);
        $response->assertSee(route('reports.supplier-purchase-invoice-aging.drilldown', ['as_of_date' => '2026-07-31']), false);
        $response->assertSee(route('reports.supplier-purchase-invoice-aging.drilldown', ['as_of_date' => '2026-06-30']), false);
        $response->assertSee(route('reports.supplier-purchase-invoice-aging.drilldown', ['as_of_date' => '2026-09-30']), false);
    }

    public function test_customer_aging_drilldown_report_date_preset_links_preserve_filters(): void
    {
        $user = User::query()->firstOrFail();

        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');

        $customer = $this->createCustomer([
            'name' => 'عميل اختصارات تاريخ التفاصيل',
        ]);

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.drilldown', [
            'customer_id' => $customer->id,
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-20',
            'aging_bucket' => 'overdue_1_30',
        ]));

        $response->assertOk();

        $response->assertSee(e(route('reports.customer-sales-invoice-aging.drilldown', [
            'customer_id' => $customer->id,
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-06',
            'aging_bucket' => 'overdue_1_30',
        ])), false);

        $response->assertSee(e(route('reports.customer-sales-invoice-aging.drilldown', [
            'customer_id' => $customer->id,
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-31',
            'aging_bucket' => 'overdue_1_30',
        ])), false);

        $response->assertSee(e(route('reports.customer-sales-invoice-aging.drilldown', [
            'customer_id' => $customer->id,
            'branch_id' => $branchId,
            'as_of_date' => '2026-06-30',
            'aging_bucket' => 'overdue_1_30',
        ])), false);

        $response->assertSee(e(route('reports.customer-sales-invoice-aging.drilldown', [
            'customer_id' => $customer->id,
            'branch_id' => $branchId,
            'as_of_date' => '2026-09-30',
            'aging_bucket' => 'overdue_1_30',
        ])), false);
    }

    public function test_supplier_aging_drilldown_report_date_preset_links_preserve_filters(): void
    {
        $user = User::query()->firstOrFail();

        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');

        $supplier = $this->createSupplier([
            'name' => 'مورد اختصارات تاريخ التفاصيل',
        ]);

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.drilldown', [
            'supplier_id' => $supplier->id,
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-20',
            'aging_bucket' => 'overdue_1_30',
        ]));

        $response->assertOk();

        $response->assertSee(e(route('reports.supplier-purchase-invoice-aging.drilldown', [
            'supplier_id' => $supplier->id,
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-06',
            'aging_bucket' => 'overdue_1_30',
        ])), false);

        $response->assertSee(e(route('reports.supplier-purchase-invoice-aging.drilldown', [
            'supplier_id' => $supplier->id,
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-31',
            'aging_bucket' => 'overdue_1_30',
        ])), false);

        $response->assertSee(e(route('reports.supplier-purchase-invoice-aging.drilldown', [
            'supplier_id' => $supplier->id,
            'branch_id' => $branchId,
            'as_of_date' => '2026-06-30',
            'aging_bucket' => 'overdue_1_30',
        ])), false);

        $response->assertSee(e(route('reports.supplier-purchase-invoice-aging.drilldown', [
            'supplier_id' => $supplier->id,
            'branch_id' => $branchId,
            'as_of_date' => '2026-09-30',
            'aging_bucket' => 'overdue_1_30',
        ])), false);
    }

    private function createCustomer(array $overrides = []): Customer
    {
        $columns = Schema::getColumnListing('customers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'عميل اختبار اختصارات التاريخ',
            'phone' => '0579854200',
            'email' => uniqid('aging-date-preset-customer-') . '@example.com',
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
            'name' => 'مورد اختبار اختصارات التاريخ',
            'phone' => '0579854201',
            'email' => uniqid('aging-date-preset-supplier-') . '@example.com',
            'city' => 'الرياض',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return Supplier::unguarded(fn () => Supplier::query()->create(array_intersect_key($data, array_flip($columns))));
    }
}
