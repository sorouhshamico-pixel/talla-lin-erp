<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase65DCashFlowDashboardPrintEligibilityContractTest extends TestCase
{
    public function test_phase_65d_eligibility_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-65d-cash-flow-dashboard-print-saved-view-eligibility-contract.json'));
        $this->assertFileExists(base_path('docs/phase-65d-cash-flow-dashboard-print-saved-view-eligibility-contract.md'));
    }

    public function test_phase_65d_contract_matches_phase_65c_locked_target(): void
    {
        $lock = json_decode(
            file_get_contents(base_path('docs/phase-65c-next-saved-view-rollout-target.json')),
            true
        );

        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65d-cash-flow-dashboard-print-saved-view-eligibility-contract.json')),
            true
        );

        $this->assertSame('Phase 65D', $contract['phase']);
        $this->assertSame('cash-flow-dashboard-print', $contract['target']['key']);
        $this->assertSame($lock['selected_target']['key'], $contract['target']['key']);
        $this->assertSame($lock['selected_target']['view_path'], $contract['target']['view_path']);
        $this->assertSame($lock['selected_target']['priority_score'], $contract['target']['priority_score']);
        $this->assertFalse($contract['target']['registered_at_lock_time']);
    }

    public function test_phase_65d_target_is_documented_as_print_only_and_not_eligible(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65d-cash-flow-dashboard-print-saved-view-eligibility-contract.json')),
            true
        );

        $targetViewPath = str_replace('\\', '/', $contract['target']['view_path']);

        $this->assertFileExists(base_path($targetViewPath));

        $view = file_get_contents(base_path($targetViewPath));

        $this->assertStringContainsString('<!DOCTYPE html>', $view);
        $this->assertStringContainsString('onclick="window.print()"', $view);
        $this->assertStringContainsString('data-testid="cash-flow-print-button"', $view);
        $this->assertStringContainsString('data-testid="cash-flow-print-filter-context"', $view);

        $this->assertFalse($contract['current_state_evidence']['contains_interactive_form']);
        $this->assertFalse($contract['current_state_evidence']['contains_get_form']);
        $this->assertFalse($contract['current_state_evidence']['contains_saved_view_controls_include_or_inline_config']);
        $this->assertTrue($contract['current_state_evidence']['is_standalone_print_document']);

        $this->assertSame(
            'not_eligible_for_saved_view_controls_rollout',
            $contract['eligibility_decision']['status']
        );

        $this->assertTrue($contract['eligibility_decision']['do_not_register_report_saved_view_key']);
        $this->assertTrue($contract['eligibility_decision']['do_not_create_saved_view_controls_config_partial']);
        $this->assertTrue($contract['eligibility_decision']['do_not_add_store_route']);
        $this->assertTrue($contract['eligibility_decision']['do_not_modify_print_view']);
    }

    public function test_phase_65d_contract_proposes_print_view_exclusion_rule(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65d-cash-flow-dashboard-print-saved-view-eligibility-contract.json')),
            true
        );

        $this->assertStringContainsString('-print', $contract['proposed_exclusion']['exclusion_rule']);
        $this->assertStringContainsString('-print.blade.php', $contract['proposed_exclusion']['exclusion_rule']);
        $this->assertSame('selector/prioritization only', $contract['proposed_exclusion']['scope']);
    }

    public function test_phase_65d_contract_is_documented(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65d-cash-flow-dashboard-print-saved-view-eligibility-contract.json')),
            true
        );

        $doc = file_get_contents(base_path('docs/phase-65d-cash-flow-dashboard-print-saved-view-eligibility-contract.md'));

        $this->assertStringContainsString('Phase 65D', $doc);
        $this->assertStringContainsString('cash-flow-dashboard-print', $doc);
        $this->assertStringContainsString($contract['target']['view_path'], $doc);
        $this->assertStringContainsString('not eligible', $doc);
        $this->assertStringContainsString('Phase 65E', $doc);
    }
}
