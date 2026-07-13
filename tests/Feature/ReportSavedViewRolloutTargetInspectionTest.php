<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewRolloutTargetInspectionTest extends TestCase
{
    public function test_phase_63b_inspection_snapshot_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-63-report-saved-view-rollout-target-inspection.md'));
        $this->assertFileExists(base_path('docs/phase-63-report-saved-view-rollout-target-inspection.json'));
    }

    public function test_inspection_matches_locked_target(): void
    {
        $locked = json_decode(
            file_get_contents(base_path('docs/phase-63-report-saved-view-rollout-target.json')),
            true
        );

        $inspection = json_decode(
            file_get_contents(base_path('docs/phase-63-report-saved-view-rollout-target-inspection.json')),
            true
        );

        $this->assertIsArray($locked);
        $this->assertIsArray($inspection);

        $this->assertSame(
            $locked['selected_target']['key'],
            $inspection['locked_target']['key']
        );

        $this->assertSame(
            $locked['selected_target']['view_path'],
            $inspection['locked_target']['view_path']
        );
    }

    public function test_inspected_view_exists_and_has_expected_metadata(): void
    {
        $inspection = json_decode(
            file_get_contents(base_path('docs/phase-63-report-saved-view-rollout-target-inspection.json')),
            true
        );

        $this->assertTrue($inspection['view']['exists']);
        $this->assertGreaterThan(0, $inspection['view']['line_count']);
        $this->assertArrayHasKey('form_count', $inspection['view']);
        $this->assertArrayHasKey('get_form_count', $inspection['view']);
        $this->assertArrayHasKey('has_saved_view_controls', $inspection['view']);
        $this->assertArrayHasKey('has_filters', $inspection['view']);

        $this->assertFileExists(base_path($inspection['locked_target']['view_path']));
    }

    public function test_inspection_recommends_config_partial_and_registry_key(): void
    {
        $inspection = json_decode(
            file_get_contents(base_path('docs/phase-63-report-saved-view-rollout-target-inspection.json')),
            true
        );

        $key = $inspection['locked_target']['key'];

        $this->assertSame($key, $inspection['recommended_registry_key']);
        $this->assertSame(
            'reports.partials.'.$key.'-saved-view-controls-config',
            $inspection['recommended_config_partial']
        );
        $this->assertSame(
            'resources/views/reports/partials/'.$key.'-saved-view-controls-config.blade.php',
            $inspection['recommended_config_partial_path']
        );
    }

    public function test_phase_63b_inspection_is_documented(): void
    {
        $doc = base_path('docs/phase-63-report-saved-view-rollout-target-inspection.md');
        $json = base_path('docs/phase-63-report-saved-view-rollout-target-inspection.json');

        $docContents = file_get_contents($doc);
        $jsonContents = file_get_contents($json);

        $this->assertStringContainsString('Phase 63B', $docContents);
        $this->assertStringContainsString('Inspect Locked Report Saved View Rollout Target', $docContents);
        $this->assertStringContainsString('ReportSavedViewRolloutTargetInspectionTest', $docContents);
        $this->assertStringContainsString('recommended_config_partial', $jsonContents);
        $this->assertStringContainsString('candidate_filter_fields', $jsonContents);
    }
}
