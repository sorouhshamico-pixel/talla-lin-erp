<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewRegistryDiagnosticReport;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ReportSavedViewDiagnosticToolingFinalizationTest extends TestCase
{
    public function test_diagnostic_report_exposes_markdown_lines_and_json_helpers(): void
    {
        $lines = ReportSavedViewRegistryDiagnosticReport::markdownLines();

        $this->assertIsArray($lines);
        $this->assertContains('# Report Saved View Registry Diagnostic Report', $lines);
        $this->assertContains('## Summary', $lines);
        $this->assertContains('- Report count: 11', $lines);
        $this->assertContains('### sales-invoice-aging', $lines);
        $this->assertContains('### customer-sales-invoice-aging', $lines);
        $this->assertContains('### customer-sales-invoice-aging', $lines);

        $json = ReportSavedViewRegistryDiagnosticReport::json();
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertSame('Report Saved View Registry Diagnostic Report', $decoded['title']);
        $this->assertSame(11, $decoded['summary']['report_count']);
        $this->assertSame(0, $decoded['summary']['invalid_count']);
        $this->assertTrue($decoded['summary']['valid']);
        $this->assertContains('sales-invoice-aging', $decoded['valid_report_keys']);
        $this->assertContains('customer-sales-invoice-aging', $decoded['valid_report_keys']);
        $this->assertSame([], $decoded['invalid_reports']);
    }

    public function test_artisan_command_uses_diagnostic_report_json_helper(): void
    {
        $consoleRoutes = file_get_contents(base_path('routes/console.php'));

        $this->assertStringContainsString('ReportSavedViewRegistryDiagnosticReport::json()', $consoleRoutes);
        $this->assertStringContainsString('ReportSavedViewRegistryDiagnosticReport::markdown()', $consoleRoutes);

        $exitCode = Artisan::call('reports:saved-view-diagnostics', [
            '--json' => true,
        ]);

        $decoded = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertIsArray($decoded);
        $this->assertSame('Report Saved View Registry Diagnostic Report', $decoded['title']);
        $this->assertSame(11, $decoded['summary']['report_count']);
        $this->assertSame(0, $decoded['summary']['invalid_count']);
        $this->assertTrue($decoded['summary']['valid']);
    }

    public function test_phase_57_diagnostic_tooling_finalization_is_documented(): void
    {
        $finalDoc = base_path('docs/phase-57-report-saved-view-diagnostic-tooling-finalization.md');
        $doc57a = base_path('docs/phase-57-report-saved-view-registry-diagnostic-report.md');
        $doc57b = base_path('docs/phase-57-report-saved-view-diagnostics-artisan-command.md');

        $this->assertFileExists($finalDoc);
        $this->assertFileExists($doc57a);
        $this->assertFileExists($doc57b);

        $finalContents = file_get_contents($finalDoc);
        $doc57aContents = file_get_contents($doc57a);
        $doc57bContents = file_get_contents($doc57b);

        $this->assertStringContainsString('Phase 57 is finalized.', $finalContents);
        $this->assertStringContainsString('markdownLines', $finalContents);
        $this->assertStringContainsString('JSON output', $finalContents);
        $this->assertStringContainsString('ReportSavedViewDiagnosticToolingFinalizationTest', $finalContents);

        $this->assertStringContainsString('Phase 57C finalization', $doc57aContents);
        $this->assertStringContainsString('markdownLines and json helpers', $doc57aContents);

        $this->assertStringContainsString('Phase 57C command finalization', $doc57bContents);
        $this->assertStringContainsString('ReportSavedViewRegistryDiagnosticReport::json', $doc57bContents);
    }
}
