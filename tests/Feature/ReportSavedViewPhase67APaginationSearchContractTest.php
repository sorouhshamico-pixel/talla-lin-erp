<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase67APaginationSearchContractTest extends TestCase
{
    public function test_phase_67a_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-67a-saved-view-management-pagination-search-contract.json'));
        $this->assertFileExists(base_path('docs/phase-67a-saved-view-management-pagination-search-contract.md'));
    }

    public function test_phase_67a_contract_matches_baseline_and_scope(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-67a-saved-view-management-pagination-search-contract.json')),
            true
        );

        $this->assertSame('Phase 67A', $contract['phase']);
        $this->assertSame('Phase 66F clean', $contract['baseline']['phase']);
        $this->assertSame('9725524', $contract['baseline']['commit']);
        $this->assertSame('1254 passed / 11086 assertions', $contract['baseline']['previous_tests']);
        $this->assertSame('audit_contract_only', $contract['scope']['type']);
        $this->assertFalse($contract['scope']['implementation_changes_allowed']);
    }

    public function test_phase_67a_contract_documented_pre_implementation_pagination_and_search_gap(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-67a-saved-view-management-pagination-search-contract.json')),
            true
        );

        foreach ([
            'management_index_uses_service_list',
            'service_list_returns_collection',
            'service_list_uses_get_not_paginate',
            'management_index_maps_all_saved_views_before_render',
            'management_index_has_no_search_query_validation',
            'management_view_has_no_search_form',
            'management_view_has_no_report_key_filter',
            'management_view_has_no_pagination_links',
        ] as $key) {
            $this->assertTrue($contract['current_state'][$key], $key);
        }
    }
    public function test_phase_66_final_state_is_still_present_before_pagination_search_work(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $editView = file_get_contents(resource_path('views/reports/saved-views/edit.blade.php'));
        $indexView = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        $this->assertStringContainsString('ReportSavedViewRegistry::find($reportKey)', $controller);
        $this->assertStringContainsString('ReportSavedViewRegistry::indexRoute($reportKey)', $controller);
        $this->assertStringNotContainsString('private const REPORT_LABELS', $controller);
        $this->assertStringNotContainsString('private const REPORT_ROUTES', $controller);

        $this->assertStringContainsString('data-testid="report-saved-view-edit-filter-list"', $editView);
        $this->assertStringNotContainsString('name="filters[', $editView);

        $this->assertStringContainsString('data-testid="report-saved-view-actions"', $indexView);
        $this->assertStringContainsString('data-testid="report-saved-view-primary-actions"', $indexView);
        $this->assertStringContainsString('data-testid="report-saved-view-secondary-actions"', $indexView);
        $this->assertStringContainsString('data-testid="report-saved-view-danger-actions"', $indexView);
    }

    public function test_phase_67a_contract_documents_risk_and_phase_67b_recommendations(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-67a-saved-view-management-pagination-search-contract.json')),
            true
        );

        foreach ([
            'management_index_uses_service_list',
            'service_list_returns_collection',
            'service_list_uses_get_not_paginate',
            'management_index_maps_all_saved_views_before_render',
            'management_index_has_no_search_query_validation',
            'management_view_has_no_search_form',
            'management_view_has_no_report_key_filter',
            'management_view_has_no_pagination_links',
        ] as $key) {
            $this->assertTrue($contract['current_state'][$key], $key);
        }

        $this->assertSame('medium', $contract['scalability_risk']['severity']);
        $this->assertNotEmpty($contract['phase_67b_recommendations']);
        $this->assertStringContainsString('pagination', implode(' ', $contract['phase_67b_recommendations']));
        $this->assertStringContainsString('search', implode(' ', $contract['phase_67b_recommendations']));
    }

    public function test_phase_67a_markdown_documents_search_filter_and_pagination_gap(): void
    {
        $doc = file_get_contents(base_path('docs/phase-67a-saved-view-management-pagination-search-contract.md'));

        $this->assertStringContainsString('Phase 67A', $doc);
        $this->assertStringContainsString('9725524', $doc);
        $this->assertStringContainsString('1254 passed / 11086 assertions', $doc);
        $this->assertStringContainsString('no search form', $doc);
        $this->assertStringContainsString('no report key filter', $doc);
        $this->assertStringContainsString('no pagination links', $doc);
        $this->assertStringContainsString('Phase 67B recommendations', $doc);
    }
}
