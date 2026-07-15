<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase70AImportContractTest extends TestCase
{
    public function test_phase_70a_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-70a-saved-view-management-import-contract.json'));
        $this->assertFileExists(base_path('docs/phase-70a-saved-view-management-import-contract.md'));
    }

    public function test_phase_70a_contract_matches_baseline_and_scope(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-70a-saved-view-management-import-contract.json')),
            true
        );

        $this->assertSame('Phase 70A', $contract['phase']);
        $this->assertSame('Phase 69C clean', $contract['baseline']['phase']);
        $this->assertSame('9917445', $contract['baseline']['commit']);
        $this->assertSame('1349 passed / 11949 assertions', $contract['baseline']['previous_tests']);
        $this->assertSame('audit_contract', $contract['scope']['type']);
        $this->assertFalse($contract['scope']['implementation_changes_expected']);

        foreach ([
            'routes/web.php',
            'app/Http/Controllers/ReportSavedViewController.php',
            'app/Services/ReportSavedViewService.php',
            'resources/views/reports/saved-views/index.blade.php',
            'resources/views/reports/saved-views/edit.blade.php',
            'app/Models/ReportSavedView.php',
            'app/Support/Reports/ReportSavedViewRegistry.php',
        ] as $excludedFile) {
            $this->assertContains($excludedFile, $contract['scope']['excluded_files']);
        }
    }

    public function test_current_saved_view_management_has_export_but_no_import_workflow(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $service = file_get_contents(app_path('Services/ReportSavedViewService.php'));
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            "Route::get('/reports/saved-views/export'",
            'reports.saved-views.export',
        ] as $exportRouteMarker) {
            $this->assertStringContainsString($exportRouteMarker, $routes);
        }

        foreach ([
            'public function export(Request $request, ReportSavedViewService $savedViewService): StreamedResponse',
            '$savedViewService->exportForManagement(',
            "'Content-Type' => 'text/csv; charset=UTF-8'",
        ] as $exportControllerMarker) {
            $this->assertStringContainsString($exportControllerMarker, $controller);
        }

        $this->assertStringContainsString('public function exportForManagement(', $service);
        $this->assertStringContainsString('data-testid="report-saved-views-export-link"', $view);

        foreach ([
            'reports.saved-views.import',
            '/reports/saved-views/import',
            'reports.saved-views.import-preview',
            '/reports/saved-views/import-preview',
        ] as $missingRouteMarker) {
            $this->assertStringNotContainsString($missingRouteMarker, $routes);
        }

        foreach ([
            'public function import(',
            'public function previewImport(',
            'importCsv(',
            'previewImportCsv(',
            'UploadedFile',
            'text/csv import',
        ] as $missingControllerMarker) {
            $this->assertStringNotContainsString($missingControllerMarker, $controller);
        }

        foreach ([
            'data-testid="report-saved-views-import-link"',
            'data-testid="report-saved-views-import-form"',
            'data-testid="report-saved-views-import-file-input"',
            "route('reports.saved-views.import'",
            "route('reports.saved-views.import-preview'",
            'استيراد CSV',
        ] as $missingViewMarker) {
            $this->assertStringNotContainsString($missingViewMarker, $view);
        }
    }

    public function test_current_phase_69_68_and_67_management_capabilities_remain_present(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $service = file_get_contents(app_path('Services/ReportSavedViewService.php'));
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            'reports.saved-views.index',
            'reports.saved-views.export',
            'reports.saved-views.bulk-destroy',
            'reports.saved-views.destroy-all',
            'reports.saved-views.destroy',
        ] as $routeMarker) {
            $this->assertStringContainsString($routeMarker, $routes);
        }

        foreach ([
            'public function index(Request $request, ReportSavedViewService $savedViewService): View',
            'public function export(Request $request, ReportSavedViewService $savedViewService): StreamedResponse',
            'public function bulkDestroy(Request $request): RedirectResponse',
            'private function managementReturnQuery(Request $request): array',
            'abort_unless((int) $savedView->user_id === (int) $request->user()->id, 404)',
        ] as $controllerMarker) {
            $this->assertStringContainsString($controllerMarker, $controller);
        }

        foreach ([
            'public function paginateForManagement(',
            'public function exportForManagement(',
            '->paginate($perPage)',
            '->withQueryString()',
            '->where(\'user_id\', $user->id)',
        ] as $serviceMarker) {
            $this->assertStringContainsString($serviceMarker, $service);
        }

        foreach ([
            'data-testid="report-saved-views-search-form"',
            'data-testid="report-saved-views-report-key-select"',
            'data-testid="report-saved-views-per-page-select"',
            'data-testid="report-saved-views-results-summary"',
            'data-testid="report-saved-views-pagination"',
            'data-testid="report-saved-views-export-link"',
            'data-testid="report-saved-views-bulk-action-form"',
            'data-testid="report-saved-views-bulk-delete-button"',
            'data-testid="report-saved-views-selected-count"',
            'data-testid="report-saved-views-bulk-return-search"',
            'data-testid="report-saved-view-actions"',
            'data-testid="report-saved-view-danger-actions"',
        ] as $viewMarker) {
            $this->assertStringContainsString($viewMarker, $view);
        }
    }

    public function test_phase_70a_contract_documents_current_state_and_next_recommendation(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-70a-saved-view-management-import-contract.json')),
            true
        );

        foreach ([
            'csv_export_route_exists',
            'csv_export_controller_action_exists',
            'csv_export_service_query_exists',
            'csv_export_link_exists',
            'management_search_filter_pagination_exists',
            'management_bulk_selection_exists',
            'management_bulk_delete_context_preserved',
            'import_route_absent',
            'import_controller_action_absent',
            'import_form_or_link_absent',
            'import_validation_absent',
            'import_preview_absent',
            'import_write_tests_absent',
        ] as $key) {
            $this->assertTrue($contract['current_state'][$key], $key);
        }

        $this->assertSame('Implement Saved View Management Import Preview', $contract['phase_70b_recommendation']['title']);
        $this->assertSame('medium-high', $contract['phase_70b_recommendation']['risk']);
        $this->assertNotEmpty($contract['phase_70b_recommendation']['implementation_targets']);
        $this->assertNotEmpty($contract['guardrails']);
    }

    public function test_phase_70a_markdown_mentions_preview_first_and_import_guardrails(): void
    {
        $markdown = file_get_contents(base_path('docs/phase-70a-saved-view-management-import-contract.md'));

        foreach ([
            'Phase 70A',
            'Phase 70B',
            'Import Preview',
            'preview-only import',
            'Do not create or update saved views during Phase 70B preview',
            'Do not accept unknown `report_key` values',
            'Do not import saved views for another user',
            'Do not silently overwrite existing saved views',
            'Do not trust CSV `filters_summary` as a complete source',
            'Keep Phase 69 export read-only and stable',
            'Keep Phase 68 bulk selection stable',
            'Keep Phase 67 management pagination stable',
        ] as $expectedText) {
            $this->assertStringContainsString($expectedText, $markdown);
        }
    }
}
