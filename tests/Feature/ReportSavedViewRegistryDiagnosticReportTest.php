<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewRegistry;
use App\Support\Reports\ReportSavedViewRegistryDiagnosticReport;
use App\Support\Reports\ReportSavedViewRegistryValidator;
use Tests\TestCase;

class ReportSavedViewRegistryDiagnosticReportTest extends TestCase
{
    public function test_diagnostic_report_builds_current_registry_health_payload(): void
    {
        $report = ReportSavedViewRegistryDiagnosticReport::build();

        $this->assertSame('Report Saved View Registry Diagnostic Report', $report['title']);

        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('rows', $report);
        $this->assertArrayHasKey('valid_report_keys', $report);
        $this->assertArrayHasKey('invalid_reports', $report);
        $this->assertArrayHasKey('generated_from', $report);

        $this->assertSame(ReportSavedViewRegistryValidator::summary(), $report['summary']);
        $this->assertSame(ReportSavedViewRegistryValidator::diagnostics(), $report['rows']);
        $this->assertSame(['sales-invoice-aging'], $report['valid_report_keys']);
        $this->assertSame([], $report['invalid_reports']);

        $this->assertContains(ReportSavedViewRegistry::class, $report['generated_from']);
        $this->assertContains(ReportSavedViewRegistryValidator::class, $report['generated_from']);
    }

    public function test_diagnostic_report_shortcut_methods_return_expected_values(): void
    {
        $this->assertTrue(ReportSavedViewRegistryDiagnosticReport::isHealthy());

        $this->assertSame(
            ReportSavedViewRegistryValidator::summary(),
            ReportSavedViewRegistryDiagnosticReport::summary()
        );

        $this->assertSame(
            ReportSavedViewRegistryValidator::diagnostics(),
            ReportSavedViewRegistryDiagnosticReport::rows()
        );

        $this->assertSame(['sales-invoice-aging'], ReportSavedViewRegistryDiagnosticReport::validReportKeys());
        $this->assertSame([], ReportSavedViewRegistryDiagnosticReport::invalidReports());
    }

    public function test_diagnostic_report_rows_include_sales_invoice_aging_details(): void
    {
        $rows = ReportSavedViewRegistryDiagnosticReport::rows();

        $this->assertCount(1, $rows);

        $row = $rows[0];

        $this->assertSame('sales-invoice-aging', $row['key']);
        $this->assertSame('تقرير أعمار ذمم فواتير المبيعات', $row['label']);
        $this->assertTrue($row['valid']);
        $this->assertSame(0, $row['error_count']);
        $this->assertSame([], $row['errors']);
        $this->assertSame('resources/views/reports/sales-invoice-aging.blade.php', $row['view_path']);
        $this->assertSame('resources/views/reports/partials/sales-invoice-aging-saved-view-controls-config.blade.php', $row['config_partial_path']);
        $this->assertSame(['customer_id', 'payment_status', 'aging_bucket'], $row['hidden_fields']);
    }

    public function test_diagnostic_report_markdown_contains_summary_and_report_rows(): void
    {
        $markdown = ReportSavedViewRegistryDiagnosticReport::markdown();

        $this->assertStringContainsString('# Report Saved View Registry Diagnostic Report', $markdown);
        $this->assertStringContainsString('## Summary', $markdown);
        $this->assertStringContainsString('- Report count: 1', $markdown);
        $this->assertStringContainsString('- Invalid count: 0', $markdown);
        $this->assertStringContainsString('- Valid: yes', $markdown);
        $this->assertStringContainsString('### sales-invoice-aging', $markdown);
        $this->assertStringContainsString('- Valid: yes', $markdown);
        $this->assertStringContainsString('- Hidden fields: customer_id, payment_status, aging_bucket', $markdown);
    }

    public function test_phase_57a_diagnostic_report_is_documented(): void
    {
        $doc = base_path('docs/phase-57-report-saved-view-registry-diagnostic-report.md');

        $this->assertFileExists($doc);

        $contents = file_get_contents($doc);

        $this->assertStringContainsString('Phase 57A', $contents);
        $this->assertStringContainsString('Report Saved View Registry Diagnostic Report', $contents);
        $this->assertStringContainsString('ReportSavedViewRegistryDiagnosticReport.php', $contents);
        $this->assertStringContainsString('markdown', $contents);
        $this->assertStringContainsString('sales-invoice-aging', $contents);
        $this->assertStringContainsString('ReportSavedViewRegistryDiagnosticReportTest', $contents);
    }
}
