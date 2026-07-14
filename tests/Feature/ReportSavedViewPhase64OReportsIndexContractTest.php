<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewPhase64OReportsIndexContractTest extends TestCase
{
    public function test_phase_64o_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-64o-reports-index-saved-view-controls-contract.json'));
        $this->assertFileExists(base_path('docs/phase-64o-reports-index-saved-view-controls-contract.md'));
    }

    public function test_phase_64o_contract_matches_phase_64n_locked_target(): void
    {
        $lock = json_decode(
            file_get_contents(base_path('docs/phase-64n-next-saved-view-rollout-target.json')),
            true
        );

        $contract = json_decode(
            file_get_contents(base_path('docs/phase-64o-reports-index-saved-view-controls-contract.json')),
            true
        );

        $this->assertSame('Phase 64O', $contract['phase']);
        $this->assertSame('index', $contract['target']['key']);
        $this->assertSame($lock['selected_target']['key'], $contract['target']['key']);
        $this->assertSame($lock['selected_target']['view_path'], $contract['target']['view_path']);
        $this->assertSame($lock['selected_target']['priority_score'], $contract['target']['priority_score']);
        $this->assertFalse($contract['target']['registered_at_lock_time']);
        $this->assertFalse($contract['target']['has_saved_view_controls_at_lock_time']);
    }

    public function test_phase_64o_contract_target_view_and_controller_exist(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-64o-reports-index-saved-view-controls-contract.json')),
            true
        );

        $targetViewPath = str_replace('\\', '/', $contract['target']['view_path']);
        $controllerPath = str_replace('\\', '/', $contract['target']['controller_path']);

        $this->assertFileExists(base_path($targetViewPath));
        $this->assertFileExists(base_path($controllerPath));

        $this->assertTrue($contract['current_state_evidence']['contains_get_form']);
        $this->assertTrue($contract['current_state_evidence']['contains_filter_terms']);
        $this->assertFalse($contract['current_state_evidence']['contains_saved_view_controls']);
        $this->assertFalse($contract['current_state_evidence']['contains_report_specific_config_partial']);
        $this->assertFalse($contract['current_state_evidence']['controller_has_report_key_constant']);
        $this->assertFalse($contract['current_state_evidence']['controller_uses_report_saved_view_service']);
        $this->assertFalse($contract['current_state_evidence']['controller_has_store_saved_view_method']);
        $this->assertFalse($contract['current_state_evidence']['routes_have_saved_view_store_route']);
    }

    public function test_phase_64o_contract_uses_saved_view_controls_conventions(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-64o-reports-index-saved-view-controls-contract.json')),
            true
        );

        $registryKey = $contract['contract']['registry_key'];

        $this->assertSame('index', $registryKey);

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

    public function test_phase_64o_contract_documents_routes_hidden_fields_and_test_ids(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-64o-reports-index-saved-view-controls-contract.json')),
            true
        );

        $this->assertTrue(Route::has('reports.index'));

        $this->assertSame('reports.index.saved-views.store', $contract['contract']['saved_view_store_route']);

        $this->assertSame([
            'from_date',
            'to_date',
            'branch_id',
            'expense_category_id',
            'payment_method',
        ], $contract['contract']['candidate_hidden_fields']);

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

    public function test_phase_64o_contract_is_documented(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-64o-reports-index-saved-view-controls-contract.json')),
            true
        );

        $doc = file_get_contents(base_path('docs/phase-64o-reports-index-saved-view-controls-contract.md'));

        $this->assertStringContainsString('Phase 64O', $doc);
        $this->assertStringContainsString('index', $doc);
        $this->assertStringContainsString($contract['target']['view_path'], $doc);
        $this->assertStringContainsString($contract['target']['controller_path'], $doc);
        $this->assertStringContainsString($contract['contract']['saved_view_store_route'], $doc);
        $this->assertStringContainsString('ReportSavedViewPhase64OReportsIndexContractTest', $doc);
    }
}
