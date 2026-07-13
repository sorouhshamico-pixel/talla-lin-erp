<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase64LockedTargetContractTest extends TestCase
{
    public function test_phase_64c_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-64-locked-target-saved-view-controls-contract.json'));
        $this->assertFileExists(base_path('docs/phase-64-locked-target-saved-view-controls-contract.md'));
    }

    public function test_phase_64c_contract_matches_locked_target_and_inspection(): void
    {
        $lock = json_decode(
            file_get_contents(base_path('docs/phase-64-next-saved-view-rollout-target.json')),
            true
        );

        $inspection = json_decode(
            file_get_contents(base_path('docs/phase-64-locked-saved-view-rollout-target-inspection.json')),
            true
        );

        $contract = json_decode(
            file_get_contents(base_path('docs/phase-64-locked-target-saved-view-controls-contract.json')),
            true
        );

        $this->assertSame('Phase 64C', $contract['phase']);
        $this->assertSame('Prepare Locked Target Saved View Controls Contract', $contract['title']);
        $this->assertSame($lock['selected_target']['key'], $contract['target']['key']);
        $this->assertSame($inspection['target']['key'], $contract['target']['key']);
        $this->assertSame(
            str_replace('\\', '/', $lock['selected_target']['view_path']),
            str_replace('\\', '/', $contract['target']['view_path'])
        );
        $this->assertSame(
            str_replace('\\', '/', $inspection['target']['view_path']),
            str_replace('\\', '/', $contract['target']['view_path'])
        );
    }

    public function test_phase_64c_contract_target_view_exists(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-64-locked-target-saved-view-controls-contract.json')),
            true
        );

        $this->assertFileExists(base_path(str_replace('\\', '/', $contract['target']['view_path'])));
    }

    public function test_phase_64c_contract_uses_saved_view_controls_conventions(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-64-locked-target-saved-view-controls-contract.json')),
            true
        );

        $registryKey = $contract['contract']['registry_key'];

        $this->assertSame($contract['target']['key'], $registryKey);
        $this->assertSame(
            'resources/views/reports/partials/' . $registryKey . '-saved-view-controls-config.blade.php',
            str_replace('\\', '/', $contract['contract']['config_partial_path'])
        );
        $this->assertSame(
            'reports.partials.' . $registryKey . '-saved-view-controls-config',
            $contract['contract']['config_partial']
        );
        $this->assertSame('reports.partials.saved-view-controls', $contract['contract']['shared_controls_partial']);
        $this->assertStringContainsString($contract['contract']['config_partial'], $contract['contract']['view_include']);
    }

    public function test_phase_64c_contract_documents_routes_hidden_fields_and_test_ids(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-64-locked-target-saved-view-controls-contract.json')),
            true
        );

        $this->assertArrayHasKey('candidate_hidden_fields', $contract['contract']);
        $this->assertIsArray($contract['contract']['candidate_hidden_fields']);

        $this->assertArrayHasKey('test_ids', $contract['contract']);
        $this->assertArrayHasKey('section_card', $contract['contract']['test_ids']);
        $this->assertArrayHasKey('form_card', $contract['contract']['test_ids']);
        $this->assertArrayHasKey('form', $contract['contract']['test_ids']);
        $this->assertArrayHasKey('name_input', $contract['contract']['test_ids']);
        $this->assertArrayHasKey('default_checkbox', $contract['contract']['test_ids']);
        $this->assertArrayHasKey('save_button', $contract['contract']['test_ids']);

        $this->assertArrayHasKey('route_names', $contract['evidence']);
        $this->assertIsArray($contract['evidence']['route_names']);
    }

    public function test_phase_64c_contract_is_documented(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-64-locked-target-saved-view-controls-contract.json')),
            true
        );

        $doc = file_get_contents(base_path('docs/phase-64-locked-target-saved-view-controls-contract.md'));

        $this->assertStringContainsString('Phase 64C', $doc);
        $this->assertStringContainsString('Prepare Locked Target Saved View Controls Contract', $doc);
        $this->assertStringContainsString($contract['target']['key'], $doc);
        $this->assertStringContainsString($contract['target']['view_path'], $doc);
        $this->assertStringContainsString($contract['contract']['config_partial_path'], $doc);
        $this->assertStringContainsString('ReportSavedViewPhase64LockedTargetContractTest', $doc);
    }
}
