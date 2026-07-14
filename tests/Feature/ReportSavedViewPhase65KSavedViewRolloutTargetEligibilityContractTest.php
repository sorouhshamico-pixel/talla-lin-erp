<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase65KSavedViewRolloutTargetEligibilityContractTest extends TestCase
{
    public function test_phase_65k_eligibility_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-65k-saved-view-rollout-target-eligibility-contract.json'));
        $this->assertFileExists(base_path('docs/phase-65k-saved-view-rollout-target-eligibility-contract.md'));
    }

    public function test_phase_65k_contract_matches_phase_65j_locked_target(): void
    {
        $lock = json_decode(
            file_get_contents(base_path('docs/phase-65j-next-saved-view-rollout-target.json')),
            true
        );

        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65k-saved-view-rollout-target-eligibility-contract.json')),
            true
        );

        $this->assertSame('Phase 65K', $contract['phase']);
        $this->assertSame('saved-view-rollout-target', $contract['target']['key']);
        $this->assertSame($lock['selected_target']['key'], $contract['target']['key']);
        $this->assertSame($lock['selected_target']['view_path'], $contract['target']['view_path']);
        $this->assertSame($lock['selected_target']['priority_score'], $contract['target']['priority_score']);
        $this->assertFalse($contract['target']['registered_at_lock_time']);
        $this->assertFalse($contract['target']['print_only_candidate']);
        $this->assertFalse($contract['target']['internal_tooling_candidate']);
    }

    public function test_phase_65k_target_is_documented_as_tooling_and_not_eligible(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65k-saved-view-rollout-target-eligibility-contract.json')),
            true
        );

        $targetViewPath = str_replace('\\', '/', $contract['target']['view_path']);

        $this->assertFileExists(base_path($targetViewPath));

        $view = file_get_contents(base_path($targetViewPath));

        $this->assertStringContainsString('Report Saved View Rollout Target', $view);
        $this->assertStringContainsString('Locked Target', $view);
        $this->assertStringContainsString('Candidate Filter Fields', $view);
        $this->assertStringContainsString('Route Names', $view);
        $this->assertStringContainsString('Includes', $view);

        $this->assertTrue($contract['current_state_evidence']['is_tooling_surface']);
        $this->assertTrue($contract['current_state_evidence']['contains_locked_target_panel']);
        $this->assertTrue($contract['current_state_evidence']['contains_candidate_filter_fields_panel']);
        $this->assertTrue($contract['current_state_evidence']['contains_route_names_panel']);
        $this->assertTrue($contract['current_state_evidence']['contains_includes_panel']);

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

    public function test_phase_65k_contract_proposes_tooling_exclusion_rule(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65k-saved-view-rollout-target-eligibility-contract.json')),
            true
        );

        $this->assertSame('selector/prioritization only', $contract['proposed_exclusion']['scope']);
        $this->assertContains('saved-view-rollout-target', $contract['proposed_exclusion']['candidate_keys_to_exclude']);
        $this->assertStringContainsString('saved-view-rollout-target', $contract['proposed_exclusion']['exclusion_rule']);
    }

    public function test_phase_65k_contract_is_documented(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65k-saved-view-rollout-target-eligibility-contract.json')),
            true
        );

        $doc = file_get_contents(base_path('docs/phase-65k-saved-view-rollout-target-eligibility-contract.md'));

        $this->assertStringContainsString('Phase 65K', $doc);
        $this->assertStringContainsString('saved-view-rollout-target', $doc);
        $this->assertStringContainsString($contract['target']['view_path'], $doc);
        $this->assertStringContainsString('not eligible', $doc);
        $this->assertStringContainsString('Phase 65L', $doc);
    }
}
