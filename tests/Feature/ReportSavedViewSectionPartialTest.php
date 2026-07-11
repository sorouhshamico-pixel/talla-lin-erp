<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewSectionPartialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_saved_view_section_partial_renders_full_section_for_sales_report(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض قسم مشترك',
            'filters' => [
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-aging.index', [
            'saved_view_id' => $savedView->id,
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-aging-saved-views-selector"', false);
        $response->assertSee('data-testid="report-saved-view-list-styles"', false);
        $response->assertSee('data-testid="report-saved-view-help-text"', false);
        $response->assertSee('data-testid="active-saved-view-banner"', false);
        $response->assertSee('data-testid="sales-invoice-aging-saved-views-list"', false);
        $response->assertSee('data-testid="sales-invoice-aging-saved-view-active-badge"', false);
        $response->assertSee('data-testid="sales-invoice-aging-saved-view-default-badge"', false);
        $response->assertSee('data-testid="sales-invoice-aging-manage-saved-views-link"', false);
    }

    public function test_saved_view_section_partial_renders_drilldown_empty_state(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.drilldown'));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-aging-drilldown-saved-views-selector"', false);
        $response->assertSee('data-testid="report-saved-view-help-text"', false);
        $response->assertSee('data-testid="supplier-aging-drilldown-saved-views-empty"', false);
        $response->assertSee('لا توجد عروض محفوظة لهذه التفاصيل حتى الآن.');
        $response->assertSee('data-testid="supplier-aging-drilldown-manage-saved-views-link"', false);
    }
}
