<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Services\ReportSavedViewService;
use Tests\TestCase;

class ReportSavedViewPhase79AArchivingContractTest extends TestCase
{
    public function test_phase_79a_contract_files_select_archiving(): void
    {
        $jsonPath = base_path(
            'docs/phase-79a-saved-view-archiving-contract.json'
        );
        $markdownPath = base_path(
            'docs/phase-79a-saved-view-archiving-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $contract = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertSame('Phase 79A', $contract['phase']);
        $this->assertSame('contract', $contract['type']);
        $this->assertSame(
            'saved_view_archiving',
            $contract['selection_decision']['selected_capability']
        );
        $this->assertSame('Phase 78C', $contract['baseline']['phase']);
        $this->assertSame('5c3def2', $contract['baseline']['commit']);
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
            'Phase 79B',
            $contract['next_recommendation']['phase']
        );
    }

    public function test_contract_locks_database_model_and_status_filter_design(): void
    {
        $contract = $this->contract();

        $this->assertSame(
            'report_saved_views',
            $contract['future_database_contract']['table']
        );
        $this->assertSame(
            'archived_at',
            $contract['future_database_contract']
                ['column']['name']
        );
        $this->assertSame(
            'nullable timestamp',
            $contract['future_database_contract']
                ['column']['type']
        );
        $this->assertSame(
            ['user_id', 'archived_at'],
            $contract['future_database_contract']['index']
        );
        $this->assertSame(
            'datetime',
            $contract['future_model_contract']['cast']
        );
        $this->assertSame(
            ['active', 'archived', 'all'],
            $contract['future_management_filter_contract']
                ['allowed_values']
        );
        $this->assertSame(
            'active',
            $contract['future_management_filter_contract']
                ['default']
        );
        $this->assertTrue(
            $contract['future_management_filter_contract']
                ['filtered_csv_export_respects_status']
        );
    }

    public function test_contract_locks_archive_restore_and_user_scope(): void
    {
        $contract = $this->contract();
        $methods = $contract['future_service_contract']['new_methods'];

        foreach ([
            'archive',
            'restore',
            'bulkArchive',
            'bulkRestore',
        ] as $method) {
            $this->assertArrayHasKey($method, $methods);
            $this->assertNotEmpty($methods[$method]['rules']);
        }

        $this->assertContains(
            'clear is_default in the same transaction',
            $methods['archive']['rules']
        );
        $this->assertContains(
            'do not automatically restore default status',
            $methods['restore']['rules']
        );
        $this->assertTrue(
            $contract['preserved_behavior']
                ['authenticated_user_scope']
        );
        $this->assertTrue(
            $contract['preserved_behavior']
                ['no_cross_user_disclosure']
        );
    }

    public function test_contract_locks_actions_csv_and_preserved_behavior(): void
    {
        $contract = $this->contract();

        $this->assertContains(
            'archive',
            $contract['future_action_contract']
                ['active_row_actions']
        );
        $this->assertContains(
            'restore',
            $contract['future_action_contract']
                ['archived_row_actions']
        );
        $this->assertFalse(
            $contract['csv_and_import_contract']['schema_change']
        );
        $this->assertFalse(
            $contract['csv_and_import_contract']
                ['format_version_change']
        );
        $this->assertFalse(
            $contract['csv_and_import_contract']
                ['existing_writer_changes_expected']
        );
        $this->assertFalse(
            $contract['csv_and_import_contract']
                ['import_parser_changes_expected']
        );

        foreach ([
            'selected_csv_export',
            'filtered_csv_export',
            'csv_import_preview_and_apply',
            'bulk_delete',
            'delete_all',
            'authenticated_user_scope',
            'main_only_workflow',
        ] as $key) {
            $this->assertTrue(
                $contract['preserved_behavior'][$key],
                $key
            );
        }

        $this->assertNotEmpty(
            $contract['required_phase_79b_tests']
        );
    }

    public function test_phase_79a_historical_contract_records_implementation_boundary(): void
    {
        $contract = $this->contract();

        $this->assertSame('Phase 79A', $contract['phase']);
        $this->assertFalse(
            $contract['scope']['runtime_changes_expected']
        );
        $this->assertFalse(
            $contract['scope']['database_changes_expected']
        );
        $this->assertSame(
            'archived_at',
            $contract['future_database_contract']
                ['column']['name']
        );
        $this->assertSame(
            ['active', 'archived', 'all'],
            $contract['future_management_filter_contract']
                ['allowed_values']
        );
        $this->assertSame(
            'Phase 79B',
            $contract['scope']['implementation_phase']
        );
    }

    public function test_phase_79a_historical_contract_declares_model_and_service_shape(): void
    {
        $contract = $this->contract();

        $this->assertSame(
            'archived_at',
            $contract['future_model_contract']
                ['fillable_addition']
        );
        $this->assertSame(
            'datetime',
            $contract['future_model_contract']['cast']
        );

        foreach ([
            'archive',
            'restore',
            'bulkArchive',
            'bulkRestore',
        ] as $method) {
            $this->assertArrayHasKey(
                $method,
                $contract['future_service_contract']
                    ['new_methods']
            );
        }

        $this->assertFalse(
            $contract['csv_and_import_contract']['schema_change']
        );
        $this->assertFalse(
            $contract['csv_and_import_contract']
                ['format_version_change']
        );
    }

    public function test_agents_file_preserves_main_only_workflow(): void
    {
        $agents = file_get_contents(base_path('AGENTS.md'));

        foreach ([
            '## Main-only workflow',
            'Do not create or push a phase branch.',
            'Do not create a Codex worktree.',
            '### 9. Commit directly on main',
            '### 10. Push only main',
            'Only push completed, fully validated commits to `origin/main`.',
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
                    . 'phase-79a-saved-view-archiving-contract.json'
                )
            ),
            true
        );
    }
}
