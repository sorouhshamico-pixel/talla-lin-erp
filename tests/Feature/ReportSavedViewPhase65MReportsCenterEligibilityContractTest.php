<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase65MReportsCenterEligibilityContractTest extends TestCase
{
    public function test_phase_65m_eligibility_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-65m-reports-center-eligibility-contract.json'));
        $this->assertFileExists(base_path('docs/phase-65m-reports-center-eligibility-contract.md'));
    }

    public function test_phase_65m_contract_matches_phase_65l_locked_target(): void
    {
        $lock = json_decode(
            file_get_contents(base_path('docs/phase-65l-next-saved-view-rollout-target.json')),
            true
        );

        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65m-reports-center-eligibility-contract.json')),
            true
        );

        $this->assertSame('Phase 65M', $contract['phase']);
        $this->assertSame('center', $contract['target']['key']);
        $this->assertSame($lock['selected_target']['key'], $contract['target']['key']);
        $this->assertSame($lock['selected_target']['view_path'], $contract['target']['view_path']);
        $this->assertSame($lock['selected_target']['priority_score'], $contract['target']['priority_score']);
        $this->assertFalse($contract['target']['registered_at_lock_time']);
        $this->assertFalse($contract['target']['print_only_candidate']);
        $this->assertFalse($contract['target']['internal_tooling_candidate']);
    }

    public function test_phase_65m_target_is_documented_as_navigation_hub_and_not_eligible(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65m-reports-center-eligibility-contract.json')),
            true
        );

        $targetViewPath = str_replace('\\', '/', $contract['target']['view_path']);

        $this->assertFileExists(base_path($targetViewPath));

        $view = file_get_contents(base_path($targetViewPath));

        $this->assertStringContainsString('<title>مركز التقارير</title>', $view);
        $this->assertStringContainsString('صفحة مركزية للوصول السريع إلى التقارير المالية ولوحات المتابعة', $view);
        $this->assertStringContainsString('reports-center-financial-dashboard-card', $view);
        $this->assertStringContainsString('reports-center-profit-loss-card', $view);
        $this->assertStringContainsString('reports-center-profit-loss-export-card', $view);

        $this->assertTrue($contract['current_state_evidence']['is_navigation_hub']);
        $this->assertTrue($contract['current_state_evidence']['contains_reports_center_title']);
        $this->assertTrue($contract['current_state_evidence']['contains_financial_dashboard_card']);
        $this->assertTrue($contract['current_state_evidence']['contains_profit_loss_card']);
        $this->assertTrue($contract['current_state_evidence']['contains_profit_loss_export_card']);

        $this->assertFalse($contract['current_state_evidence']['contains_interactive_form']);
        $this->assertFalse($contract['current_state_evidence']['contains_get_form']);
        $this->assertFalse($contract['current_state_evidence']['contains_saved_view_controls_include_or_inline_config']);

        $this->assertContains('reports.financial-dashboard', $contract['current_state_evidence']['linked_route_names']);
        $this->assertContains('reports.profit-loss', $contract['current_state_evidence']['linked_route_names']);
        $this->assertContains('reports.profit-loss.export', $contract['current_state_evidence']['linked_route_names']);

        $this->assertSame(
            'not_eligible_for_saved_view_controls_rollout',
            $contract['eligibility_decision']['status']
        );

        $this->assertTrue($contract['eligibility_decision']['do_not_register_report_saved_view_key']);
        $this->assertTrue($contract['eligibility_decision']['do_not_create_saved_view_controls_config_partial']);
        $this->assertTrue($contract['eligibility_decision']['do_not_add_store_route']);
        $this->assertTrue($contract['eligibility_decision']['do_not_modify_navigation_view']);
    }

    public function test_phase_65m_contract_proposes_navigation_hub_exclusion_rule(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65m-reports-center-eligibility-contract.json')),
            true
        );

        $this->assertSame('selector/prioritization only', $contract['proposed_exclusion']['scope']);
        $this->assertContains('center', $contract['proposed_exclusion']['candidate_keys_to_exclude']);
        $this->assertStringContainsString('center', $contract['proposed_exclusion']['exclusion_rule']);
    }

    public function test_phase_65m_contract_is_documented(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65m-reports-center-eligibility-contract.json')),
            true
        );

        $doc = file_get_contents(base_path('docs/phase-65m-reports-center-eligibility-contract.md'));

        $this->assertStringContainsString('Phase 65M', $doc);
        $this->assertStringContainsString('center', $doc);
        $this->assertStringContainsString($contract['target']['view_path'], $doc);
        $this->assertStringContainsString('not eligible', $doc);
        $this->assertStringContainsString('Phase 65N', $doc);
    }
}
