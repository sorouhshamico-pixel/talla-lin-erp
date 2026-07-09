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

class SupplierPurchaseInvoiceAgingDrilldownSavedViewTest extends TestCase
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

    public function test_supplier_aging_drilldown_shows_save_view_form(): void
    {
        $user = User::query()->firstOrFail();
        $supplier = Supplier::query()->orderBy('id')->firstOrFail();
        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.drilldown', [
            'supplier_id' => $supplier->id,
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-31',
            'aging_bucket' => 'without_due_date',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-aging-drilldown-save-view-form"', false);
        $response->assertSee(route('reports.supplier-purchase-invoice-aging.drilldown.saved-views.store'), false);
        $response->assertSee('value="' . $supplier->id . '"', false);
        $response->assertSee('value="' . $branchId . '"', false);
        $response->assertSee('value="2026-07-31"', false);
        $response->assertSee('value="without_due_date"', false);
    }

    public function test_user_can_save_supplier_aging_drilldown_filters_as_named_view(): void
    {
        $user = User::query()->firstOrFail();
        $supplier = Supplier::query()->orderBy('id')->firstOrFail();
        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');

        $response = $this->actingAs($user)->post(route('reports.supplier-purchase-invoice-aging.drilldown.saved-views.store'), [
            'name' => 'تفاصيل موردين نهاية الشهر',
            'supplier_id' => $supplier->id,
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-31',
            'aging_bucket' => 'without_due_date',
            'is_default' => '1',
        ]);

        $response->assertRedirect(route('reports.supplier-purchase-invoice-aging.drilldown', [
            'supplier_id' => $supplier->id,
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-31',
            'aging_bucket' => 'without_due_date',
        ]));

        $savedView = ReportSavedView::query()->firstOrFail();

        $this->assertSame($user->id, $savedView->user_id);
        $this->assertSame('supplier-purchase-invoice-aging-drilldown', $savedView->report_key);
        $this->assertSame('تفاصيل موردين نهاية الشهر', $savedView->name);
        $this->assertTrue($savedView->is_default);
        $this->assertSame([
            'supplier_id' => $supplier->id,
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-31',
            'aging_bucket' => 'without_due_date',
        ], $savedView->filters);
    }

    public function test_supplier_aging_drilldown_saved_view_requires_name(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this
            ->actingAs($user)
            ->from(route('reports.supplier-purchase-invoice-aging.drilldown'))
            ->post(route('reports.supplier-purchase-invoice-aging.drilldown.saved-views.store'), [
                'name' => '',
                'as_of_date' => '2026-07-31',
            ]);

        $response->assertRedirect(route('reports.supplier-purchase-invoice-aging.drilldown'));
        $response->assertSessionHasErrors('name');
    }
}
