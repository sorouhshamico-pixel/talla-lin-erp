<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewRegistry;
use Tests\TestCase;

class ReportSavedViewPhase66FSavedViewManagementFinalizationTest extends TestCase
{
    public function test_phase_66f_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-66f-saved-view-management-finalization.json'));
        $this->assertFileExists(base_path('docs/phase-66f-saved-view-management-finalization.md'));
    }

    public function test_all_phase_66_contract_artifacts_exist(): void
    {
        foreach ([
            'docs/phase-66a-saved-view-management-ux-audit-contract.json',
            'docs/phase-66a-saved-view-management-ux-audit-contract.md',
            'docs/phase-66b-saved-view-management-registry-alignment.json',
            'docs/phase-66b-saved-view-management-registry-alignment.md',
            'docs/phase-66c-saved-view-edit-filters-read-only.json',
            'docs/phase-66c-saved-view-edit-filters-read-only.md',
            'docs/phase-66d-saved-view-management-ownership-authorization.json',
            'docs/phase-66d-saved-view-management-ownership-authorization.md',
            'docs/phase-66e-saved-view-management-table-actions-ux-cleanup.json',
            'docs/phase-66e-saved-view-management-table-actions-ux-cleanup.md',
            'docs/phase-66f-saved-view-management-finalization.json',
            'docs/phase-66f-saved-view-management-finalization.md',
        ] as $path) {
            $this->assertFileExists(base_path($path), $path);
        }
    }

    public function test_management_controller_final_state_uses_registry_and_has_no_static_management_maps(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));

        $this->assertStringContainsString('use App\Support\Reports\ReportSavedViewRegistry;', $controller);
        $this->assertStringContainsString('ReportSavedViewRegistry::find($reportKey)', $controller);
        $this->assertStringContainsString('ReportSavedViewRegistry::indexRoute($reportKey)', $controller);

        $this->assertStringNotContainsString('private const REPORT_LABELS', $controller);
        $this->assertStringNotContainsString('private const REPORT_ROUTES', $controller);
        $this->assertStringNotContainsString('self::REPORT_LABELS', $controller);
        $this->assertStringNotContainsString('self::REPORT_ROUTES', $controller);
    }

    public function test_saved_view_edit_final_state_keeps_filters_read_only(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $editView = file_get_contents(resource_path('views/reports/saved-views/edit.blade.php'));

        $this->assertStringNotContainsString("'filters' => ['nullable', 'array']", $controller);
        $this->assertStringNotContainsString("'filters.*' => ['nullable', 'string', 'max:255']", $controller);
        $this->assertStringNotContainsString('$validated[\'filters\']', $controller);
        $this->assertStringNotContainsString("'filters' => \$filters", $controller);

        $this->assertStringContainsString('data-testid="report-saved-view-edit-filter-list"', $editView);
        $this->assertStringContainsString('data-testid="report-saved-view-edit-filter-raw-value"', $editView);
        $this->assertStringNotContainsString('data-testid="report-saved-view-edit-filter-inputs"', $editView);
        $this->assertStringNotContainsString('data-testid="report-saved-view-edit-filter-input"', $editView);
        $this->assertStringNotContainsString('name="filters[', $editView);
    }

    public function test_management_index_final_state_groups_actions_and_preserves_action_markers(): void
    {
        $indexView = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            'data-testid="report-saved-view-actions"',
            'data-testid="report-saved-view-primary-actions"',
            'data-testid="report-saved-view-secondary-actions"',
            'data-testid="report-saved-view-danger-actions"',
            'data-testid="report-saved-view-open-link"',
            'data-testid="report-saved-view-edit-link"',
            'data-testid="report-saved-view-apply-link"',
            'data-testid="report-saved-view-duplicate-form"',
            'data-testid="report-saved-view-duplicate-button"',
            'data-testid="report-saved-view-make-default-button"',
            'data-testid="report-saved-view-delete-button"',
        ] as $marker) {
            $this->assertStringContainsString($marker, $indexView);
        }

        $this->assertStringContainsString('<th>الإجراءات</th>', $indexView);
        $this->assertStringNotContainsString('<th>إجراء</th>', $indexView);
    }

    public function test_phase_66f_json_contract_documents_final_state(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-66f-saved-view-management-finalization.json')),
            true
        );

        $this->assertSame('Phase 66F', $contract['phase']);
        $this->assertSame('Phase 66E clean', $contract['baseline']['phase']);
        $this->assertSame('975f33a', $contract['baseline']['commit']);
        $this->assertFalse($contract['scope']['implementation_changes_expected']);

        foreach (['66A', '66B', '66C', '66D', '66E'] as $phase) {
            $this->assertArrayHasKey($phase, $contract['completed_phase_66_work']);
        }

        foreach ([
            'management_uses_registry_for_report_labels_and_urls',
            'saved_view_edit_filters_are_read_only',
            'cross_user_record_actions_return_not_found',
            'bulk_delete_is_scoped_to_authenticated_user',
            'management_table_actions_are_grouped',
            'existing_management_action_test_ids_are_preserved',
        ] as $finalStateKey) {
            $this->assertTrue($contract['final_state_contract'][$finalStateKey], $finalStateKey);
        }

        $this->assertSame('Phase 67A', $contract['recommended_next_phase']['phase']);
    }

    public function test_registry_still_has_registered_saved_view_reports(): void
    {
        $this->assertSame(13, ReportSavedViewRegistry::count());

        foreach ([
            'financial-dashboard',
            'sales-invoice-collections',
            'sales-invoice-collection-follow-ups',
            'profit-loss',
            'index',
        ] as $key) {
            $this->assertTrue(ReportSavedViewRegistry::has($key), $key);
            $this->assertNotNull(ReportSavedViewRegistry::indexRoute($key), $key);
        }
    }
}
