<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewCsvExportWriter;
use ReflectionClass;
use Tests\TestCase;

class ReportSavedViewPhase78ASelectedCsvExportContractTest extends TestCase
{
    public function test_phase_78a_contract_files_exist_and_select_next_capability(): void
    {
        $jsonPath = base_path(
            'docs/phase-78a-selected-saved-view-csv-export-contract.json'
        );
        $markdownPath = base_path(
            'docs/phase-78a-selected-saved-view-csv-export-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $contract = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertSame('Phase 78A', $contract['phase']);
        $this->assertSame('contract', $contract['type']);
        $this->assertSame(
            'selected_saved_view_csv_export',
            $contract['selection_decision']['selected_capability']
        );
        $this->assertSame('Phase 77C', $contract['baseline']['phase']);
        $this->assertSame('4220470', $contract['baseline']['commit']);
        $this->assertFalse(
            $contract['scope']['runtime_changes_expected']
        );
        $this->assertFalse(
            $contract['scope']['database_changes_expected']
        );
        $this->assertSame(
            'Phase 78B',
            $contract['scope']['implementation_phase']
        );
        $this->assertSame(
            'Phase 78C',
            $contract['scope']['finalization_phase']
        );
        $this->assertSame(
            'Phase 78B',
            $contract['next_recommendation']['phase']
        );
    }

    public function test_contract_defines_selected_export_http_service_and_response_boundaries(): void
    {
        $contract = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-78a-selected-saved-view-csv-export-contract.json'
                )
            ),
            true
        );

        $this->assertSame(
            'POST',
            $contract['future_http_contract']['method']
        );
        $this->assertSame(
            'reports.saved-views.export-selected',
            $contract['future_http_contract']['route_name']
        );
        $this->assertSame(
            'exportSelected',
            $contract['future_http_contract']['controller_method']
        );
        $this->assertContains(
            'auth',
            $contract['future_http_contract']['middleware']
        );
        $this->assertSame(
            'exportSelectedForManagement',
            $contract['future_service_contract']['method']
        );
        $this->assertSame(
            'App\\Support\\Reports\\ReportSavedViewCsvExportWriter',
            $contract['future_response_contract']['writer']
        );
        $this->assertFalse(
            $contract['future_response_contract']['writer_changes_expected']
        );
        $this->assertSame(
            'return a valid header-only CSV',
            $contract['future_response_contract']
                ['empty_owned_selection_behavior']
        );
        $this->assertTrue(
            $contract['future_view_contract']
                ['reuse_existing_row_checkboxes']
        );
        $this->assertTrue(
            $contract['future_view_contract']
                ['selected_export_button_disabled_when_empty']
        );
        $this->assertNotEmpty(
            $contract['required_phase_78b_tests']
        );
    }

    public function test_current_management_baseline_has_selection_and_filtered_export_but_not_selected_export(): void
    {
        $view = file_get_contents(
            resource_path('views/reports/saved-views/index.blade.php')
        );
        $controller = file_get_contents(
            app_path('Http/Controllers/ReportSavedViewController.php')
        );
        $service = file_get_contents(
            app_path('Services/ReportSavedViewService.php')
        );
        $routes = file_get_contents(base_path('routes/web.php'));

        foreach ([
            'report-saved-views-export-link',
            'report-saved-views-bulk-action-form',
            'report-saved-view-bulk-select-checkbox',
            'report-saved-views-select-all-checkbox',
            'report-saved-views-bulk-delete-button',
            'report-saved-views-selected-count',
        ] as $marker) {
            $this->assertStringContainsString($marker, $view);
        }

        $this->assertStringContainsString(
            'public function export(',
            $controller
        );
        $this->assertStringContainsString(
            'public function exportForManagement(',
            $service
        );

        foreach ([
            'reports.saved-views.export-selected',
            'report-saved-views-export-selected-button',
        ] as $marker) {
            $this->assertStringNotContainsString($marker, $view);
            $this->assertStringNotContainsString($marker, $routes);
        }

        $this->assertStringNotContainsString(
            'public function exportSelected(',
            $controller
        );
        $this->assertStringNotContainsString(
            'public function exportSelectedForManagement(',
            $service
        );
    }

    public function test_existing_writer_is_reusable_without_phase_78a_runtime_changes(): void
    {
        $reflection = new ReflectionClass(
            ReportSavedViewCsvExportWriter::class
        );
        $source = file_get_contents($reflection->getFileName());

        $this->assertTrue($reflection->isFinal());
        $this->assertNull($reflection->getConstructor());
        $this->assertTrue(
            $reflection->getMethod('write')->isPublic()
        );
        $this->assertSame(
            'iterable',
            (string) $reflection
                ->getMethod('write')
                ->getParameters()[0]
                ->getType()
        );

        foreach ([
            "fopen('php://output', 'w')",
            'ReportSavedViewImportExportVersionRegistry::exportHeader()',
            'ReportSavedViewImportExportVersionRegistry::currentVersion()',
            '$filtersPayload = json_encode(',
            'fputcsv($handle',
            'fclose($handle)',
        ] as $marker) {
            $this->assertStringContainsString($marker, $source);
        }
    }
}
