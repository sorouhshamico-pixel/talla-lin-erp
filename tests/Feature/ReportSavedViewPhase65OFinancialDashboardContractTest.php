<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewPhase65OFinancialDashboardContractTest extends TestCase
{
    public function test_phase_65o_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-65o-financial-dashboard-saved-view-controls-contract.json'));
        $this->assertFileExists(base_path('docs/phase-65o-financial-dashboard-saved-view-controls-contract.md'));
    }

    public function test_phase_65o_contract_matches_phase_65n_locked_target(): void
    {
        $lock = json_decode(
            file_get_contents(base_path('docs/phase-65n-next-saved-view-rollout-target.json')),
            true
        );

        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65o-financial-dashboard-saved-view-controls-contract.json')),
            true
        );

        $this->assertSame('Phase 65O', $contract['phase']);
        $this->assertSame('financial-dashboard', $contract['target']['key']);
        $this->assertSame($lock['selected_target']['key'], $contract['target']['key']);
        $this->assertSame($lock['selected_target']['view_path'], $contract['target']['view_path']);
        $this->assertSame($lock['selected_target']['priority_score'], $contract['target']['priority_score']);
        $this->assertFalse($contract['target']['registered_at_lock_time']);
        $this->assertFalse($contract['target']['print_only_candidate']);
        $this->assertFalse($contract['target']['internal_tooling_candidate']);
        $this->assertFalse($contract['target']['navigation_hub_candidate']);
    }

    public function test_phase_65o_contract_documents_business_dashboard_current_state(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65o-financial-dashboard-saved-view-controls-contract.json')),
            true
        );

        $viewPath = str_replace('\\', '/', $contract['target']['view_path']);
        $controllerPath = str_replace('\\', '/', $contract['target']['controller_path']);

        $this->assertFileExists(base_path($viewPath));
        $this->assertFileExists(base_path($controllerPath));

        $view = file_get_contents(base_path($viewPath));
        $controller = file_get_contents(base_path($controllerPath));

        $this->assertStringContainsString('<title>الداشبورد المالية</title>', $view);
        $this->assertStringContainsString('financial-dashboard-current-month-revenues', $view);
        $this->assertStringContainsString('financial-dashboard-current-month-expenses', $view);
        $this->assertStringContainsString('financial-dashboard-current-month-net-profit', $view);
        $this->assertStringContainsString('financial-dashboard-uncollected-revenues', $view);
        $this->assertStringContainsString('financial-dashboard-unpaid-expenses', $view);
        $this->assertStringContainsString('public function __invoke(): View', $controller);

        $this->assertTrue($contract['current_state_evidence']['is_business_dashboard']);
        $this->assertTrue($contract['current_state_evidence']['controller_is_invokable']);
        $this->assertTrue($contract['current_state_evidence']['controller_uses_schema_guards']);
        $this->assertTrue($contract['current_state_evidence']['existing_index_route_in_routes_file']);
        $this->assertFalse($contract['current_state_evidence']['contains_interactive_form']);
        $this->assertFalse($contract['current_state_evidence']['contains_get_form']);
        $this->assertFalse($contract['current_state_evidence']['contains_saved_view_controls_include_or_inline_config']);
    }

    public function test_phase_65o_contract_defines_empty_filter_saved_view_rollout(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65o-financial-dashboard-saved-view-controls-contract.json')),
            true
        );

        $this->assertSame(
            'eligible_for_empty_filter_saved_view_controls_rollout',
            $contract['eligibility_decision']['status']
        );

        $this->assertSame('empty_filter_dashboard_saved_views', $contract['eligibility_decision']['rollout_mode']);
        $this->assertSame([], $contract['eligibility_decision']['hidden_fields']);
        $this->assertTrue($contract['eligibility_decision']['do_register_report_saved_view_key']);
        $this->assertTrue($contract['eligibility_decision']['do_create_saved_view_controls_config_partial']);
        $this->assertTrue($contract['eligibility_decision']['do_add_saved_view_store_route']);
        $this->assertTrue($contract['eligibility_decision']['do_add_json_export_route']);
    }

    public function test_phase_65o_contract_defines_route_and_registry_contracts(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65o-financial-dashboard-saved-view-controls-contract.json')),
            true
        );

        $this->assertTrue(Route::has($contract['route_contract']['existing_index_route']));

        $this->assertSame('reports.financial-dashboard', $contract['registry_contract']['index_route']);
        $this->assertSame('reports.financial-dashboard.json', $contract['registry_contract']['export_route']);
        $this->assertSame('reports.financial-dashboard.saved-views.store', $contract['registry_contract']['saved_view_store_route']);
        $this->assertSame([], $contract['registry_contract']['hidden_fields']);
        $this->assertSame(
            'resources/views/reports/partials/financial-dashboard-saved-view-controls-config.blade.php',
            $contract['registry_contract']['config_partial_path']
        );

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
            'financial-dashboard-status',
        ] as $testId) {
            $this->assertContains($testId, $contract['registry_contract']['test_ids']);
        }
    }

    public function test_phase_65o_contract_is_documented(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65o-financial-dashboard-saved-view-controls-contract.json')),
            true
        );

        $doc = file_get_contents(base_path('docs/phase-65o-financial-dashboard-saved-view-controls-contract.md'));

        $this->assertStringContainsString('Phase 65O', $doc);
        $this->assertStringContainsString('financial-dashboard', $doc);
        $this->assertStringContainsString($contract['target']['view_path'], $doc);
        $this->assertStringContainsString('empty-filter', $doc);
        $this->assertStringContainsString('Phase 65P rollout', $doc);
    }
}
