<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase64KNextTargetLockTest extends TestCase
{
    public function test_phase_64k_lock_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-64k-saved-view-rollout-selector-snapshot.json'));
        $this->assertFileExists(base_path('docs/phase-64k-next-saved-view-rollout-target.json'));
        $this->assertFileExists(base_path('docs/phase-64k-next-saved-view-rollout-target.md'));
    }

    public function test_phase_64k_locked_target_matches_selector_snapshot(): void
    {
        $snapshot = json_decode(
            file_get_contents(base_path('docs/phase-64k-saved-view-rollout-selector-snapshot.json')),
            true
        );

        $lock = json_decode(
            file_get_contents(base_path('docs/phase-64k-next-saved-view-rollout-target.json')),
            true
        );

        $this->assertSame('Phase 64K', $snapshot['phase']);
        $this->assertSame('Phase 64K', $lock['phase']);
        $this->assertTrue($snapshot['selector_plan']['has_next_candidate']);

        $nextCandidate = $snapshot['selector_plan']['next_candidate'];

        $this->assertSame($nextCandidate['key'], $lock['selected_target']['key']);
        $this->assertSame($nextCandidate['view_path'], $lock['selected_target']['view_path']);
        $this->assertSame($nextCandidate['priority_score'], $lock['selected_target']['priority_score']);
        $this->assertFalse($lock['selected_target']['registered_at_lock_time']);
    }

    public function test_phase_64k_locked_target_view_exists_and_config_path_uses_convention(): void
    {
        $lock = json_decode(
            file_get_contents(base_path('docs/phase-64k-next-saved-view-rollout-target.json')),
            true
        );

        $key = $lock['selected_target']['key'];
        $viewPath = str_replace('\\', '/', $lock['selected_target']['view_path']);

        $this->assertFileExists(base_path($viewPath));

        $this->assertSame(
            'reports.partials.' . $key . '-saved-view-controls-config',
            $lock['proposed_contract_seed']['config_partial']
        );

        $this->assertSame(
            'resources/views/reports/partials/' . $key . '-saved-view-controls-config.blade.php',
            str_replace('\\', '/', $lock['proposed_contract_seed']['config_partial_path'])
        );

        $this->assertSame('reports.partials.saved-view-controls', $lock['proposed_contract_seed']['shared_controls_partial']);
    }

    public function test_phase_64k_lock_is_documented(): void
    {
        $lock = json_decode(
            file_get_contents(base_path('docs/phase-64k-next-saved-view-rollout-target.json')),
            true
        );

        $doc = file_get_contents(base_path('docs/phase-64k-next-saved-view-rollout-target.md'));

        $this->assertStringContainsString('Phase 64K', $doc);
        $this->assertStringContainsString('Phase 64J clean', $doc);
        $this->assertStringContainsString('01f5eeb', $doc);
        $this->assertStringContainsString($lock['selected_target']['key'], $doc);
        $this->assertStringContainsString($lock['selected_target']['view_path'], $doc);
        $this->assertStringContainsString($lock['proposed_contract_seed']['config_partial_path'], $doc);
    }
}
