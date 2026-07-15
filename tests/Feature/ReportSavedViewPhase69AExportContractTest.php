<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase69AExportContractTest extends TestCase
{
    public function test_phase_69a_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-69a-saved-view-management-export-contract.json'));
        $this->assertFileExists(base_path('docs/phase-69a-saved-view-management-export-contract.md'));
    }

    public function test_phase_69a_contract_matches_baseline_and_scope(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-69a-saved-view-management-export-contract.json')),
            true
        );

        $this->assertSame('Phase 69A', $contract['phase']);
        $this->assertSame('Phase 68E clean', $contract['baseline']['phase']);
        $this->assertSame('b24643c', $contract['baseline']['commit']);
        $this->assertSame('1327 passed / 11697 assertions', $contract['baseline']['previous_tests']);
        $this->assertSame('audit_contract', $contract['scope']['type']);
        $this->assertFalse($contract['scope']['implementation_changes_expected']);

        foreach ([
            'app/Http/Controllers/ReportSavedViewController.php',
            'app/Services/ReportSavedViewService.php',
            'routes/web.php',
            'resources/views/reports/saved-views/index.blade.php',
            'resources/views/reports/saved-views/edit.blade.php',
            'app/Models/ReportSavedView.php',
            'app/Support/Reports/ReportSavedViewRegistry.php',
        ] as $excludedFile) {
            $this->assertContains($excludedFile, $contract['scope']['excluded_files']);
        }
    }

    public function test_phase_69a_contract_documented_pre_implementation_export_gap(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-69a-saved-view-management-export-contract.json')),
            true
        );

        foreach ([
            'management_search_filter_pagination_exists',
            'management_bulk_selection_exists',
            'management_bulk_delete_context_preserved',
            'export_route_absent',
            'export_controller_action_absent',
            'export_link_or_button_absent',
            'export_test_coverage_absent',
        ] as $key) {
            $this->assertTrue($contract['current_state'][$key], $key);
        }

        $this->assertSame('Implement Saved View Management CSV Export', $contract['phase_69b_recommendation']['title']);
        $this->assertSame('low-medium', $contract['phase_69b_recommendation']['risk']);
        $this->assertNotEmpty($contract['phase_69b_recommendation']['implementation_targets']);
        $this->assertNotEmpty($contract['guardrails']);
    }

    public function test_phase_69a_contract_remains_historical_after_phase_69b_implementation(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-69a-saved-view-management-export-contract.json')),
            true
        );

        $this->assertSame('audit_contract', $contract['scope']['type']);
        $this->assertFalse($contract['scope']['implementation_changes_expected']);

        foreach ([
            'export_route_absent',
            'export_controller_action_absent',
            'export_link_or_button_absent',
            'export_test_coverage_absent',
        ] as $preImplementationGap) {
            $this->assertTrue($contract['current_state'][$preImplementationGap], $preImplementationGap);
        }
    }

    public function test_current_phase_67_and_phase_68_management_capabilities_remain_present(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));
        $service = file_get_contents(app_path('Services/ReportSavedViewService.php'));

        foreach ([
            'reports.saved-views.index',
            'reports.saved-views.bulk-destroy',
            'reports.saved-views.destroy-all',
            'reports.saved-views.destroy',
        ] as $routeMarker) {
            $this->assertStringContainsString($routeMarker, $routes);
        }

        foreach ([
            '$savedViewService->paginateForManagement(',
            "'search' => ['nullable', 'string', 'max:120']",
            "'report_key' => ['nullable', 'string', 'max:120']",
            "'per_page' => ['nullable', 'integer', 'min:5', 'max:100']",
            'public function bulkDestroy(Request $request): RedirectResponse',
            'private function managementReturnQuery(Request $request): array',
            'abort_unless((int) $savedView->user_id === (int) $request->user()->id, 404)',
        ] as $controllerMarker) {
            $this->assertStringContainsString($controllerMarker, $controller);
        }

        foreach ([
            'public function paginateForManagement(',
            '->paginate($perPage)',
            '->withQueryString()',
        ] as $serviceMarker) {
            $this->assertStringContainsString($serviceMarker, $service);
        }

        foreach ([
            'data-testid="report-saved-views-search-form"',
            'data-testid="report-saved-views-report-key-select"',
            'data-testid="report-saved-views-per-page-select"',
            'data-testid="report-saved-views-results-summary"',
            'data-testid="report-saved-views-pagination"',
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

    public function test_phase_69a_markdown_mentions_export_columns_scope_and_guardrails(): void
    {
        $markdown = file_get_contents(base_path('docs/phase-69a-saved-view-management-export-contract.md'));

        foreach ([
            'Phase 69A',
            'Phase 69B',
            'CSV Export',
            'search',
            'report_key',
            'full filtered result set',
            'name',
            'report label',
            'report key',
            'default flag',
            'filter count',
            'filters summary',
            'updated_at',
            'Do not export saved views owned by another user',
        ] as $expectedText) {
            $this->assertStringContainsString($expectedText, $markdown);
        }
    }
}
