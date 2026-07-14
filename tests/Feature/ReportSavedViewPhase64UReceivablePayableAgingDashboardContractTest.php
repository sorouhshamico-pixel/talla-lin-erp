<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewPhase64UReceivablePayableAgingDashboardContractTest extends TestCase
{
    public function test_phase_64u_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-64u-receivable-payable-aging-dashboard-saved-view-controls-contract.json'));
        $this->assertFileExists(base_path('docs/phase-64u-receivable-payable-aging-dashboard-saved-view-controls-contract.md'));
    }

    public function test_phase_64u_contract_matches_phase_64t_locked_target(): void
    {
        $lock = json_decode(
            file_get_contents(base_path('docs/phase-64t-next-saved-view-rollout-target.json')),
            true
        );

        $contract = json_decode(
            file_get_contents(base_path('docs/phase-64u-receivable-payable-aging-dashboard-saved-view-controls-contract.json')),
            true
        );

        $this->assertSame('Phase 64U', $contract['phase']);
        $this->assertSame('receivable-payable-aging-dashboard', $contract['target']['key']);
        $this->assertSame($lock['selected_target']['key'], $contract['target']['key']);
        $this->assertSame($lock['selected_target']['view_path'], $contract['target']['view_path']);
        $this->assertSame($lock['selected_target']['priority_score'], $contract['target']['priority_score']);
        $this->assertFalse($contract['target']['registered_at_lock_time']);
        $this->assertFalse($contract['target']['has_saved_view_controls_at_lock_time']);
    }

    public function test_phase_64u_contract_target_view_controller_and_routes_exist(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-64u-receivable-payable-aging-dashboard-saved-view-controls-contract.json')),
            true
        );

        $targetViewPath = str_replace('\\', '/', $contract['target']['view_path']);
        $controllerPath = str_replace('\\', '/', $contract['target']['controller_path']);

        $this->assertFileExists(base_path($targetViewPath));
        $this->assertFileExists(base_path($controllerPath));

        $this->assertTrue(Route::has('reports.receivable-payable-aging-dashboard.index'));
        $this->assertTrue(Route::has('reports.receivable-payable-aging-dashboard.export'));
        $this->assertTrue(Route::has('reports.receivable-payable-aging-dashboard.print'));

        $this->assertTrue($contract['current_state_evidence']['contains_get_form']);
        $this->assertTrue($contract['current_state_evidence']['contains_filter_terms']);
        $this->assertFalse($contract['current_state_evidence']['contains_saved_view_controls']);
        $this->assertFalse($contract['current_state_evidence']['contains_report_specific_config_partial']);
        $this->assertTrue($contract['current_state_evidence']['controller_has_report_key_constant']);
        $this->assertTrue($contract['current_state_evidence']['controller_has_filter_keys_constant']);
        $this->assertTrue($contract['current_state_evidence']['controller_uses_filter_preference_service']);
        $this->assertFalse($contract['current_state_evidence']['controller_uses_report_saved_view_service']);
        $this->assertFalse($contract['current_state_evidence']['controller_has_store_saved_view_method']);
        $this->assertTrue($contract['current_state_evidence']['controller_has_index_method']);
        $this->assertTrue($contract['current_state_evidence']['controller_has_print_method']);
        $this->assertTrue($contract['current_state_evidence']['controller_has_export_method']);
        $this->assertTrue($contract['current_state_evidence']['routes_have_index_route']);
        $this->assertTrue($contract['current_state_evidence']['routes_have_export_route']);
        $this->assertTrue($contract['current_state_evidence']['routes_have_print_route']);
        $this->assertFalse($contract['current_state_evidence']['routes_have_saved_view_store_route']);
    }

    public function test_phase_64u_contract_uses_saved_view_controls_conventions(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-64u-receivable-payable-aging-dashboard-saved-view-controls-contract.json')),
            true
        );

        $registryKey = $contract['contract']['registry_key'];

        $this->assertSame('receivable-payable-aging-dashboard', $registryKey);

        $this->assertSame(
            'resources/views/reports/partials/' . $registryKey . '-saved-view-controls-config.blade.php',
            str_replace('\\', '/', $contract['contract']['config_partial_path'])
        );

        $this->assertSame(
            'reports.partials.' . $registryKey . '-saved-view-controls-config',
            $contract['contract']['config_partial']
        );

        $this->assertSame('reports.partials.saved-view-controls', $contract['contract']['shared_controls_partial']);
        $this->assertStringContainsString($contract['contract']['config_partial'], $contract['contract']['view_include']);
    }

    public function test_phase_64u_contract_documents_routes_hidden_fields_and_test_ids(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-64u-receivable-payable-aging-dashboard-saved-view-controls-contract.json')),
            true
        );

        $this->assertSame('reports.receivable-payable-aging-dashboard.index', $contract['contract']['index_route']);
        $this->assertSame('reports.receivable-payable-aging-dashboard.export', $contract['contract']['export_route']);
        $this->assertSame('reports.receivable-payable-aging-dashboard.print', $contract['contract']['print_route']);
        $this->assertSame('reports.receivable-payable-aging-dashboard.saved-views.store', $contract['contract']['saved_view_store_route']);

        $this->assertSame([
            'branch_id',
            'as_of_date',
        ], $contract['contract']['candidate_hidden_fields']);

        foreach ([
            'section_card',
            'empty',
            'form_card',
            'form',
            'name_input',
            'default_checkbox',
            'save_button',
            'list',
            'item',
            'open_link',
            'active_badge',
            'default_badge',
        ] as $testIdKey) {
            $this->assertArrayHasKey($testIdKey, $contract['contract']['test_ids']);
            $this->assertNotEmpty($contract['contract']['test_ids'][$testIdKey]);
        }
    }

    public function test_phase_64u_contract_is_documented(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-64u-receivable-payable-aging-dashboard-saved-view-controls-contract.json')),
            true
        );

        $doc = file_get_contents(base_path('docs/phase-64u-receivable-payable-aging-dashboard-saved-view-controls-contract.md'));

        $this->assertStringContainsString('Phase 64U', $doc);
        $this->assertStringContainsString('receivable-payable-aging-dashboard', $doc);
        $this->assertStringContainsString($contract['target']['view_path'], $doc);
        $this->assertStringContainsString($contract['target']['controller_path'], $doc);
        $this->assertStringContainsString($contract['contract']['index_route'], $doc);
        $this->assertStringContainsString($contract['contract']['export_route'], $doc);
        $this->assertStringContainsString($contract['contract']['print_route'], $doc);
        $this->assertStringContainsString($contract['contract']['saved_view_store_route'], $doc);
        $this->assertStringContainsString('ReportSavedViewPhase64UReceivablePayableAgingDashboardContractTest', $doc);
    }
}
