<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewRegistryValidator;
use Tests\TestCase;

class ReportSavedViewRegistryDiagnosticsFinalizationTest extends TestCase
{
    public function test_registry_validator_diagnostics_report_current_valid_state(): void
    {
        $diagnostics = ReportSavedViewRegistryValidator::diagnostics();

        $this->assertCount(8, $diagnostics);

        $rowsByKey = collect($diagnostics)->keyBy('key');

        foreach ([
            'sales-invoice-aging',
            'customer-sales-invoice-aging',
            'customer-sales-invoice-aging-drilldown',
            'supplier-purchase-invoice-aging',
            'supplier-purchase-invoice-aging-drilldown',
            'cash-flow-dashboard',
            'index',
            'profit-loss',
        ] as $key) {
            $this->assertTrue($rowsByKey->has($key));

            $row = $rowsByKey[$key];

            $this->assertSame($key, $row['key']);
            $this->assertTrue($row['valid']);
            $this->assertSame([], $row['errors']);
            $this->assertNotEmpty($row['label']);
            $this->assertNotEmpty($row['view_path']);
            $this->assertNotEmpty($row['config_partial_path']);
        }
    }

    public function test_registry_validator_summary_counts_are_consistent(): void
    {
        $summary = ReportSavedViewRegistryValidator::summary();
        $diagnostics = ReportSavedViewRegistryValidator::diagnostics();

        $this->assertSame(count($diagnostics), $summary['report_count']);
        $this->assertSame(0, $summary['invalid_count']);
        $this->assertTrue($summary['valid']);
    }

    public function test_registry_validator_valid_report_keys_returns_only_valid_reports(): void
    {
        $validReportKeys = ReportSavedViewRegistryValidator::validReportKeys();

        $this->assertSame([
            'sales-invoice-aging',
            'customer-sales-invoice-aging',
            'customer-sales-invoice-aging-drilldown',
            'supplier-purchase-invoice-aging',
            'supplier-purchase-invoice-aging-drilldown',
            'cash-flow-dashboard',
            'index',
            'profit-loss',
        ], $validReportKeys);
    }

    public function test_phase_56_diagnostics_finalization_is_documented(): void
    {
        $doc = base_path('docs/phase-56-report-saved-view-registry-diagnostics-finalization.md');

        $this->assertFileExists($doc);

        $contents = file_get_contents($doc);

        $this->assertStringContainsString('Phase 56', $contents);
        $this->assertStringContainsString('diagnostics', strtolower($contents));
    }
}
