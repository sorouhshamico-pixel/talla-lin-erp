<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewCandidateScanner;
use App\Support\Reports\ReportSavedViewRegistry;
use App\Support\Reports\ReportSavedViewRegistryDiagnosticReport;
use App\Support\Reports\ReportSavedViewRegistryValidator;
use App\Support\Reports\ReportSavedViewRolloutSelector;
use Tests\TestCase;

class ReportSavedViewPhase65QCompletionStateTest extends TestCase
{
    public function test_phase_65q_completion_documents_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-65q-saved-view-rollout-completion-state.json'));
        $this->assertFileExists(base_path('docs/phase-65q-saved-view-rollout-completion-state.md'));
    }

    public function test_saved_view_rollout_selector_is_exhausted_after_financial_dashboard_rollout(): void
    {
        $plan = ReportSavedViewRolloutSelector::plan();

        $this->assertFalse($plan['has_next_candidate']);
        $this->assertNull($plan['next_candidate']);
        $this->assertSame([], ReportSavedViewRolloutSelector::prioritizedCandidates());
        $this->assertSame(0, $plan['unregistered_candidate_count']);
    }

    public function test_phase_65q_completion_json_matches_current_healthy_state(): void
    {
        $completion = json_decode(
            file_get_contents(base_path('docs/phase-65q-saved-view-rollout-completion-state.json')),
            true
        );

        $this->assertSame('Phase 65Q', $completion['phase']);
        $this->assertSame('Phase 65P clean', $completion['baseline']['phase']);
        $this->assertSame('6e46087', $completion['baseline']['commit']);
        $this->assertSame('1210 passed / 10704 assertions', $completion['baseline']['tests']);

        $this->assertTrue($completion['completion_status']['rollout_selector_exhausted']);
        $this->assertFalse($completion['completion_status']['has_next_candidate']);
        $this->assertNull($completion['completion_status']['next_candidate']);
        $this->assertSame(0, $completion['completion_status']['prioritized_candidate_count']);
        $this->assertSame(ReportSavedViewRegistry::count(), $completion['completion_status']['registered_report_count']);
        $this->assertSame(0, $completion['completion_status']['registry_invalid_count']);
        $this->assertSame(0, $completion['completion_status']['diagnostic_invalid_count']);
        $this->assertTrue($completion['completion_status']['registry_valid']);
        $this->assertTrue($completion['completion_status']['diagnostics_valid']);
    }

    public function test_financial_dashboard_is_registered_and_has_saved_view_controls(): void
    {
        $this->assertTrue(ReportSavedViewRegistry::has('financial-dashboard'));

        $report = ReportSavedViewRegistry::find('financial-dashboard');

        $this->assertSame('financial-dashboard', $report['key']);
        $this->assertSame('الداشبورد المالية', $report['label']);
        $this->assertSame('reports.financial-dashboard', $report['index_route']);
        $this->assertSame('reports.financial-dashboard.json', $report['export_route']);
        $this->assertSame('reports.financial-dashboard.saved-views.store', $report['saved_view_store_route']);
        $this->assertSame([], $report['hidden_fields']);

        $candidate = collect(ReportSavedViewCandidateScanner::candidates())
            ->firstWhere('key', 'financial-dashboard');

        $this->assertNotNull($candidate);
        $this->assertTrue($candidate['registered']);
        $this->assertTrue($candidate['has_saved_view_controls']);
    }

    public function test_registry_and_diagnostics_remain_healthy_after_completion(): void
    {
        $registrySummary = ReportSavedViewRegistryValidator::summary();
        $diagnosticSummary = ReportSavedViewRegistryDiagnosticReport::summary();

        $this->assertSame(13, $registrySummary['report_count']);
        $this->assertSame(0, $registrySummary['invalid_count']);
        $this->assertTrue($registrySummary['valid']);

        $this->assertSame(13, $diagnosticSummary['report_count']);
        $this->assertSame(0, $diagnosticSummary['invalid_count']);
        $this->assertTrue($diagnosticSummary['valid']);
    }

    public function test_completion_markdown_is_documented(): void
    {
        $doc = file_get_contents(base_path('docs/phase-65q-saved-view-rollout-completion-state.md'));

        $this->assertStringContainsString('Phase 65Q', $doc);
        $this->assertStringContainsString('6e46087', $doc);
        $this->assertStringContainsString('1210 passed / 10704 assertions', $doc);
        $this->assertStringContainsString('Rollout selector exhausted: yes', $doc);
        $this->assertStringContainsString('financial-dashboard', $doc);
    }
}
