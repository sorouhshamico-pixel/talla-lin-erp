<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewCandidateScanner;
use App\Support\Reports\ReportSavedViewRegistry;
use Tests\TestCase;

class ReportSavedViewRolloutTargetLockTest extends TestCase
{
    public function test_phase_63a_rollout_target_snapshot_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-63-report-saved-view-rollout-target.md'));
        $this->assertFileExists(base_path('docs/phase-63-report-saved-view-rollout-target.json'));
    }

    public function test_locked_rollout_target_has_stable_identity(): void
    {
        $snapshot = json_decode(
            file_get_contents(base_path('docs/phase-63-report-saved-view-rollout-target.json')),
            true
        );

        $this->assertIsArray($snapshot);
        $this->assertSame('customer-sales-invoice-aging', $snapshot['selected_target']['key']);
        $this->assertSame(
            'resources/views/reports/customer-sales-invoice-aging.blade.php',
            $snapshot['selected_target']['view_path']
        );
        $this->assertSame(100, $snapshot['selected_target']['priority_score']);
    }

    public function test_locked_rollout_target_was_unregistered_at_lock_time(): void
    {
        $snapshot = json_decode(
            file_get_contents(base_path('docs/phase-63-report-saved-view-rollout-target.json')),
            true
        );

        $this->assertFalse($snapshot['selected_target']['registered']);
    }

    public function test_locked_rollout_target_view_exists(): void
    {
        $snapshot = json_decode(
            file_get_contents(base_path('docs/phase-63-report-saved-view-rollout-target.json')),
            true
        );

        $this->assertFileExists(base_path($snapshot['selected_target']['view_path']));
    }

    public function test_locked_rollout_target_can_progress_from_unregistered_to_registered(): void
    {
        $snapshot = json_decode(
            file_get_contents(base_path('docs/phase-63-report-saved-view-rollout-target.json')),
            true
        );

        $targetKey = $snapshot['selected_target']['key'];
        $unregisteredKeys = array_column(ReportSavedViewCandidateScanner::unregisteredCandidates(), 'key');

        $this->assertTrue(
            ReportSavedViewRegistry::has($targetKey) || in_array($targetKey, $unregisteredKeys, true)
        );
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
        $this->assertStringContainsString('customer-sales-invoice-aging', $jsonContents);
        $this->assertStringContainsString('resources/views/reports/customer-sales-invoice-aging.blade.php', $docContents);
    }
}
