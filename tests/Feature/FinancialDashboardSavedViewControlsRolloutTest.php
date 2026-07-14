<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Support\Reports\ReportSavedViewCandidateScanner;
use App\Support\Reports\ReportSavedViewRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FinancialDashboardSavedViewControlsRolloutTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_dashboard_config_partial_exists_and_uses_shared_controls(): void
    {
        $configPartial = resource_path('views/reports/partials/financial-dashboard-saved-view-controls-config.blade.php');

        $this->assertFileExists($configPartial);

        $contents = file_get_contents($configPartial);

        $this->assertStringContainsString('$financialDashboardSavedViewControlsConfig = [', $contents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-controls'", $contents);
        $this->assertStringContainsString("'routeName' => 'reports.financial-dashboard'", $contents);
        $this->assertStringContainsString("'storeRouteName' => 'reports.financial-dashboard.saved-views.store'", $contents);
        $this->assertStringContainsString("'hiddenFields' => []", $contents);

        foreach ([
            'financial-dashboard-saved-views-selector',
            'financial-dashboard-saved-views-empty',
            'financial-dashboard-save-view-card',
            'financial-dashboard-save-view-form',
            'financial-dashboard-saved-view-name-input',
            'financial-dashboard-saved-view-default-checkbox',
            'financial-dashboard-save-view-button',
            'financial-dashboard-saved-views-list',
            'financial-dashboard-saved-view-item',
            'financial-dashboard-saved-view-open-link',
            'financial-dashboard-saved-view-active-badge',
            'financial-dashboard-saved-view-default-badge',
        ] as $testId) {
            $this->assertStringContainsString($testId, $contents);
        }
    }

    public function test_financial_dashboard_routes_view_and_registry_are_wired(): void
    {
        $this->assertTrue(Route::has('reports.financial-dashboard'));
        $this->assertTrue(Route::has('reports.financial-dashboard.json'));
        $this->assertTrue(Route::has('reports.financial-dashboard.saved-views.store'));

        $controller = file_get_contents(app_path('Http/Controllers/FinancialDashboardController.php'));
        $view = file_get_contents(resource_path('views/reports/financial-dashboard.blade.php'));

        $this->assertStringContainsString("private const REPORT_KEY = 'financial-dashboard';", $controller);
        $this->assertStringContainsString('ReportSavedViewService', $controller);
        $this->assertStringContainsString('$savedViews', $controller);
        $this->assertStringContainsString('public function json(): JsonResponse', $controller);
        $this->assertStringContainsString('public function storeSavedView(Request $request, ReportSavedViewService $savedViews): RedirectResponse', $controller);
        $this->assertStringContainsString("@include('reports.partials.financial-dashboard-saved-view-controls-config')", $view);
        $this->assertStringContainsString('data-testid="financial-dashboard-status"', $view);

        $this->assertTrue(ReportSavedViewRegistry::has('financial-dashboard'));

        $report = ReportSavedViewRegistry::find('financial-dashboard');

        $this->assertSame('financial-dashboard', $report['key']);
        $this->assertSame('الداشبورد المالية', $report['label']);
        $this->assertSame('reports.financial-dashboard', $report['index_route']);
        $this->assertSame('reports.financial-dashboard.json', $report['export_route']);
        $this->assertSame('reports.financial-dashboard.saved-views.store', $report['saved_view_store_route']);
        $this->assertSame([], $report['hidden_fields']);
        $this->assertSame('financial-dashboard-save-view-form', $report['test_ids']['form']);
    }

    public function test_financial_dashboard_renders_saves_empty_filter_saved_view_and_json_export(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.financial-dashboard'))
            ->assertOk()
            ->assertSee('data-testid="financial-dashboard-saved-views-selector"', false)
            ->assertSee('data-testid="financial-dashboard-saved-views-empty"', false)
            ->assertSee('data-testid="financial-dashboard-save-view-card"', false)
            ->assertSee('data-testid="financial-dashboard-save-view-form"', false)
            ->assertSee('data-testid="financial-dashboard-saved-view-name-input"', false)
            ->assertSee('data-testid="financial-dashboard-saved-view-default-checkbox"', false)
            ->assertSee('data-testid="financial-dashboard-save-view-button"', false);

        $this->actingAs($user)
            ->get(route('reports.financial-dashboard.json'))
            ->assertOk()
            ->assertJsonStructure([
                'fromDate',
                'toDate',
                'currentMonthRevenues',
                'currentMonthExpenses',
                'currentMonthNetProfit',
                'uncollectedRevenues',
                'unpaidExpenses',
            ]);

        $this->actingAs($user)
            ->post(route('reports.financial-dashboard.saved-views.store'), [
                'name' => 'الداشبورد المالية الحالية',
                'is_default' => '1',
            ])
            ->assertRedirect(route('reports.financial-dashboard'));

        $this->assertDatabaseHas('report_saved_views', [
            'user_id' => $user->id,
            'report_key' => 'financial-dashboard',
            'name' => 'الداشبورد المالية الحالية',
            'is_default' => true,
        ]);

        $savedView = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('report_key', 'financial-dashboard')
            ->first();

        $this->assertNotNull($savedView);
        $this->assertSame([], $savedView->filters);
    }

    public function test_financial_dashboard_candidate_scanner_marks_target_registered(): void
    {
        $candidate = collect(ReportSavedViewCandidateScanner::candidates())
            ->firstWhere('key', 'financial-dashboard');

        $this->assertNotNull($candidate);
        $this->assertTrue($candidate['registered']);
        $this->assertTrue($candidate['has_saved_view_controls']);
    }
}
