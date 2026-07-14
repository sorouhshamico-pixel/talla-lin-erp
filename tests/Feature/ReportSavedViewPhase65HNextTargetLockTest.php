<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewRolloutSelector;
use Tests\TestCase;

class ReportSavedViewPhase65HNextTargetLockTest extends TestCase
{
    public function test_phase_65h_lock_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-65h-saved-view-rollout-selector-snapshot.json'));
        $this->assertFileExists(base_path('docs/phase-65h-next-saved-view-rollout-target.json'));
        $this->assertFileExists(base_path('docs/phase-65h-next-saved-view-rollout-target.md'));
    }

    public function test_phase_65h_locked_target_matches_selector_snapshot(): void
    {
        $snapshot = json_decode(
            file_get_contents(base_path('docs/phase-65h-saved-view-rollout-selector-snapshot.json')),
            true
        );

        $lock = json_decode(
            file_get_contents(base_path('docs/phase-65h-next-saved-view-rollout-target.json')),
            true
        );

        $this->assertSame('Phase 65H', $snapshot['phase']);
        $this->assertSame('Phase 65H', $lock['phase']);
        $this->assertSame('Phase 65G clean', $lock['baseline']['phase']);
        $this->assertSame('107827d', $lock['baseline']['commit']);
        $this->assertSame('1165 passed / 10261 assertions', $lock['baseline']['tests']);
        $this->assertTrue($snapshot['selector_plan']['has_next_candidate']);

        $nextCandidate = $snapshot['selector_plan']['next_candidate'];

        $this->assertSame($nextCandidate['key'], $lock['selected_target']['key']);
        $this->assertSame($nextCandidate['view_path'], $lock['selected_target']['view_path']);
        $this->assertSame($nextCandidate['priority_score'], $lock['selected_target']['priority_score']);
        $this->assertFalse($lock['selected_target']['registered_at_lock_time']);
        $this->assertFalse($lock['selected_target']['print_only_candidate']);
        $this->assertNotSame('sales-invoice-collections', $lock['selected_target']['key']);
    }

    public function test_phase_65h_locked_target_is_not_print_only_and_view_exists(): void
    {
        $lock = json_decode(
            file_get_contents(base_path('docs/phase-65h-next-saved-view-rollout-target.json')),
            true
        );

        $candidate = [
            'key' => $lock['selected_target']['key'],
            'view_path' => $lock['selected_target']['view_path'],
        ];

        $this->assertFalse(ReportSavedViewRolloutSelector::isPrintOnlyCandidate($candidate));
        $this->assertFileExists(base_path(str_replace('\\', '/', $lock['selected_target']['view_path'])));
    }

    public function test_phase_65h_locked_target_config_path_uses_convention(): void
    {
        $lock = json_decode(
            file_get_contents(base_path('docs/phase-65h-next-saved-view-rollout-target.json')),
            true
        );

        $key = $lock['selected_target']['key'];

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

    public function test_phase_65h_lock_is_documented(): void
    {
        $lock = json_decode(
            file_get_contents(base_path('docs/phase-65h-next-saved-view-rollout-target.json')),
            true
        );

        $doc = file_get_contents(base_path('docs/phase-65h-next-saved-view-rollout-target.md'));

        $this->assertStringContainsString('Phase 65H', $doc);
        $this->assertStringContainsString('Phase 65G clean', $doc);
        $this->assertStringContainsString('107827d', $doc);
        $this->assertStringContainsString('1165 passed / 10261 assertions', $doc);
        $this->assertStringContainsString($lock['selected_target']['key'], $doc);
        $this->assertStringContainsString($lock['selected_target']['view_path'], $doc);
        $this->assertStringContainsString($lock['proposed_contract_seed']['config_partial_path'], $doc);
    }
}
