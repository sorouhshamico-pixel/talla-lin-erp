<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SupplierPurchaseInvoiceAgingDrilldownDefaultSavedViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-09 10:00:00');

        $this->seed(InitialSetupSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_supplier_aging_drilldown_applies_default_saved_view_when_opened_without_filters(): void
    {
        $user = User::query()->firstOrFail();
        $supplier = Supplier::query()->orderBy('id')->firstOrFail();
        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'supplier-purchase-invoice-aging-drilldown',
            'name' => 'عرض افتراضي لتفاصيل الموردين',
            'filters' => [
                'supplier_id' => $supplier->id,
                'branch_id' => $branchId,
                'as_of_date' => '2026-07-31',
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.drilldown'));

        $response->assertOk();

        $preference = DB::table('user_report_filter_preferences')
            ->where('user_id', $user->id)
            ->where('report_key', 'supplier-purchase-invoice-aging-drilldown')
            ->first();

        $this->assertNotNull($preference);

        $filters = json_decode($preference->filters, true);

        $this->assertSame($supplier->id, $filters['supplier_id']);
        $this->assertSame($branchId, $filters['branch_id']);
        $this->assertSame('2026-07-31', $filters['as_of_date']);
        $this->assertSame('without_due_date', $filters['aging_bucket']);
    }

    public function test_supplier_aging_drilldown_explicit_filters_override_default_saved_view(): void
    {
        $user = User::query()->firstOrFail();
        $supplier = Supplier::query()->orderBy('id')->firstOrFail();
        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'supplier-purchase-invoice-aging-drilldown',
            'name' => 'عرض افتراضي لتفاصيل الموردين',
            'filters' => [
                'supplier_id' => $supplier->id,
                'branch_id' => $branchId,
                'as_of_date' => '2026-07-31',
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.drilldown', [
            'aging_bucket' => 'not_due',
        ]));

        $response->assertOk();

        $preference = DB::table('user_report_filter_preferences')
            ->where('user_id', $user->id)
            ->where('report_key', 'supplier-purchase-invoice-aging-drilldown')
            ->first();

        $this->assertNotNull($preference);

        $filters = json_decode($preference->filters, true);
        $nonEmptyFilters = array_filter($filters, fn ($value) => $value !== null && $value !== '');

        $this->assertSame('not_due', $filters['aging_bucket']);
        $this->assertArrayNotHasKey('supplier_id', $nonEmptyFilters);
        $this->assertArrayNotHasKey('branch_id', $nonEmptyFilters);
        $this->assertArrayNotHasKey('as_of_date', $nonEmptyFilters);
    }

    public function test_supplier_aging_drilldown_reset_filters_does_not_apply_default_saved_view(): void
    {
        $user = User::query()->firstOrFail();
        $supplier = Supplier::query()->orderBy('id')->firstOrFail();
        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'supplier-purchase-invoice-aging-drilldown',
            'name' => 'عرض افتراضي لتفاصيل الموردين',
            'filters' => [
                'supplier_id' => $supplier->id,
                'branch_id' => $branchId,
                'as_of_date' => '2026-07-31',
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.drilldown', [
            'reset_filters' => 1,
        ]));

        $response->assertOk();

        $this->assertDatabaseMissing('user_report_filter_preferences', [
            'user_id' => $user->id,
            'report_key' => 'supplier-purchase-invoice-aging-drilldown',
        ]);
    }
}
