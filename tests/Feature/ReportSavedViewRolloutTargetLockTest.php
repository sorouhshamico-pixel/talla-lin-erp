<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewCandidateScanner;
use App\Support\Reports\ReportSavedViewRolloutSelector;
use Tests\TestCase;

class ReportSavedViewRolloutTargetLockTest extends TestCase
{
    public function test_phase_63a_rollout_target_snapshot_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-63-report-saved-view-rollout-target.md'));
        $this->assertFileExists(base_path('docs/phase-63-report-saved-view-rollout-target.json'));
    }

    public function test_locked_rollout_target_matches_selector_next_candidate(): void
    {
        $snapshot = json_decode(
            file_get_contents(base_path('docs/phase-63-report-saved-view-rollout-target.json')),
            true
        );

        $nextCandidate = ReportSavedViewRolloutSelector::nextCandidate();

        $this->assertIsArray($snapshot);
        $this->assertIsArray($nextCandidate);

        $this->assertSame($nextCandidate['key'], $snapshot['selected_target']['key']);
        $this->assertSame($nextCandidate['view_path'], $snapshot['selected_target']['view_path']);
        $this->assertSame($nextCandidate['priority_score'], $snapshot['selected_target']['priority_score']);
    }

    public function test_locked_rollout_target_is_unregistered_candidate(): void
    {
        $snapshot = json_decode(
            file_get_contents(base_path('docs/phase-63-report-saved-view-rollout-target.json')),
            true
        );

        $target = $snapshot['selected_target'];

        $this->assertFalse($target['registered']);
        $this->assertContains(
            $target['key'],
            array_column(ReportSavedViewCandidateScanner::unregisteredCandidates(), 'key')
        );
    }

    public function test_locked_rollout_target_view_exists(): void
    {
        $snapshot = json_decode(
            file_get_contents(base_path('docs/phase-63-report-saved-view-rollout-target.json')),
            true
        );

        $this->assertFileExists(base_path($snapshot['selected_target']['view_path']));
    }

    public function test_phase_63a_rollout_target_is_documented(): void
    {
        $doc = base_path('docs/phase-63-report-saved-view-rollout-target.md');
        $json = base_path('docs/phase-63-report-saved-view-rollout-target.json');

        $docContents = file_get_contents($doc);
        $jsonContents = file_get_contents($json);

        $this->assertStringContainsString('Phase 63A', $docContents);
        $this->assertStringContainsString('Lock Next Report Saved View Rollout Target', $docContents);
        $this->assertStringContainsString('ReportSavedViewRolloutTargetLockTest', $docContents);
        $this->assertStringContainsString('"customer-sales-invoice-aging"', $jsonContents);
        $this->assertStringContainsString('resources/views/reports/customer-sales-invoice-aging.blade.php', $docContents);
    }
}
