<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Reports\ReportSavedViewRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReceivablePayableAgingDashboardSavedViewControlsRolloutTest extends TestCase
{
    use RefreshDatabase;

    public function test_receivable_payable_aging_dashboard_saved_view_controls_config_partial_exists_and_uses_shared_controls(): void
    {
        $configPartial = resource_path('views/reports/partials/receivable-payable-aging-dashboard-saved-view-controls-config.blade.php');

        $this->assertFileExists($configPartial);

        $contents = file_get_contents($configPartial);

        $this->assertStringContainsString('$receivablePayableAgingDashboardSavedViewControlsConfig = [', $contents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-controls'", $contents);

        $this->assertStringContainsString("'routeName' => 'reports.receivable-payable-aging-dashboard.index'", $contents);
        $this->assertStringContainsString("'storeRouteName' => 'reports.receivable-payable-aging-dashboard.saved-views.store'", $contents);

        foreach ([
            'receivable-payable-aging-dashboard-saved-views-selector',
            'receivable-payable-aging-dashboard-saved-views-empty',
            'receivable-payable-aging-dashboard-save-view-card',
            'receivable-payable-aging-dashboard-save-view-form',
            'receivable-payable-aging-dashboard-saved-view-name-input',
            'receivable-payable-aging-dashboard-saved-view-default-checkbox',
            'receivable-payable-aging-dashboard-save-view-button',
            'receivable-payable-aging-dashboard-saved-views-list',
            'receivable-payable-aging-dashboard-saved-view-item',
            'receivable-payable-aging-dashboard-saved-view-open-link',
            'receivable-payable-aging-dashboard-saved-view-active-badge',
            'receivable-payable-aging-dashboard-saved-view-default-badge',
        ] as $testId) {
            $this->assertStringContainsString($testId, $contents);
        }

        foreach ([
            "'branch_id' => \$selectedBranchId ?? null",
            "'as_of_date' => \$selectedAsOfDate ?? null",
        ] as $hiddenField) {
            $this->assertStringContainsString($hiddenField, $contents);
        }
    }

    public function test_receivable_payable_aging_dashboard_route_controller_and_view_are_wired_for_saved_views(): void
    {
        $this->assertTrue(Route::has('reports.receivable-payable-aging-dashboard.saved-views.store'));

        $controller = file_get_contents(app_path('Http/Controllers/ReceivablePayableAgingDashboardController.php'));
        $view = file_get_contents(resource_path('views/reports/receivable-payable-aging-dashboard.blade.php'));

        $this->assertStringContainsString("private const REPORT_KEY = 'receivable-payable-aging-dashboard';", $controller);
        $this->assertStringContainsString('ReportFilterPreferenceService', $controller);
        $this->assertStringContainsString('ReportSavedViewService', $controller);
        $this->assertStringContainsString('function storeSavedView', $controller);
        $this->assertStringContainsString('requestWithDefaultSavedView', $controller);
        $this->assertStringContainsString('function index', $controller);
        $this->assertStringContainsString('function print', $controller);
        $this->assertStringContainsString('function export', $controller);

        foreach ([
            'branch_id',
            'as_of_date',
        ] as $field) {
            $this->assertStringContainsString("'{$field}'", $controller);
        }

        $this->assertStringContainsString("@include('reports.partials.receivable-payable-aging-dashboard-saved-view-controls-config')", $view);
        $this->assertStringContainsString('data-testid="receivable-payable-aging-dashboard-status"', $view);
        $this->assertStringContainsString('data-testid="receivable-payable-aging-dashboard"', $view);
        $this->assertStringContainsString('data-testid="aging-dashboard-export-link"', $view);
        $this->assertStringContainsString('data-testid="aging-dashboard-print-link"', $view);
    }

    public function test_receivable_payable_aging_dashboard_registry_contains_rollout_contract(): void
    {
        $this->assertTrue(ReportSavedViewRegistry::has('receivable-payable-aging-dashboard'));

        $report = ReportSavedViewRegistry::find('receivable-payable-aging-dashboard');

        $this->assertSame('receivable-payable-aging-dashboard', $report['key']);
        $this->assertSame('لوحة أعمار الذمم', $report['label']);
        $this->assertSame('reports.receivable-payable-aging-dashboard.index', $report['index_route']);
        $this->assertSame('reports.receivable-payable-aging-dashboard.export', $report['export_route']);
        $this->assertSame('reports.receivable-payable-aging-dashboard.saved-views.store', $report['saved_view_store_route']);
        $this->assertSame('reports.partials.receivable-payable-aging-dashboard-saved-view-controls-config', $report['config_partial']);
        $this->assertSame('resources/views/reports/partials/receivable-payable-aging-dashboard-saved-view-controls-config.blade.php', $report['config_partial_path']);

        $this->assertSame([
            'branch_id',
            'as_of_date',
        ], $report['hidden_fields']);

        $this->assertSame('receivable-payable-aging-dashboard-saved-views-selector', $report['test_ids']['section_card']);
        $this->assertSame('receivable-payable-aging-dashboard-save-view-form', $report['test_ids']['form']);
    }

    public function test_receivable_payable_aging_dashboard_renders_and_saves_saved_view_controls(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.receivable-payable-aging-dashboard.index'))
            ->assertOk()
            ->assertSee('data-testid="receivable-payable-aging-dashboard-saved-views-selector"', false)
            ->assertSee('data-testid="receivable-payable-aging-dashboard-saved-views-empty"', false)
            ->assertSee('data-testid="receivable-payable-aging-dashboard-save-view-card"', false)
            ->assertSee('data-testid="receivable-payable-aging-dashboard-save-view-form"', false)
            ->assertSee('data-testid="receivable-payable-aging-dashboard-saved-view-name-input"', false)
            ->assertSee('data-testid="receivable-payable-aging-dashboard-saved-view-default-checkbox"', false)
            ->assertSee('data-testid="receivable-payable-aging-dashboard-save-view-button"', false)
            ->assertSee('name="branch_id"', false)
            ->assertSee('name="as_of_date"', false);

        $this->actingAs($user)
            ->post(route('reports.receivable-payable-aging-dashboard.saved-views.store'), [
                'name' => 'أعمار الذمم التجريبي',
                'as_of_date' => '2026-07-31',
                'is_default' => '1',
            ])
            ->assertRedirect(route('reports.receivable-payable-aging-dashboard.index', [
                'as_of_date' => '2026-07-31',
            ]));

        $this->assertDatabaseHas('report_saved_views', [
            'user_id' => $user->id,
            'report_key' => 'receivable-payable-aging-dashboard',
            'name' => 'أعمار الذمم التجريبي',
            'is_default' => true,
        ]);
    }
}
