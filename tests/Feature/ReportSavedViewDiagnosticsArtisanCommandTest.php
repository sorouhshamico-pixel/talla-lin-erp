<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ReportSavedViewDiagnosticsArtisanCommandTest extends TestCase
{
    public function test_report_saved_view_diagnostics_command_is_registered_in_console_routes(): void
    {
        $consoleRoutes = base_path('routes/console.php');

        $this->assertFileExists($consoleRoutes);

        $contents = file_get_contents($consoleRoutes);

        $this->assertStringContainsString('ReportSavedViewRegistryDiagnosticReport', $contents);
        $this->assertStringContainsString('reports:saved-view-diagnostics', $contents);
        $this->assertStringContainsString('--json', $contents);
        $this->assertStringContainsString('Show report saved view registry diagnostics', $contents);
    }

    public function test_report_saved_view_diagnostics_command_outputs_markdown_report(): void
    {
        $exitCode = Artisan::call('reports:saved-view-diagnostics');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('# Report Saved View Registry Diagnostic Report', $output);
        $this->assertStringContainsString('## Summary', $output);
        $this->assertStringContainsString('- Report count: 5', $output);
        $this->assertStringContainsString('- Invalid count: 0', $output);
        $this->assertStringContainsString('- Valid: yes', $output);
        $this->assertStringContainsString('### sales-invoice-aging', $output);
        $this->assertStringContainsString('### customer-sales-invoice-aging', $output);
        $this->assertStringContainsString('### customer-sales-invoice-aging', $output);
        $this->assertStringContainsString('- Hidden fields: customer_id, payment_status, aging_bucket', $output);
    }

    public function test_report_saved_view_diagnostics_command_outputs_json_report(): void
    {
        $exitCode = Artisan::call('reports:saved-view-diagnostics', [
            '--json' => true,
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('"title": "Report Saved View Registry Diagnostic Report"', $output);
        $this->assertStringContainsString('"report_count": 5', $output);
        $this->assertStringContainsString('"invalid_count": 0', $output);
        $this->assertStringContainsString('"valid": true', $output);
        $this->assertStringContainsString('"sales-invoice-aging"', $output);
        $this->assertStringContainsString('"customer-sales-invoice-aging"', $output);
        $this->assertStringContainsString('"customer-sales-invoice-aging"', $output);
    }

    public function test_phase_57b_artisan_command_is_documented(): void
    {
        $doc = base_path('docs/phase-57-report-saved-view-diagnostics-artisan-command.md');

        $this->assertFileExists($doc);

        $contents = file_get_contents($doc);

        $this->assertStringContainsString('Phase 57B', $contents);
        $this->assertStringContainsString('Report Saved View Diagnostics Artisan Command', $contents);
        $this->assertStringContainsString('php artisan reports:saved-view-diagnostics', $contents);
        $this->assertStringContainsString('--json', $contents);
        $this->assertStringContainsString('ReportSavedViewDiagnosticsArtisanCommandTest', $contents);
    }
}
