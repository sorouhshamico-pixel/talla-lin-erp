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

    public function test_current_runtime_records_preimplementation_gap(): void
    {
        $model = file_get_contents(
            app_path('Models/ReportSavedView.php')
        );
        $service = file_get_contents(
            app_path('Services/ReportSavedViewService.php')
        );
        $controller = file_get_contents(
            app_path(
                'Http/Controllers/ReportSavedViewController.php'
            )
        );
        $routes = file_get_contents(base_path('routes/web.php'));
        $view = file_get_contents(
            resource_path(
                'views/reports/saved-views/index.blade.php'
            )
        );

        $this->assertStringNotContainsString(
            "'archived_at'",
            $model
        );
        $this->assertStringNotContainsString(
            'public function isArchived(',
            $model
        );
        $this->assertStringNotContainsString(
            'public function archive(',
            $service
        );
        $this->assertStringNotContainsString(
            'public function restore(',
            $service
        );
        $this->assertStringNotContainsString(
            "->whereNull('archived_at')",
            $service
        );
        $this->assertStringNotContainsString(
            "'status' =>",
            $controller
        );

        foreach ([
            'reports.saved-views.archive',
            'reports.saved-views.restore',
            'reports.saved-views.bulk-archive',
            'reports.saved-views.bulk-restore',
        ] as $marker) {
            $this->assertStringNotContainsString($marker, $routes);
        }

        foreach ([
            'report-saved-views-status-select',
            'report-saved-view-archive-button',
            'report-saved-view-restore-button',
            'report-saved-views-bulk-archive-button',
            'report-saved-views-bulk-restore-button',
        ] as $marker) {
            $this->assertStringNotContainsString($marker, $view);
        }

        $this->assertStringContainsString(
            'reports.saved-views.export-selected',
            $routes
        );
        $this->assertStringContainsString(
            'report-saved-views-bulk-delete-button',
            $view
        );
    }

    public function test_model_and_service_public_baseline_remain_unchanged_in_contract_phase(): void
    {
        $model = new ReportSavedView();

        $this->assertSame([
            'user_id',
            'report_key',
            'name',
            'filters',
            'is_default',
        ], $model->getFillable());

        $this->assertTrue(
            method_exists(
                ReportSavedViewService::class,
                'exportSelectedForManagement'
            )
        );
        $this->assertFalse(
            method_exists(
                ReportSavedViewService::class,
                'archive'
            )
        );
        $this->assertFalse(
            method_exists(
                ReportSavedViewService::class,
                'restore'
            )
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
            'Phase 79A — Prepare Saved View Archiving Contract',
            'Phase 79B — Implement Saved View Archiving',
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
