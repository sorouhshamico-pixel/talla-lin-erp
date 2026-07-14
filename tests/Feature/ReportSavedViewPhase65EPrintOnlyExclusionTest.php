<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewRolloutSelector;
use Tests\TestCase;

class ReportSavedViewPhase65EPrintOnlyExclusionTest extends TestCase
{
    public function test_phase_65e_print_only_exclusion_docs_and_lock_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-65e-saved-view-rollout-selector-snapshot-after-print-exclusion.json'));
        $this->assertFileExists(base_path('docs/phase-65e-next-saved-view-rollout-target.json'));
        $this->assertFileExists(base_path('docs/phase-65e-next-saved-view-rollout-target.md'));
    }

    public function test_rollout_selector_excludes_print_only_candidates_from_prioritized_candidates(): void
    {
        $excluded = ReportSavedViewRolloutSelector::excludedPrintCandidates();

        $this->assertNotEmpty($excluded);

        $excludedKeys = array_column($excluded, 'key');

        $this->assertContains('cash-flow-dashboard-print', $excludedKeys);

        foreach ($excluded as $candidate) {
            $this->assertTrue(ReportSavedViewRolloutSelector::isPrintOnlyCandidate($candidate));
        }

        foreach (ReportSavedViewRolloutSelector::prioritizedCandidates() as $candidate) {
            $this->assertFalse(
                ReportSavedViewRolloutSelector::isPrintOnlyCandidate($candidate),
                "{$candidate['key']} should not remain in prioritized rollout candidates."
            );
        }
    }

    public function test_phase_65e_plan_tracks_excluded_print_candidates_and_next_target_is_non_print(): void
    {
        $plan = ReportSavedViewRolloutSelector::plan();

        $this->assertArrayHasKey('excluded_print_candidate_count', $plan);
        $this->assertArrayHasKey('excluded_print_candidates', $plan);
        $this->assertGreaterThanOrEqual(1, $plan['excluded_print_candidate_count']);
        $this->assertSame(count($plan['excluded_print_candidates']), $plan['excluded_print_candidate_count']);

        $this->assertTrue($plan['has_next_candidate']);
        $this->assertIsArray($plan['next_candidate']);
        $this->assertFalse(ReportSavedViewRolloutSelector::isPrintOnlyCandidate($plan['next_candidate']));
    }

    public function test_phase_65e_lock_matches_selector_snapshot(): void
    {
        $snapshot = json_decode(
            file_get_contents(base_path('docs/phase-65e-saved-view-rollout-selector-snapshot-after-print-exclusion.json')),
            true
        );

        $lock = json_decode(
            file_get_contents(base_path('docs/phase-65e-next-saved-view-rollout-target.json')),
            true
        );

        $this->assertSame('Phase 65E', $snapshot['phase']);
        $this->assertSame('Phase 65E', $lock['phase']);
        $this->assertSame('Phase 65D clean', $lock['baseline']['phase']);
        $this->assertSame('7817b74', $lock['baseline']['commit']);
        $this->assertSame('1150 passed / 10020 assertions', $lock['baseline']['tests']);

        $nextCandidate = $snapshot['selector_plan']['next_candidate'];

        $this->assertSame($nextCandidate['key'], $lock['selected_target']['key']);
        $this->assertSame($nextCandidate['view_path'], $lock['selected_target']['view_path']);
        $this->assertFalse($lock['selected_target']['registered_at_lock_time']);
        $this->assertFalse($lock['selected_target']['print_only_candidate']);
    }

    public function test_phase_65e_lock_is_documented(): void
    {
        $lock = json_decode(
            file_get_contents(base_path('docs/phase-65e-next-saved-view-rollout-target.json')),
            true
        );

        $doc = file_get_contents(base_path('docs/phase-65e-next-saved-view-rollout-target.md'));

        $this->assertStringContainsString('Phase 65E', $doc);
        $this->assertStringContainsString('7817b74', $doc);
        $this->assertStringContainsString('1150 passed / 10020 assertions', $doc);
        $this->assertStringContainsString('Print-only candidates are excluded', $doc);
        $this->assertStringContainsString('cash-flow-dashboard-print', $doc);
        $this->assertStringContainsString($lock['selected_target']['key'], $doc);
        $this->assertStringContainsString($lock['proposed_contract_seed']['config_partial_path'], $doc);
    }
}
