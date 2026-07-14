<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewRolloutSelector;
use Tests\TestCase;

class ReportSavedViewPhase65LRolloutTargetToolingExclusionTest extends TestCase
{
    public function test_phase_65l_tooling_exclusion_docs_and_lock_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-65l-saved-view-rollout-selector-snapshot-after-rollout-target-tooling-exclusion.json'));
        $this->assertFileExists(base_path('docs/phase-65l-next-saved-view-rollout-target.json'));
        $this->assertFileExists(base_path('docs/phase-65l-next-saved-view-rollout-target.md'));
    }

    public function test_rollout_selector_excludes_both_internal_tooling_candidates_from_prioritized_candidates(): void
    {
        $excluded = ReportSavedViewRolloutSelector::excludedInternalToolingCandidates();

        $this->assertNotEmpty($excluded);

        $excludedKeys = array_column($excluded, 'key');

        $this->assertContains('saved-view-rollout-selector', $excludedKeys);
        $this->assertContains('saved-view-rollout-target', $excludedKeys);

        foreach ($excluded as $candidate) {
            $this->assertTrue(ReportSavedViewRolloutSelector::isInternalToolingCandidate($candidate));
        }

        foreach (ReportSavedViewRolloutSelector::prioritizedCandidates() as $candidate) {
            $this->assertFalse(
                ReportSavedViewRolloutSelector::isInternalToolingCandidate($candidate),
                "{$candidate['key']} should not remain in prioritized rollout candidates."
            );

            $this->assertFalse(
                ReportSavedViewRolloutSelector::isPrintOnlyCandidate($candidate),
                "{$candidate['key']} should not be a print-only rollout candidate."
            );
        }
    }

    public function test_phase_65l_plan_tracks_tooling_exclusions_and_next_target_is_not_tooling(): void
    {
        $plan = ReportSavedViewRolloutSelector::plan();

        $this->assertArrayHasKey('excluded_print_candidate_count', $plan);
        $this->assertArrayHasKey('excluded_print_candidates', $plan);
        $this->assertArrayHasKey('excluded_tooling_candidate_count', $plan);
        $this->assertArrayHasKey('excluded_tooling_candidates', $plan);

        $this->assertGreaterThanOrEqual(2, $plan['excluded_tooling_candidate_count']);
        $this->assertSame(count($plan['excluded_tooling_candidates']), $plan['excluded_tooling_candidate_count']);

        $this->assertTrue($plan['has_next_candidate']);
        $this->assertIsArray($plan['next_candidate']);
        $this->assertFalse(ReportSavedViewRolloutSelector::isInternalToolingCandidate($plan['next_candidate']));
        $this->assertFalse(ReportSavedViewRolloutSelector::isPrintOnlyCandidate($plan['next_candidate']));
    }

    public function test_phase_65l_lock_matches_selector_snapshot(): void
    {
        $snapshot = json_decode(
            file_get_contents(base_path('docs/phase-65l-saved-view-rollout-selector-snapshot-after-rollout-target-tooling-exclusion.json')),
            true
        );

        $lock = json_decode(
            file_get_contents(base_path('docs/phase-65l-next-saved-view-rollout-target.json')),
            true
        );

        $this->assertSame('Phase 65L', $snapshot['phase']);
        $this->assertSame('Phase 65L', $lock['phase']);
        $this->assertSame('Phase 65K clean', $lock['baseline']['phase']);
        $this->assertSame('7adc043', $lock['baseline']['commit']);
        $this->assertSame('1185 passed / 10400 assertions', $lock['baseline']['tests']);

        $nextCandidate = $snapshot['selector_plan']['next_candidate'];

        $this->assertSame($nextCandidate['key'], $lock['selected_target']['key']);
        $this->assertSame($nextCandidate['view_path'], $lock['selected_target']['view_path']);
        $this->assertFalse($lock['selected_target']['registered_at_lock_time']);
        $this->assertFalse($lock['selected_target']['print_only_candidate']);
        $this->assertFalse($lock['selected_target']['internal_tooling_candidate']);
        $this->assertNotSame('saved-view-rollout-target', $lock['selected_target']['key']);
    }

    public function test_phase_65l_lock_is_documented(): void
    {
        $lock = json_decode(
            file_get_contents(base_path('docs/phase-65l-next-saved-view-rollout-target.json')),
            true
        );

        $doc = file_get_contents(base_path('docs/phase-65l-next-saved-view-rollout-target.md'));

        $this->assertStringContainsString('Phase 65L', $doc);
        $this->assertStringContainsString('7adc043', $doc);
        $this->assertStringContainsString('1185 passed / 10400 assertions', $doc);
        $this->assertStringContainsString('saved-view-rollout-target', $doc);
        $this->assertStringContainsString($lock['selected_target']['key'], $doc);
        $this->assertStringContainsString($lock['proposed_contract_seed']['config_partial_path'], $doc);
    }
}
