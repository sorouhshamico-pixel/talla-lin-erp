<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase67EFinalizationTest extends TestCase
{
    public function test_phase_67e_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-67e-saved-view-management-search-pagination-finalization.json'));
        $this->assertFileExists(base_path('docs/phase-67e-saved-view-management-search-pagination-finalization.md'));
    }

    public function test_all_phase_67_contract_artifacts_exist(): void
    {
        foreach ([
            'docs/phase-67a-saved-view-management-pagination-search-contract.json',
            'docs/phase-67a-saved-view-management-pagination-search-contract.md',
            'docs/phase-67b-saved-view-management-pagination-search-implementation.json',
            'docs/phase-67b-saved-view-management-pagination-search-implementation.md',
            'docs/phase-67c-saved-view-management-filtered-empty-state-ux.json',
            'docs/phase-67c-saved-view-management-filtered-empty-state-ux.md',
            'docs/phase-67d-saved-view-management-per-page-results-summary.json',
            'docs/phase-67d-saved-view-management-per-page-results-summary.md',
            'docs/phase-67e-saved-view-management-search-pagination-finalization.json',
            'docs/phase-67e-saved-view-management-search-pagination-finalization.md',
        ] as $path) {
            $this->assertFileExists(base_path($path), $path);
        }
    }

    public function test_phase_67_final_controller_state_exposes_search_report_filter_and_per_page(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));

        foreach ([
            "'search' => ['nullable', 'string', 'max:120']",
            "'report_key' => ['nullable', 'string', 'max:120']",
            "'per_page' => ['nullable', 'integer', 'min:5', 'max:100']",
            '$savedViewService->paginateForManagement(',
            'matchingReportKeysForSearch($search)',
            'matchingFilterValuesForSearch($search)',
            '$savedViews->getCollection()->transform(',
            "'totalSavedViews' => \$savedViews->total()",
            "'per_page' => \$savedViews->perPage()",
            "'reportOptions' => \$this->reportFilterOptions()",
        ] as $marker) {
            $this->assertStringContainsString($marker, $controller);
        }

        $this->assertStringContainsString('ReportSavedViewRegistry::has($reportKey)', $controller);
        $this->assertStringContainsString('ReportSavedViewRegistry::reports()', $controller);
    }

    public function test_phase_67_final_service_state_uses_paginated_management_query(): void
    {
        $service = file_get_contents(app_path('Services/ReportSavedViewService.php'));

        foreach ([
            'use Illuminate\Contracts\Pagination\LengthAwarePaginator;',
            'public function paginateForManagement(',
            'array $matchingReportKeys = []',
            'array $matchingFilterValues = []',
            '->paginate($perPage)',
            '->withQueryString()',
            "->where('user_id', \$user->id)",
            "->orderByDesc('is_default')",
            "->orderBy('name')",
        ] as $marker) {
            $this->assertStringContainsString($marker, $service);
        }
    }

    public function test_phase_67_final_index_view_state_has_search_filter_empty_state_and_summary_controls(): void
    {
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            'data-testid="report-saved-views-search-form"',
            'data-testid="report-saved-views-search-input"',
            'data-testid="report-saved-views-report-key-select"',
            'data-testid="report-saved-views-per-page-select"',
            'data-testid="report-saved-views-search-submit-button"',
            'data-testid="report-saved-views-search-clear-link"',
            'data-testid="report-saved-views-active-filters"',
            'data-testid="report-saved-views-active-search"',
            'data-testid="report-saved-views-active-report-key"',
            'data-testid="report-saved-views-active-filters-clear-link"',
            'data-testid="report-saved-views-filtered-empty-message"',
            'data-testid="report-saved-views-filtered-empty-clear-link"',
            'data-testid="report-saved-views-unfiltered-empty-message"',
            'data-testid="report-saved-views-results-summary"',
            'data-testid="report-saved-views-per-page-summary"',
            'data-testid="report-saved-views-pagination"',
            '$savedViews->links()',
            '$savedViews->count() === 0',
            '$savedViews->total() > 0',
            '$savedViews->firstItem()',
            '$savedViews->lastItem()',
            '$savedViews->perPage()',
        ] as $marker) {
            $this->assertStringContainsString($marker, $view);
        }
    }

    public function test_phase_66_saved_view_management_guardrails_remain_present(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $editView = file_get_contents(resource_path('views/reports/saved-views/edit.blade.php'));
        $indexView = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        $this->assertStringContainsString('ReportSavedViewRegistry::find($reportKey)', $controller);
        $this->assertStringContainsString('ReportSavedViewRegistry::indexRoute($reportKey)', $controller);
        $this->assertStringContainsString('abort_unless((int) $savedView->user_id === (int) $request->user()->id, 404)', $controller);

        $this->assertStringContainsString('data-testid="report-saved-view-edit-filter-list"', $editView);
        $this->assertStringNotContainsString('name="filters[', $editView);

        foreach ([
            'data-testid="report-saved-view-actions"',
            'data-testid="report-saved-view-primary-actions"',
            'data-testid="report-saved-view-secondary-actions"',
            'data-testid="report-saved-view-danger-actions"',
        ] as $marker) {
            $this->assertStringContainsString($marker, $indexView);
        }
    }

    public function test_phase_67e_json_contract_documents_final_state(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-67e-saved-view-management-search-pagination-finalization.json')),
            true
        );

        $this->assertSame('Phase 67E', $contract['phase']);
        $this->assertSame('Phase 67D clean', $contract['baseline']['phase']);
        $this->assertSame('be7172f', $contract['baseline']['commit']);
        $this->assertSame('1281 passed / 11277 assertions', $contract['baseline']['previous_tests']);
        $this->assertFalse($contract['scope']['implementation_changes_expected']);

        foreach (['67A', '67B', '67C', '67D'] as $phase) {
            $this->assertArrayHasKey($phase, $contract['completed_phase_67_work']);
        }

        foreach ([
            'search_input_visible',
            'report_key_filter_visible',
            'per_page_selector_visible',
            'paginated_management_query',
            'pagination_preserves_query_string',
            'active_filter_summary_visible',
            'filtered_empty_state_message_visible',
            'results_range_summary_visible',
            'phase_66_guardrails_preserved',
        ] as $finalStateKey) {
            $this->assertTrue($contract['final_state_contract'][$finalStateKey], $finalStateKey);
        }

        $this->assertSame('Phase 68A', $contract['recommended_next_phase']['phase']);
    }
}
