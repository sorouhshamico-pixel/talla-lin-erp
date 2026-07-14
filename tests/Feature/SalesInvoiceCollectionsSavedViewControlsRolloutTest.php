<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Support\Reports\ReportSavedViewCandidateScanner;
use App\Support\Reports\ReportSavedViewRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SalesInvoiceCollectionsSavedViewControlsRolloutTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_invoice_collections_config_partial_exists_and_uses_shared_controls(): void
    {
        $configPartial = resource_path('views/reports/partials/sales-invoice-collections-saved-view-controls-config.blade.php');

        $this->assertFileExists($configPartial);

        $contents = file_get_contents($configPartial);

        $this->assertStringContainsString('$salesInvoiceCollectionsSavedViewControlsConfig = [', $contents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-controls'", $contents);
        $this->assertStringContainsString("'routeName' => 'reports.sales-invoice-collections.index'", $contents);
        $this->assertStringContainsString("'storeRouteName' => 'reports.sales-invoice-collections.saved-views.store'", $contents);
        $this->assertStringContainsString("'hiddenFields' => []", $contents);

        foreach ([
            'sales-invoice-collections-saved-views-selector',
            'sales-invoice-collections-saved-views-empty',
            'sales-invoice-collections-save-view-card',
            'sales-invoice-collections-save-view-form',
            'sales-invoice-collections-saved-view-name-input',
            'sales-invoice-collections-saved-view-default-checkbox',
            'sales-invoice-collections-save-view-button',
            'sales-invoice-collections-saved-views-list',
            'sales-invoice-collections-saved-view-item',
            'sales-invoice-collections-saved-view-open-link',
            'sales-invoice-collections-saved-view-active-badge',
            'sales-invoice-collections-saved-view-default-badge',
        ] as $testId) {
            $this->assertStringContainsString($testId, $contents);
        }
    }

    public function test_sales_invoice_collections_routes_view_and_registry_are_wired(): void
    {
        $this->assertTrue(Route::has('reports.sales-invoice-collections.index'));
        $this->assertTrue(Route::has('reports.sales-invoice-collections.json'));
        $this->assertTrue(Route::has('reports.sales-invoice-collections.saved-views.store'));

        $controller = file_get_contents(app_path('Http/Controllers/SalesInvoiceCollectionReportController.php'));
        $view = file_get_contents(resource_path('views/reports/sales-invoice-collections.blade.php'));

        $this->assertStringContainsString('private const REPORT_KEY = \'sales-invoice-collections\';', $controller);
        $this->assertStringContainsString('ReportSavedViewService $savedViews', $controller);
        $this->assertStringContainsString('public function json(): JsonResponse', $controller);
        $this->assertStringContainsString('public function storeSavedView(Request $request, ReportSavedViewService $savedViews): RedirectResponse', $controller);
        $this->assertStringContainsString("@include('reports.partials.sales-invoice-collections-saved-view-controls-config')", $view);
        $this->assertStringContainsString('data-testid="sales-invoice-collections-status"', $view);

        $this->assertTrue(ReportSavedViewRegistry::has('sales-invoice-collections'));

        $report = ReportSavedViewRegistry::find('sales-invoice-collections');

        $this->assertSame('sales-invoice-collections', $report['key']);
        $this->assertSame('تقرير تحصيل فواتير المبيعات', $report['label']);
        $this->assertSame('reports.sales-invoice-collections.index', $report['index_route']);
        $this->assertSame('reports.sales-invoice-collections.json', $report['export_route']);
        $this->assertSame('reports.sales-invoice-collections.saved-views.store', $report['saved_view_store_route']);
        $this->assertSame([], $report['hidden_fields']);
        $this->assertSame('sales-invoice-collections-save-view-form', $report['test_ids']['form']);
    }

    public function test_sales_invoice_collections_renders_saves_empty_filter_saved_view_and_json_export(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.sales-invoice-collections.index'))
            ->assertOk()
            ->assertSee('data-testid="sales-invoice-collections-saved-views-selector"', false)
            ->assertSee('data-testid="sales-invoice-collections-saved-views-empty"', false)
            ->assertSee('data-testid="sales-invoice-collections-save-view-card"', false)
            ->assertSee('data-testid="sales-invoice-collections-save-view-form"', false)
            ->assertSee('data-testid="sales-invoice-collections-saved-view-name-input"', false)
            ->assertSee('data-testid="sales-invoice-collections-saved-view-default-checkbox"', false)
            ->assertSee('data-testid="sales-invoice-collections-save-view-button"', false);

        $this->actingAs($user)
            ->get(route('reports.sales-invoice-collections.json'))
            ->assertOk()
            ->assertJsonPath('summary.outstanding_count', 0)
            ->assertJsonCount(0, 'invoices');

        $this->actingAs($user)
            ->post(route('reports.sales-invoice-collections.saved-views.store'), [
                'name' => 'تحصيل الفواتير الحالية',
                'is_default' => '1',
            ])
            ->assertRedirect(route('reports.sales-invoice-collections.index'));

        $this->assertDatabaseHas('report_saved_views', [
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-collections',
            'name' => 'تحصيل الفواتير الحالية',
            'is_default' => true,
        ]);

        $savedView = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('report_key', 'sales-invoice-collections')
            ->first();

        $this->assertNotNull($savedView);
        $this->assertSame([], $savedView->filters);
    }
    public function test_sales_invoice_collections_candidate_scanner_marks_target_registered(): void
    {
        $candidate = collect(ReportSavedViewCandidateScanner::candidates())
            ->firstWhere('key', 'sales-invoice-collections');

        $this->assertNotNull($candidate);
        $this->assertTrue($candidate['registered']);
        $this->assertTrue($candidate['has_saved_view_controls']);
    }
}
