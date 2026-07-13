<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewCandidateScanner;
use App\Support\Reports\ReportSavedViewRegistry;
use Tests\TestCase;

class ReportSavedViewPhase64NextRolloutTargetTest extends TestCase
{
    public function test_phase_64a_next_rollout_target_snapshot_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-64-next-saved-view-rollout-target.md'));
        $this->assertFileExists(base_path('docs/phase-64-next-saved-view-rollout-target.json'));
    }

    public function test_phase_64a_locked_target_snapshot_has_stable_identity(): void
    {
        $snapshot = json_decode(
            file_get_contents(base_path('docs/phase-64-next-saved-view-rollout-target.json')),
            true
        );

        $this->assertIsArray($snapshot);
        $this->assertSame('Phase 64A', $snapshot['phase']);
        $this->assertSame('Select Next Saved View Rollout Target', $snapshot['title']);

        $this->assertSame('customer-sales-invoice-aging-drilldown', $snapshot['selected_target']['key']);
        $this->assertSame('resources/views/reports/customer-sales-invoice-aging-drilldown.blade.php', $snapshot['selected_target']['view_path']);
        $this->assertSame(100, $snapshot['selected_target']['priority_score']);
    }

    public function test_phase_64a_locked_target_was_unregistered_at_lock_time(): void
    {
        $snapshot = json_decode(
            file_get_contents(base_path('docs/phase-64-next-saved-view-rollout-target.json')),
            true
        );

        $this->assertFalse($snapshot['selected_target']['registered']);
        $this->assertNotContains($snapshot['selected_target']['key'], $snapshot['registered_keys']);
    }

    public function test_phase_64a_locked_target_view_exists(): void
    {
        $snapshot = json_decode(
            file_get_contents(base_path('docs/phase-64-next-saved-view-rollout-target.json')),
            true
        );

        $this->assertFileExists(base_path($snapshot['selected_target']['view_path']));
    }

    public function test_phase_64a_locked_target_can_progress_from_unregistered_to_registered(): void
    {
        $snapshot = json_decode(
            file_get_contents(base_path('docs/phase-64-next-saved-view-rollout-target.json')),
            true
        );

        $targetKey = $snapshot['selected_target']['key'];
        $unregisteredKeys = array_column(ReportSavedViewCandidateScanner::unregisteredCandidates(), 'key');

        $this->assertTrue(
            ReportSavedViewRegistry::has($targetKey) || in_array($targetKey, $unregisteredKeys, true)
        );
    }

    public function test_phase_64a_next_rollout_target_is_documented(): void
    {
        $doc = file_get_contents(base_path('docs/phase-64-next-saved-view-rollout-target.md'));

        $this->assertStringContainsString('Phase 64A', $doc);
        $this->assertStringContainsString('Select Next Saved View Rollout Target', $doc);
        $this->assertStringContainsString('customer-sales-invoice-aging-drilldown', $doc);
        $this->assertStringContainsString('resources/views/reports/customer-sales-invoice-aging-drilldown.blade.php', $doc);
        $this->assertStringContainsString('ReportSavedViewPhase64NextRolloutTargetTest', $doc);
    }
}
