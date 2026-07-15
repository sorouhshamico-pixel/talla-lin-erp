<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase68ABulkSelectionContractTest extends TestCase
{
    public function test_phase_68a_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-68a-saved-view-management-bulk-selection-contract.json'));
        $this->assertFileExists(base_path('docs/phase-68a-saved-view-management-bulk-selection-contract.md'));
    }

    public function test_phase_68a_contract_matches_baseline_and_scope(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-68a-saved-view-management-bulk-selection-contract.json')),
            true
        );

        $this->assertSame('Phase 68A', $contract['phase']);
        $this->assertSame('Phase 67E clean', $contract['baseline']['phase']);
        $this->assertSame('c54365e', $contract['baseline']['commit']);
        $this->assertSame('1288 passed / 11360 assertions', $contract['baseline']['previous_tests']);
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

    public function test_current_management_page_has_row_actions_and_delete_all_but_no_bulk_selection_controls(): void
    {
        $indexView = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            'data-testid="report-saved-view-row"',
            'data-testid="report-saved-view-actions"',
            'data-testid="report-saved-view-primary-actions"',
            'data-testid="report-saved-view-secondary-actions"',
            'data-testid="report-saved-view-danger-actions"',
            'data-testid="report-saved-view-delete-button"',
            'data-testid="report-saved-views-clear-all-button"',
        ] as $existingMarker) {
            $this->assertStringContainsString($existingMarker, $indexView);
        }

        foreach ([
            'data-testid="report-saved-view-bulk-select-checkbox"',
            'data-testid="report-saved-views-select-all-checkbox"',
            'data-testid="report-saved-views-bulk-action-form"',
            'data-testid="report-saved-views-bulk-delete-button"',
            'name="saved_view_ids[]"',
            "name='saved_view_ids[]'",
        ] as $missingMarker) {
            $this->assertStringNotContainsString($missingMarker, $indexView);
        }
    }

    public function test_current_routes_and_controller_have_no_bulk_destroy_action(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));

        foreach ([
            'reports.saved-views.index',
            'reports.saved-views.destroy',
            'reports.saved-views.destroy-all',
        ] as $existingRouteName) {
            $this->assertStringContainsString($existingRouteName, $routes);
        }

        foreach ([
            'reports.saved-views.bulk-destroy',
            'reports.saved-views.destroy-selected',
            'bulkDestroy',
            'destroySelected',
            'selectedSavedViewIds',
            'saved_view_ids',
        ] as $missingMarker) {
            $this->assertStringNotContainsString($missingMarker, $routes);
            $this->assertStringNotContainsString($missingMarker, $controller);
        }
    }

    public function test_phase_67_final_state_is_preserved_before_bulk_selection_work(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $service = file_get_contents(app_path('Services/ReportSavedViewService.php'));
        $indexView = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            "'search' => ['nullable', 'string', 'max:120']",
            "'report_key' => ['nullable', 'string', 'max:120']",
            "'per_page' => ['nullable', 'integer', 'min:5', 'max:100']",
            '$savedViewService->paginateForManagement(',
            "'per_page' => \$savedViews->perPage()",
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
            'data-testid="report-saved-views-active-filters"',
            'data-testid="report-saved-views-filtered-empty-message"',
            'data-testid="report-saved-views-results-summary"',
            'data-testid="report-saved-views-pagination"',
        ] as $viewMarker) {
            $this->assertStringContainsString($viewMarker, $indexView);
        }
    }

    public function test_phase_68a_contract_documents_recommendations_and_guardrails(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-68a-saved-view-management-bulk-selection-contract.json')),
            true
        );

        foreach ([
            'single_row_actions_exist',
            'delete_all_action_exists',
            'bulk_selection_checkboxes_absent',
            'select_all_checkbox_absent',
            'bulk_delete_form_absent',
            'bulk_destroy_route_absent',
            'bulk_destroy_controller_method_absent',
        ] as $key) {
            $this->assertTrue($contract['current_state'][$key], $key);
        }

        $this->assertSame('Phase 68B', $contract['phase_68b_recommendation']['title'] === 'Implement Saved View Management Bulk Selection'
            ? 'Phase 68B'
            : null
        );
        $this->assertSame('medium', $contract['phase_68b_recommendation']['risk']);
        $this->assertNotEmpty($contract['phase_68b_recommendation']['implementation_targets']);
        $this->assertNotEmpty($contract['guardrails']);
    }

    public function test_phase_68a_markdown_mentions_selection_scope_and_next_phase(): void
    {
        $markdown = file_get_contents(base_path('docs/phase-68a-saved-view-management-bulk-selection-contract.md'));

        foreach ([
            'Phase 68A',
            'Phase 68B',
            'select-all',
            'per-row checkboxes',
            'selected saved view IDs only',
            'selection-scoped',
            'Risk level: medium',
        ] as $expectedText) {
            $this->assertStringContainsString($expectedText, $markdown);
        }
    }
}
