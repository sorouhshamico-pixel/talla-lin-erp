<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Services\ReportSavedViewService;
use Tests\TestCase;

class ReportSavedViewPhase80ATagsContractTest extends TestCase
{
    public function test_phase_80a_selects_tags_and_records_baseline(): void
    {
        $jsonPath = base_path(
            'docs/phase-80a-saved-view-tags-contract.json'
        );
        $markdownPath = base_path(
            'docs/phase-80a-saved-view-tags-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $contract = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertSame('Phase 80A', $contract['phase']);
        $this->assertSame('contract', $contract['type']);
        $this->assertSame(
            'saved_view_tags',
            $contract['selection_decision']
                ['selected_capability']
        );
        $this->assertSame(
            'Phase 79C',
            $contract['baseline']['phase']
        );
        $this->assertSame(
            '5dbb364',
            $contract['baseline']['commit']
        );
        $this->assertSame(
            'direct main only',
            $contract['baseline']['workflow']
        );
        $this->assertFalse(
            $contract['scope']['runtime_changes_expected']
        );
        $this->assertFalse(
            $contract['scope']['database_changes_expected']
        );
        $this->assertSame(
            'Phase 80B',
            $contract['next_recommendation']['phase']
        );
    }

    public function test_contract_locks_normalized_user_scoped_schema(): void
    {
        $contract = $this->contract();

        $this->assertSame(
            'report_saved_view_tags',
            $contract['future_database_contract']
                ['tags_table']['name']
        );
        $this->assertSame(
            ['user_id', 'normalized_name'],
            $contract['future_database_contract']
                ['tags_table']['unique']
        );
        $this->assertSame(
            'report_saved_view_tag',
            $contract['future_database_contract']
                ['pivot_table']['name']
        );
        $this->assertSame(
            [
                'report_saved_view_id',
                'report_saved_view_tag_id',
            ],
            $contract['future_database_contract']
                ['pivot_table']['primary_key']
        );
        $this->assertTrue(
            $contract['future_tag_model_contract']
                ['normalization']
                ['case_insensitive_uniqueness']
        );
        $this->assertSame(
            40,
            $contract['future_tag_model_contract']
                ['normalization']['maximum_name_length']
        );
    }

    public function test_contract_locks_filter_assignment_and_lifecycle(): void
    {
        $contract = $this->contract();

        $this->assertSame(
            'tag_ids',
            $contract['future_management_filter_contract']
                ['parameter']
        );
        $this->assertSame(
            'any selected tag',
            $contract['future_management_filter_contract']
                ['matching_mode']
        );

        foreach ([
            'listForUser',
            'create',
            'update',
            'delete',
            'syncSavedViewTags',
            'bulkAttach',
            'bulkDetach',
        ] as $method) {
            $this->assertArrayHasKey(
                $method,
                $contract['future_service_contract']['methods']
            );
        }

        $this->assertTrue(
            $contract['archive_interaction_contract']
                ['tags_remain_assigned_when_archived']
        );
        $this->assertTrue(
            $contract['archive_interaction_contract']
                ['tags_remain_assigned_when_restored']
        );
        $this->assertTrue(
            $contract['duplicate_contract']
                ['duplicate_copies_tags']
        );
    }

    public function test_contract_locks_csv_boundary_and_preserved_behavior(): void
    {
        $contract = $this->contract();

        $this->assertFalse(
            $contract['csv_and_import_contract']['schema_change']
        );
        $this->assertFalse(
            $contract['csv_and_import_contract']
                ['format_version_change']
        );
        $this->assertFalse(
            $contract['csv_and_import_contract']['tags_exported']
        );
        $this->assertFalse(
            $contract['csv_and_import_contract']
                ['writer_changes_expected']
        );
        $this->assertFalse(
            $contract['csv_and_import_contract']
                ['parser_changes_expected']
        );

        foreach ([
            'archiving_and_restoration',
            'status_filter',
            'selected_csv_export',
            'filtered_csv_export',
            'csv_import_preview_and_apply',
            'single_bulk_delete',
            'delete_all',
            'default_view_behavior',
            'authenticated_user_scope',
            'historical_source_contracts',
            'main_only_workflow',
        ] as $key) {
            $this->assertTrue(
                $contract['preserved_behavior'][$key],
                $key
            );
        }

        $this->assertNotEmpty(
            $contract['required_phase_80b_tests']
        );
    }

    public function test_phase_80a_records_preimplementation_contract(): void
    {
        $contract = $this->contract();

        $this->assertFalse(
            $contract['scope']['runtime_changes_expected']
        );
        $this->assertFalse(
            $contract['scope']['database_changes_expected']
        );
        $this->assertSame(
            'Phase 80B',
            $contract['scope']['implementation_phase']
        );
        $this->assertSame(
            'tags(): BelongsToMany',
            $contract[
                'future_saved_view_model_contract'
            ]['relation']
        );
        $this->assertSame(
            'App\\Services\\ReportSavedViewTagService',
            $contract[
                'future_service_contract'
            ]['tag_service']
        );
    }

    public function test_agents_file_records_phase_80a_and_main_only_workflow(): void
    {
        $agents = file_get_contents(base_path('AGENTS.md'));

        foreach ([
            '## Main-only workflow',
            'Do not create or push a phase branch.',
            'Do not create a Codex worktree.',
            '### 9. Commit directly on main',
            '### 10. Push only main',
            'Phase 80A — Prepare Saved View Tags Contract',
            'Phase 80B — Implement Saved View Tags',
        ] as $marker) {
            $this->assertStringContainsString($marker, $agents);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function contract(): array
    {
        return json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-80a-saved-view-tags-contract.json'
                )
            ),
            true
        );
    }
}
