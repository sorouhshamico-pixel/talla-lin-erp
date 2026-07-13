<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewCandidateScanner;
use App\Support\Reports\ReportSavedViewRegistry;
use Tests\TestCase;

class ReportSavedViewPhase64LockedTargetInspectionTest extends TestCase
{
    public function test_phase_64b_locked_target_inspection_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-64-locked-saved-view-rollout-target-inspection.json'));
        $this->assertFileExists(base_path('docs/phase-64-locked-saved-view-rollout-target-inspection.md'));
    }

    public function test_phase_64b_inspection_matches_phase_64a_locked_target(): void
    {
        $lock = json_decode(
            file_get_contents(base_path('docs/phase-64-next-saved-view-rollout-target.json')),
            true
        );

        $inspection = json_decode(
            file_get_contents(base_path('docs/phase-64-locked-saved-view-rollout-target-inspection.json')),
            true
        );

        $lockedViewPath = str_replace('\\', '/', $lock['selected_target']['view_path']);
        $inspectionViewPath = str_replace('\\', '/', $inspection['target']['view_path']);

        $this->assertSame('Phase 64B', $inspection['phase']);
        $this->assertSame('Inspect Locked Saved View Rollout Target', $inspection['title']);
        $this->assertSame($lock['selected_target']['key'], $inspection['target']['key']);
        $this->assertSame($lockedViewPath, $inspectionViewPath);
        $this->assertSame($lock['selected_target']['priority_score'], $inspection['target']['priority_score']);
    }

    public function test_phase_64b_inspected_target_view_exists_and_is_readable(): void
    {
        $inspection = json_decode(
            file_get_contents(base_path('docs/phase-64-locked-saved-view-rollout-target-inspection.json')),
            true
        );

        $this->assertTrue($inspection['view_inspection']['view_exists']);
        $this->assertGreaterThan(0, $inspection['view_inspection']['line_count']);
        $this->assertFileExists(base_path(str_replace('\\', '/', $inspection['target']['view_path'])));
    }

    public function test_phase_64b_recommended_rollout_contract_is_documented(): void
    {
        $inspection = json_decode(
            file_get_contents(base_path('docs/phase-64-locked-saved-view-rollout-target-inspection.json')),
            true
        );

        $contract = $inspection['recommended_rollout_contract'];
        $targetKey = $inspection['target']['key'];

        $this->assertSame($targetKey, $contract['registry_key']);
        $this->assertSame(
            'resources/views/reports/partials/' . $targetKey . '-saved-view-controls-config.blade.php',
            str_replace('\\', '/', $contract['config_partial_path'])
        );
        $this->assertSame('reports.partials.saved-view-controls', $contract['shared_controls_partial']);
        $this->assertStringContainsString($targetKey . '-saved-view-controls-config', $contract['config_partial']);
        $this->assertStringContainsString($contract['config_partial'], $contract['view_should_include']);
    }

    public function test_phase_64b_locked_target_can_still_progress_to_registered_state(): void
    {
        $inspection = json_decode(
            file_get_contents(base_path('docs/phase-64-locked-saved-view-rollout-target-inspection.json')),
            true
        );

        $targetKey = $inspection['target']['key'];
        $unregisteredKeys = array_column(ReportSavedViewCandidateScanner::unregisteredCandidates(), 'key');

        $this->assertTrue(
            ReportSavedViewRegistry::has($targetKey) || in_array($targetKey, $unregisteredKeys, true)
        );
    }

    public function test_phase_64b_inspection_is_documented(): void
    {
        $inspection = json_decode(
            file_get_contents(base_path('docs/phase-64-locked-saved-view-rollout-target-inspection.json')),
            true
        );

        $doc = file_get_contents(base_path('docs/phase-64-locked-saved-view-rollout-target-inspection.md'));

        $this->assertStringContainsString('Phase 64B', $doc);
        $this->assertStringContainsString('Inspect Locked Saved View Rollout Target', $doc);
        $this->assertStringContainsString($inspection['target']['key'], $doc);
        $this->assertStringContainsString(str_replace('\\', '/', $inspection['target']['view_path']), $doc);
        $this->assertStringContainsString('ReportSavedViewPhase64LockedTargetInspectionTest', $doc);
    }
}
