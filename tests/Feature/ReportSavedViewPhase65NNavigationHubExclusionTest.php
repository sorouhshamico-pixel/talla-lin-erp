<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewRolloutSelector;
use Tests\TestCase;

class ReportSavedViewPhase65NNavigationHubExclusionTest extends TestCase
{
    public function test_phase_65n_navigation_hub_exclusion_docs_and_lock_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-65n-saved-view-rollout-selector-snapshot-after-navigation-hub-exclusion.json'));
        $this->assertFileExists(base_path('docs/phase-65n-next-saved-view-rollout-target.json'));
        $this->assertFileExists(base_path('docs/phase-65n-next-saved-view-rollout-target.md'));
    }

    public function test_rollout_selector_excludes_navigation_hub_candidates_from_prioritized_candidates(): void
    {
        $excluded = ReportSavedViewRolloutSelector::excludedNavigationHubCandidates();

        $this->assertNotEmpty($excluded);

        $excludedKeys = array_column($excluded, 'key');

        $this->assertContains('center', $excludedKeys);

        foreach ($excluded as $candidate) {
            $this->assertTrue(ReportSavedViewRolloutSelector::isNavigationHubCandidate($candidate));
        }

        foreach (ReportSavedViewRolloutSelector::prioritizedCandidates() as $candidate) {
            $this->assertFalse(
                ReportSavedViewRolloutSelector::isNavigationHubCandidate($candidate),
                "{$candidate['key']} should not remain in prioritized rollout candidates."
            );

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

    public function test_phase_65n_plan_tracks_navigation_exclusions_and_next_target_is_not_navigation_hub(): void
    {
        $plan = ReportSavedViewRolloutSelector::plan();

        $this->assertArrayHasKey('excluded_print_candidate_count', $plan);
        $this->assertArrayHasKey('excluded_print_candidates', $plan);
        $this->assertArrayHasKey('excluded_tooling_candidate_count', $plan);
        $this->assertArrayHasKey('excluded_tooling_candidates', $plan);
        $this->assertArrayHasKey('excluded_navigation_candidate_count', $plan);
        $this->assertArrayHasKey('excluded_navigation_candidates', $plan);

        $this->assertGreaterThanOrEqual(1, $plan['excluded_navigation_candidate_count']);
        $this->assertSame(count($plan['excluded_navigation_candidates']), $plan['excluded_navigation_candidate_count']);

        $this->assertTrue($plan['has_next_candidate']);
        $this->assertIsArray($plan['next_candidate']);
        $this->assertFalse(ReportSavedViewRolloutSelector::isNavigationHubCandidate($plan['next_candidate']));
        $this->assertFalse(ReportSavedViewRolloutSelector::isInternalToolingCandidate($plan['next_candidate']));
        $this->assertFalse(ReportSavedViewRolloutSelector::isPrintOnlyCandidate($plan['next_candidate']));
    }

    public function test_phase_65n_lock_matches_selector_snapshot(): void
    {
        $snapshot = json_decode(
            file_get_contents(base_path('docs/phase-65n-saved-view-rollout-selector-snapshot-after-navigation-hub-exclusion.json')),
            true
        );

        $lock = json_decode(
            file_get_contents(base_path('docs/phase-65n-next-saved-view-rollout-target.json')),
            true
        );

        $this->assertSame('Phase 65N', $snapshot['phase']);
        $this->assertSame('Phase 65N', $lock['phase']);
        $this->assertSame('Phase 65M clean', $lock['baseline']['phase']);
        $this->assertSame('2390575', $lock['baseline']['commit']);
        $this->assertSame('1195 passed / 10474 assertions', $lock['baseline']['tests']);

        $nextCandidate = $snapshot['selector_plan']['next_candidate'];

        $this->assertSame($nextCandidate['key'], $lock['selected_target']['key']);
        $this->assertSame($nextCandidate['view_path'], $lock['selected_target']['view_path']);
        $this->assertFalse($lock['selected_target']['registered_at_lock_time']);
        $this->assertFalse($lock['selected_target']['print_only_candidate']);
        $this->assertFalse($lock['selected_target']['internal_tooling_candidate']);
        $this->assertFalse($lock['selected_target']['navigation_hub_candidate']);
        $this->assertNotSame('center', $lock['selected_target']['key']);
    }

    public function test_phase_65n_lock_is_documented(): void
    {
        $lock = json_decode(
            file_get_contents(base_path('docs/phase-65n-next-saved-view-rollout-target.json')),
            true
        );

        $doc = file_get_contents(base_path('docs/phase-65n-next-saved-view-rollout-target.md'));

        $this->assertStringContainsString('Phase 65N', $doc);
        $this->assertStringContainsString('2390575', $doc);
        $this->assertStringContainsString('1195 passed / 10474 assertions', $doc);
        $this->assertStringContainsString('center', $doc);
        $this->assertStringContainsString($lock['selected_target']['key'], $doc);
        $this->assertStringContainsString($lock['proposed_contract_seed']['config_partial_path'], $doc);
    }
}
