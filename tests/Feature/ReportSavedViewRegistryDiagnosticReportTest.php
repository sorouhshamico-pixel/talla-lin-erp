<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewRegistryDiagnosticReport;
use App\Support\Reports\ReportSavedViewRegistryValidator;
use Tests\TestCase;

class ReportSavedViewRegistryDiagnosticReportTest extends TestCase
{
    public function test_diagnostic_report_builds_summary_and_rows(): void
    {
        $report = ReportSavedViewRegistryDiagnosticReport::build();

        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('rows', $report);

        $this->assertSame(
            ReportSavedViewRegistryValidator::summary(),
            $report['summary']
        );

        $this->assertSame(
            ReportSavedViewRegistryValidator::diagnostics(),
            $report['rows']
        );
    }

    public function test_diagnostic_report_rows_and_valid_keys_include_registered_reports(): void
    {
        $rows = ReportSavedViewRegistryDiagnosticReport::rows();
        $rowsByKey = collect($rows)->keyBy('key');

        $this->assertCount(11, $rows);

        foreach ([
            'sales-invoice-aging',
            'customer-sales-invoice-aging',
            'customer-sales-invoice-aging-drilldown',
            'supplier-purchase-invoice-aging',
            'supplier-purchase-invoice-aging-drilldown',
            'cash-flow-dashboard',
            'index',
            'profit-loss',
            'receivable-payable-aging-dashboard',
            'sales-invoice-collection-follow-ups',
            'saved-view-candidates',
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

        $validReportKeys = ReportSavedViewRegistryDiagnosticReport::validReportKeys();

        $this->assertSame([
            'sales-invoice-aging',
            'customer-sales-invoice-aging',
            'customer-sales-invoice-aging-drilldown',
            'supplier-purchase-invoice-aging',
            'supplier-purchase-invoice-aging-drilldown',
            'cash-flow-dashboard',
            'index',
            'profit-loss',
            'receivable-payable-aging-dashboard',
            'sales-invoice-collection-follow-ups',
            'saved-view-candidates',
        ], $validReportKeys);

        $this->assertSame([], ReportSavedViewRegistryDiagnosticReport::invalidReports());
    }

    public function test_diagnostic_report_summary_matches_validator_state(): void
    {
        $summary = ReportSavedViewRegistryDiagnosticReport::summary();

        $this->assertSame(11, $summary['report_count']);
        $this->assertSame(0, $summary['invalid_count']);
        $this->assertTrue($summary['valid']);
        $this->assertTrue(ReportSavedViewRegistryDiagnosticReport::isHealthy());
    }

    public function test_diagnostic_report_markdown_contains_summary_and_report_rows(): void
    {
        $markdown = ReportSavedViewRegistryDiagnosticReport::markdown();

        $this->assertStringContainsString('# Report Saved View Registry Diagnostic Report', $markdown);
        $this->assertStringContainsString('## Summary', $markdown);
        $this->assertStringContainsString('- Report count: 11', $markdown);
        $this->assertStringContainsString('- Invalid count: 0', $markdown);
        $this->assertStringContainsString('- Valid: yes', $markdown);
        $this->assertStringContainsString('### sales-invoice-aging', $markdown);
        $this->assertStringContainsString('### customer-sales-invoice-aging', $markdown);
    }

    public function test_diagnostic_report_json_is_serializable(): void
    {
        $json = ReportSavedViewRegistryDiagnosticReport::json();

        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('summary', $decoded);
        $this->assertArrayHasKey('rows', $decoded);
        $this->assertSame(11, $decoded['summary']['report_count']);
        $this->assertSame(0, $decoded['summary']['invalid_count']);
    }

    public function test_phase_57_diagnostic_report_is_documented(): void
    {
        $doc = base_path('docs/phase-57-report-saved-view-registry-diagnostic-report.md');

        $this->assertFileExists($doc);

        $contents = file_get_contents($doc);

        $this->assertStringContainsString('Phase 57A', $contents);
        $this->assertStringContainsString('ReportSavedViewRegistryDiagnosticReport', $contents);
        $this->assertStringContainsString('ReportSavedViewRegistryDiagnosticReportTest', $contents);
    }
}
