<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewRegistryValidator;
use Tests\TestCase;

class ReportSavedViewRegistryDiagnosticsFinalizationTest extends TestCase
{
    public function test_registry_validator_diagnostics_report_current_valid_state(): void
    {
        $diagnostics = ReportSavedViewRegistryValidator::diagnostics();

        $this->assertCount(1, $diagnostics);

        $row = $diagnostics[0];

        $this->assertSame('sales-invoice-aging', $row['key']);
        $this->assertSame('تقرير أعمار ذمم فواتير المبيعات', $row['label']);
        $this->assertTrue($row['valid']);
        $this->assertSame(0, $row['error_count']);
        $this->assertSame([], $row['errors']);
        $this->assertSame('resources/views/reports/sales-invoice-aging.blade.php', $row['view_path']);
        $this->assertSame('resources/views/reports/partials/sales-invoice-aging-saved-view-controls-config.blade.php', $row['config_partial_path']);
        $this->assertSame('reports.sales-invoice-aging.index', $row['index_route']);
        $this->assertSame('reports.sales-invoice-aging.saved-views.store', $row['saved_view_store_route']);
        $this->assertSame(['customer_id', 'payment_status', 'aging_bucket'], $row['hidden_fields']);
        $this->assertArrayHasKey('save_button', $row['test_ids']);
    }

    public function test_registry_validator_returns_invalid_report_diagnostics_for_custom_invalid_reports(): void
    {
        $invalidReports = ReportSavedViewRegistryValidator::invalidReports([
            'broken-report' => [
                'key' => 'wrong-key',
                'label' => '',
                'hidden_fields' => [],
                'test_ids' => [],
            ],
        ]);

        $this->assertCount(1, $invalidReports);

        $row = $invalidReports[0];

        $this->assertSame('broken-report', $row['key']);
        $this->assertFalse($row['valid']);
        $this->assertGreaterThan(0, $row['error_count']);
        $this->assertNotEmpty($row['errors']);

        $combinedErrors = implode("\n", $row['errors']);

        $this->assertStringContainsString('Registry array key must match the report key field.', $combinedErrors);
        $this->assertStringContainsString('Field [hidden_fields] must be a non-empty array.', $combinedErrors);
        $this->assertStringContainsString('Field [test_ids] must be a non-empty array.', $combinedErrors);
    }

    public function test_registry_validator_valid_report_keys_returns_only_valid_reports(): void
    {
        $this->assertSame(['sales-invoice-aging'], ReportSavedViewRegistryValidator::validReportKeys());
    }

    public function test_phase_56_diagnostics_finalization_is_documented(): void
    {
        $finalDoc = base_path('docs/phase-56-report-saved-view-registry-diagnostics-finalization.md');
        $validatorDoc = base_path('docs/phase-56-report-saved-view-registry-validator.md');
        $metadataDoc = base_path('docs/phase-56-report-saved-view-registry-metadata-helpers.md');

        $this->assertFileExists($finalDoc);
        $this->assertFileExists($validatorDoc);
        $this->assertFileExists($metadataDoc);

        $finalContents = file_get_contents($finalDoc);
        $validatorContents = file_get_contents($validatorDoc);
        $metadataContents = file_get_contents($metadataDoc);

        $this->assertStringContainsString('Phase 56 is finalized.', $finalContents);
        $this->assertStringContainsString('diagnostics', $finalContents);
        $this->assertStringContainsString('invalidReports', $finalContents);
        $this->assertStringContainsString('validReportKeys', $finalContents);
        $this->assertStringContainsString('ReportSavedViewRegistryDiagnosticsFinalizationTest', $finalContents);

        $this->assertStringContainsString('Phase 56C diagnostics finalization', $validatorContents);
        $this->assertStringContainsString('diagnostics, invalidReports, and validReportKeys', $validatorContents);

        $this->assertStringContainsString('Diagnostics integration', $metadataContents);
    }
}
