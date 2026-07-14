<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewDiagnosticSnapshotExporter;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;
use Tests\TestCase;

class ReportSavedViewDiagnosticSnapshotExportTest extends TestCase
{
    public function test_snapshot_exporter_writes_markdown_snapshot(): void
    {
        $snapshot = ReportSavedViewDiagnosticSnapshotExporter::exportMarkdown('test-report-saved-view-diagnostics.md');

        $this->assertSame('markdown', $snapshot['format']);
        $this->assertSame('test-report-saved-view-diagnostics.md', $snapshot['filename']);
        $this->assertSame('report-saved-view-diagnostics/test-report-saved-view-diagnostics.md', $snapshot['relative_path']);
        $this->assertFileExists($snapshot['absolute_path']);

        $contents = file_get_contents($snapshot['absolute_path']);

        $this->assertStringContainsString('# Report Saved View Registry Diagnostic Report', $contents);
        $this->assertStringContainsString('- Report count: 7', $contents);
        $this->assertStringContainsString('### sales-invoice-aging', $contents);
        $this->assertStringContainsString('### customer-sales-invoice-aging', $contents);
        $this->assertStringContainsString('### customer-sales-invoice-aging', $contents);
    }

    public function test_snapshot_exporter_writes_json_snapshot(): void
    {
        $snapshot = ReportSavedViewDiagnosticSnapshotExporter::exportJson('test-report-saved-view-diagnostics.json');

        $this->assertSame('json', $snapshot['format']);
        $this->assertSame('test-report-saved-view-diagnostics.json', $snapshot['filename']);
        $this->assertSame('report-saved-view-diagnostics/test-report-saved-view-diagnostics.json', $snapshot['relative_path']);
        $this->assertFileExists($snapshot['absolute_path']);

        $decoded = json_decode(file_get_contents($snapshot['absolute_path']), true);

        $this->assertIsArray($decoded);
        $this->assertSame('Report Saved View Registry Diagnostic Report', $decoded['title']);
        $this->assertSame(7, $decoded['summary']['report_count']);
        $this->assertSame(0, $decoded['summary']['invalid_count']);
        $this->assertTrue($decoded['summary']['valid']);
        $this->assertContains('sales-invoice-aging', $decoded['valid_report_keys']);
        $this->assertContains('customer-sales-invoice-aging', $decoded['valid_report_keys']);
    }

    public function test_snapshot_exporter_rejects_invalid_format(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ReportSavedViewDiagnosticSnapshotExporter::export('xml');
    }

    public function test_artisan_command_writes_markdown_snapshot(): void
    {
        $exitCode = Artisan::call('reports:saved-view-diagnostics', [
            '--write' => true,
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Report saved view diagnostics snapshot written to:', $output);
        $this->assertStringContainsString('report-saved-view-diagnostics/report-saved-view-diagnostics.md', $output);

        $this->assertFileExists(storage_path('app/report-saved-view-diagnostics/report-saved-view-diagnostics.md'));
    }

    public function test_artisan_command_writes_json_snapshot_with_format_option(): void
    {
        $exitCode = Artisan::call('reports:saved-view-diagnostics', [
            '--write' => true,
            '--format' => 'json',
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('report-saved-view-diagnostics/report-saved-view-diagnostics.json', $output);

        $path = storage_path('app/report-saved-view-diagnostics/report-saved-view-diagnostics.json');

        $this->assertFileExists($path);

        $decoded = json_decode(file_get_contents($path), true);

        $this->assertSame('Report Saved View Registry Diagnostic Report', $decoded['title']);
    }

    public function test_artisan_command_writes_json_snapshot_with_json_shortcut(): void
    {
        $exitCode = Artisan::call('reports:saved-view-diagnostics', [
            '--write' => true,
            '--json' => true,
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('report-saved-view-diagnostics/report-saved-view-diagnostics.json', $output);
    }

    public function test_phase_58a_snapshot_export_is_documented(): void
    {
        $doc = base_path('docs/phase-58-report-saved-view-diagnostic-snapshot-export.md');
        $consoleRoutes = base_path('routes/console.php');

        $this->assertFileExists($doc);
        $this->assertFileExists($consoleRoutes);

        $contents = file_get_contents($doc);
        $consoleContents = file_get_contents($consoleRoutes);

        $this->assertStringContainsString('Phase 58A', $contents);
        $this->assertStringContainsString('Report Saved View Diagnostic Snapshot Export', $contents);
        $this->assertStringContainsString('ReportSavedViewDiagnosticSnapshotExporter.php', $contents);
        $this->assertStringContainsString('php artisan reports:saved-view-diagnostics --write', $contents);
        $this->assertStringContainsString('ReportSavedViewDiagnosticSnapshotExportTest', $contents);

        $this->assertStringContainsString('ReportSavedViewDiagnosticSnapshotExporter', $consoleContents);
        $this->assertStringContainsString('--write', $consoleContents);
        $this->assertStringContainsString('--format=markdown', $consoleContents);
    }
}
