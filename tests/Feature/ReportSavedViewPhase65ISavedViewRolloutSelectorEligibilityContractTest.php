<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase65ISavedViewRolloutSelectorEligibilityContractTest extends TestCase
{
    public function test_phase_65i_eligibility_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-65i-saved-view-rollout-selector-eligibility-contract.json'));
        $this->assertFileExists(base_path('docs/phase-65i-saved-view-rollout-selector-eligibility-contract.md'));
    }

    public function test_phase_65i_contract_matches_phase_65h_locked_target(): void
    {
        $lock = json_decode(
            file_get_contents(base_path('docs/phase-65h-next-saved-view-rollout-target.json')),
            true
        );

        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65i-saved-view-rollout-selector-eligibility-contract.json')),
            true
        );

        $this->assertSame('Phase 65I', $contract['phase']);
        $this->assertSame('saved-view-rollout-selector', $contract['target']['key']);
        $this->assertSame($lock['selected_target']['key'], $contract['target']['key']);
        $this->assertSame($lock['selected_target']['view_path'], $contract['target']['view_path']);
        $this->assertSame($lock['selected_target']['priority_score'], $contract['target']['priority_score']);
        $this->assertFalse($contract['target']['registered_at_lock_time']);
        $this->assertFalse($contract['target']['print_only_candidate']);
    }

    public function test_phase_65i_target_is_documented_as_tooling_and_not_eligible(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65i-saved-view-rollout-selector-eligibility-contract.json')),
            true
        );

        $targetViewPath = str_replace('\\', '/', $contract['target']['view_path']);

        $this->assertFileExists(base_path($targetViewPath));

        $view = file_get_contents(base_path($targetViewPath));

        $this->assertStringContainsString('Report Saved View Rollout Selector', $view);
        $this->assertStringContainsString('Rollout Workflow', $view);
        $this->assertStringContainsString('CLI Commands', $view);
        $this->assertStringContainsString('Next Candidate', $view);
        $this->assertStringContainsString('Prioritized Candidates', $view);

        $this->assertTrue($contract['current_state_evidence']['is_tooling_surface']);
        $this->assertTrue($contract['current_state_evidence']['contains_rollout_workflow']);
        $this->assertTrue($contract['current_state_evidence']['contains_cli_commands']);
        $this->assertTrue($contract['current_state_evidence']['contains_next_candidate_panel']);
        $this->assertTrue($contract['current_state_evidence']['contains_prioritized_candidates_table']);

        $this->assertFalse($contract['current_state_evidence']['contains_interactive_form']);
        $this->assertFalse($contract['current_state_evidence']['contains_get_form']);
        $this->assertFalse($contract['current_state_evidence']['contains_saved_view_controls_include_or_inline_config']);

        $this->assertSame(
            'not_eligible_for_saved_view_controls_rollout',
            $contract['eligibility_decision']['status']
        );

        $this->assertTrue($contract['eligibility_decision']['do_not_register_report_saved_view_key']);
        $this->assertTrue($contract['eligibility_decision']['do_not_create_saved_view_controls_config_partial']);
        $this->assertTrue($contract['eligibility_decision']['do_not_add_store_route']);
        $this->assertTrue($contract['eligibility_decision']['do_not_modify_tooling_view']);
    }

    public function test_phase_65i_contract_proposes_tooling_exclusion_rule(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65i-saved-view-rollout-selector-eligibility-contract.json')),
            true
        );

        $this->assertSame('selector/prioritization only', $contract['proposed_exclusion']['scope']);
        $this->assertContains('saved-view-rollout-selector', $contract['proposed_exclusion']['candidate_keys_to_exclude']);
        $this->assertStringContainsString('saved-view-rollout-selector', $contract['proposed_exclusion']['exclusion_rule']);
    }

    public function test_phase_65i_contract_is_documented(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65i-saved-view-rollout-selector-eligibility-contract.json')),
            true
        );

        $doc = file_get_contents(base_path('docs/phase-65i-saved-view-rollout-selector-eligibility-contract.md'));

        $this->assertStringContainsString('Phase 65I', $doc);
        $this->assertStringContainsString('saved-view-rollout-selector', $doc);
        $this->assertStringContainsString($contract['target']['view_path'], $doc);
        $this->assertStringContainsString('not eligible', $doc);
        $this->assertStringContainsString('Phase 65J', $doc);
    }
}
