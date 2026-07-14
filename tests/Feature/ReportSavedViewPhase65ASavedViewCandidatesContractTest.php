<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewPhase65ASavedViewCandidatesContractTest extends TestCase
{
    public function test_phase_65a_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-65a-saved-view-candidates-saved-view-controls-contract.json'));
        $this->assertFileExists(base_path('docs/phase-65a-saved-view-candidates-saved-view-controls-contract.md'));
    }

    public function test_phase_65a_contract_matches_phase_64z_locked_target(): void
    {
        $lock = json_decode(
            file_get_contents(base_path('docs/phase-64z-next-saved-view-rollout-target.json')),
            true
        );

        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65a-saved-view-candidates-saved-view-controls-contract.json')),
            true
        );

        $this->assertSame('Phase 65A', $contract['phase']);
        $this->assertSame('saved-view-candidates', $contract['target']['key']);
        $this->assertSame($lock['selected_target']['key'], $contract['target']['key']);
        $this->assertSame($lock['selected_target']['view_path'], $contract['target']['view_path']);
        $this->assertSame($lock['selected_target']['priority_score'], $contract['target']['priority_score']);
        $this->assertFalse($contract['target']['registered_at_lock_time']);
        $this->assertFalse($contract['target']['has_get_form_at_lock_time']);
        $this->assertTrue($contract['target']['route_implemented_as_closure']);
        $this->assertNull($contract['target']['controller_path']);
    }

    public function test_phase_65a_contract_target_view_and_existing_routes_exist(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65a-saved-view-candidates-saved-view-controls-contract.json')),
            true
        );

        $targetViewPath = str_replace('\\', '/', $contract['target']['view_path']);

        $this->assertFileExists(base_path($targetViewPath));

        $this->assertTrue(Route::has('reports.saved-view-candidates.index'));
        $this->assertTrue(Route::has('reports.saved-view-candidates.markdown'));
        $this->assertTrue(Route::has('reports.saved-view-candidates.json'));

        $this->assertFalse($contract['current_state_evidence']['contains_get_form']);
        $this->assertTrue($contract['current_state_evidence']['contains_filter_terms']);
        $this->assertTrue($contract['current_state_evidence']['contains_saved_view_controls_terms']);
        $this->assertFalse($contract['current_state_evidence']['contains_report_specific_config_partial']);
        $this->assertTrue($contract['current_state_evidence']['routes_have_index_route']);
        $this->assertTrue($contract['current_state_evidence']['routes_have_markdown_route']);
        $this->assertTrue($contract['current_state_evidence']['routes_have_json_route']);
        $this->assertFalse($contract['current_state_evidence']['routes_have_saved_view_store_route']);
    }

    public function test_phase_65a_contract_uses_saved_view_controls_conventions(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65a-saved-view-candidates-saved-view-controls-contract.json')),
            true
        );

        $registryKey = $contract['contract']['registry_key'];

        $this->assertSame('saved-view-candidates', $registryKey);

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

    public function test_phase_65a_contract_documents_routes_empty_hidden_fields_and_test_ids(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65a-saved-view-candidates-saved-view-controls-contract.json')),
            true
        );

        $this->assertSame('reports.saved-view-candidates.index', $contract['contract']['index_route']);
        $this->assertSame('reports.saved-view-candidates.markdown', $contract['contract']['markdown_route']);
        $this->assertSame('reports.saved-view-candidates.json', $contract['contract']['export_route']);
        $this->assertSame('reports.saved-view-candidates.saved-views.store', $contract['contract']['saved_view_store_route']);
        $this->assertSame([], $contract['contract']['candidate_hidden_fields']);

        foreach ([
            'section_card',
            'empty',
            'form_card',
            'form',
            'name_input',
            'default_checkbox',
            'save_button',
            'list',
            'item',
            'open_link',
            'active_badge',
            'default_badge',
        ] as $testIdKey) {
            $this->assertArrayHasKey($testIdKey, $contract['contract']['test_ids']);
            $this->assertNotEmpty($contract['contract']['test_ids'][$testIdKey]);
        }
    }

    public function test_phase_65a_contract_is_documented(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65a-saved-view-candidates-saved-view-controls-contract.json')),
            true
        );

        $doc = file_get_contents(base_path('docs/phase-65a-saved-view-candidates-saved-view-controls-contract.md'));

        $this->assertStringContainsString('Phase 65A', $doc);
        $this->assertStringContainsString('saved-view-candidates', $doc);
        $this->assertStringContainsString($contract['target']['view_path'], $doc);
        $this->assertStringContainsString($contract['contract']['index_route'], $doc);
        $this->assertStringContainsString($contract['contract']['markdown_route'], $doc);
        $this->assertStringContainsString($contract['contract']['export_route'], $doc);
        $this->assertStringContainsString($contract['contract']['saved_view_store_route'], $doc);
        $this->assertStringContainsString('ReportSavedViewPhase65ASavedViewCandidatesContractTest', $doc);
    }
}
