<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Reports\ReportSavedViewRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CashFlowDashboardSavedViewControlsRolloutTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_flow_dashboard_saved_view_controls_config_partial_exists_and_uses_shared_controls(): void
    {
        $configPartial = resource_path('views/reports/partials/cash-flow-dashboard-saved-view-controls-config.blade.php');

        $this->assertFileExists($configPartial);

        $contents = file_get_contents($configPartial);

        $this->assertStringContainsString('$cashFlowDashboardSavedViewControlsConfig = [', $contents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-controls'", $contents);

        $this->assertStringContainsString("'routeName' => 'reports.cash-flow-dashboard.index'", $contents);
        $this->assertStringContainsString("'storeRouteName' => 'reports.cash-flow-dashboard.saved-views.store'", $contents);

        foreach ([
            'cash-flow-dashboard-saved-views-selector',
            'cash-flow-dashboard-saved-views-empty',
            'cash-flow-dashboard-save-view-card',
            'cash-flow-dashboard-save-view-form',
            'cash-flow-dashboard-saved-view-name-input',
            'cash-flow-dashboard-saved-view-default-checkbox',
            'cash-flow-dashboard-save-view-button',
            'cash-flow-dashboard-saved-views-list',
            'cash-flow-dashboard-saved-view-item',
            'cash-flow-dashboard-saved-view-open-link',
            'cash-flow-dashboard-saved-view-active-badge',
            'cash-flow-dashboard-saved-view-default-badge',
        ] as $testId) {
            $this->assertStringContainsString($testId, $contents);
        }

        foreach ([
            "'branch_id' => \$selectedBranchId",
            "'date_from' => \$selectedDateFrom",
            "'date_to' => \$selectedDateTo",
        ] as $hiddenField) {
            $this->assertStringContainsString($hiddenField, $contents);
        }
    }

    public function test_cash_flow_dashboard_route_controller_and_view_are_wired_for_saved_views(): void
    {
        $this->assertTrue(Route::has('reports.cash-flow-dashboard.saved-views.store'));

        $controller = file_get_contents(app_path('Http/Controllers/CashFlowDashboardController.php'));
        $view = file_get_contents(resource_path('views/reports/cash-flow-dashboard.blade.php'));

        $this->assertStringContainsString('ReportSavedViewService', $controller);
        $this->assertStringContainsString('function storeSavedView', $controller);
        $this->assertStringContainsString('requestWithDefaultSavedView', $controller);
        $this->assertStringContainsString("'branch_id' =>", $controller);
        $this->assertStringContainsString("'date_from' =>", $controller);
        $this->assertStringContainsString("'date_to' =>", $controller);

        $this->assertStringContainsString("@include('reports.partials.cash-flow-dashboard-saved-view-controls-config')", $view);
        $this->assertStringContainsString('data-testid="cash-flow-dashboard-status"', $view);
        $this->assertStringContainsString('data-testid="cash-flow-dashboard-report-date"', $view);
    }

    public function test_cash_flow_dashboard_registry_contains_rollout_contract(): void
    {
        $this->assertTrue(ReportSavedViewRegistry::has('cash-flow-dashboard'));

        $report = ReportSavedViewRegistry::find('cash-flow-dashboard');

        $this->assertSame('cash-flow-dashboard', $report['key']);
        $this->assertSame('لوحة التدفق النقدي المتوقع', $report['label']);
        $this->assertSame('reports.cash-flow-dashboard.index', $report['index_route']);
        $this->assertSame('reports.cash-flow-dashboard.export', $report['export_route']);
        $this->assertSame('reports.cash-flow-dashboard.saved-views.store', $report['saved_view_store_route']);
        $this->assertSame('reports.partials.cash-flow-dashboard-saved-view-controls-config', $report['config_partial']);
        $this->assertSame('resources/views/reports/partials/cash-flow-dashboard-saved-view-controls-config.blade.php', $report['config_partial_path']);

        $this->assertSame([
            'branch_id',
            'date_from',
            'date_to',
        ], $report['hidden_fields']);

        $this->assertSame('cash-flow-dashboard-saved-views-selector', $report['test_ids']['section_card']);
        $this->assertSame('cash-flow-dashboard-save-view-form', $report['test_ids']['form']);
    }

    public function test_cash_flow_dashboard_renders_and_saves_saved_view_controls(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.cash-flow-dashboard.index'))
            ->assertOk()
            ->assertSee('data-testid="cash-flow-dashboard-saved-views-selector"', false)
            ->assertSee('data-testid="cash-flow-dashboard-saved-views-empty"', false)
            ->assertSee('data-testid="cash-flow-dashboard-save-view-card"', false)
            ->assertSee('data-testid="cash-flow-dashboard-save-view-form"', false)
            ->assertSee('data-testid="cash-flow-dashboard-saved-view-name-input"', false)
            ->assertSee('data-testid="cash-flow-dashboard-saved-view-default-checkbox"', false)
            ->assertSee('data-testid="cash-flow-dashboard-save-view-button"', false)
            ->assertSee('name="branch_id"', false)
            ->assertSee('name="date_from"', false)
            ->assertSee('name="date_to"', false);

        $this->actingAs($user)
            ->post(route('reports.cash-flow-dashboard.saved-views.store'), [
                'name' => 'تدفق نقدي تجريبي',
                'branch_id' => '',
                'date_from' => '2026-07-01',
                'date_to' => '2026-07-31',
                'is_default' => '1',
            ])
            ->assertRedirect(route('reports.cash-flow-dashboard.index', [
                'date_from' => '2026-07-01',
                'date_to' => '2026-07-31',
            ]));

        $this->assertDatabaseHas('report_saved_views', [
            'user_id' => $user->id,
            'report_key' => 'cash-flow-dashboard',
            'name' => 'تدفق نقدي تجريبي',
            'is_default' => true,
        ]);
    }
}
