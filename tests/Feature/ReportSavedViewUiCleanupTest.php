<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewUiCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_sales_invoice_saved_views_use_consistent_ui_classes(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض واجهة نشط',
            'filters' => [
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-aging.index', [
            'saved_view_id' => $savedView->id,
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="report-saved-view-list-styles"', false);
        $response->assertSee('class="saved-view-link"', false);
        $response->assertSee('class="saved-view-badges"', false);
        $response->assertSee('class="saved-view-badge saved-view-badge-active"', false);
        $response->assertSee('class="saved-view-badge saved-view-badge-default"', false);
    }

    public function test_supplier_drilldown_saved_views_use_consistent_ui_classes(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'supplier-purchase-invoice-aging-drilldown',
            'name' => 'عرض تفاصيل مورد نشط',
            'filters' => [
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.drilldown', [
            'saved_view_id' => $savedView->id,
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="report-saved-view-list-styles"', false);
        $response->assertSee('class="saved-view-link"', false);
        $response->assertSee('class="saved-view-badges"', false);
        $response->assertSee('class="saved-view-badge saved-view-badge-active"', false);
        $response->assertSee('class="saved-view-badge saved-view-badge-default"', false);
    }
}
