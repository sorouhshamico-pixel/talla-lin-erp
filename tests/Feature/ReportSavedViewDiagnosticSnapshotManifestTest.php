<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewDiagnosticSnapshotExporter;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ReportSavedViewDiagnosticSnapshotManifestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteDiagnosticSnapshotDirectory();
    }

    protected function tearDown(): void
    {
        $this->deleteDiagnosticSnapshotDirectory();

        parent::tearDown();
    }

    public function test_snapshot_exporter_writes_manifest_for_markdown_exports(): void
    {
        $snapshot = ReportSavedViewDiagnosticSnapshotExporter::exportMarkdown('manifest-markdown-test.md');

        $this->assertSame('report-saved-view-diagnostics/manifest.json', $snapshot['manifest_relative_path']);
        $this->assertFileExists($snapshot['manifest_absolute_path']);

        $manifest = ReportSavedViewDiagnosticSnapshotExporter::manifest();

        $this->assertSame('report-saved-view-diagnostics', $manifest['directory']);
        $this->assertNotNull($manifest['updated_at']);
        $this->assertArrayHasKey('markdown', $manifest['latest']);
        $this->assertSame('manifest-markdown-test.md', $manifest['latest']['markdown']['filename']);
        $this->assertSame('report-saved-view-diagnostics/manifest-markdown-test.md', $manifest['latest']['markdown']['relative_path']);
        $this->assertTrue($manifest['latest']['markdown']['healthy']);
        $this->assertSame(11, $manifest['latest']['markdown']['report_count']);
        $this->assertSame(0, $manifest['latest']['markdown']['invalid_count']);
        $this->assertCount(1, $manifest['history']);
    }

    public function test_snapshot_exporter_writes_manifest_for_json_exports(): void
    {
        ReportSavedViewDiagnosticSnapshotExporter::exportJson('manifest-json-test.json');

        $manifest = ReportSavedViewDiagnosticSnapshotExporter::manifest();

        $this->assertArrayHasKey('json', $manifest['latest']);
        $this->assertSame('manifest-json-test.json', $manifest['latest']['json']['filename']);
        $this->assertSame('json', $manifest['latest']['json']['format']);
        $this->assertSame('report-saved-view-diagnostics/manifest-json-test.json', $manifest['latest']['json']['relative_path']);
        $this->assertTrue($manifest['latest']['json']['healthy']);
        $this->assertSame(11, $manifest['latest']['json']['report_count']);
        $this->assertSame(0, $manifest['latest']['json']['invalid_count']);
    }

    public function test_manifest_tracks_latest_snapshot_per_format_and_history(): void
    {
        ReportSavedViewDiagnosticSnapshotExporter::exportMarkdown('first.md');
        ReportSavedViewDiagnosticSnapshotExporter::exportJson('first.json');
        ReportSavedViewDiagnosticSnapshotExporter::exportMarkdown('second.md');

        $manifest = ReportSavedViewDiagnosticSnapshotExporter::manifest();

        $this->assertSame('second.md', $manifest['latest']['markdown']['filename']);
        $this->assertSame('first.json', $manifest['latest']['json']['filename']);
        $this->assertCount(3, $manifest['history']);
    }

    public function test_manifest_helpers_return_expected_paths_and_empty_manifest(): void
    {
        $this->assertSame(
            storage_path('app/report-saved-view-diagnostics/manifest.json'),
            ReportSavedViewDiagnosticSnapshotExporter::manifestPath()
        );

        $this->assertSame(
            'report-saved-view-diagnostics/manifest.json',
            ReportSavedViewDiagnosticSnapshotExporter::manifestRelativePath()
        );

        $manifest = ReportSavedViewDiagnosticSnapshotExporter::manifest();

        $this->assertSame('report-saved-view-diagnostics', $manifest['directory']);
        $this->assertNull($manifest['updated_at']);
        $this->assertSame([], $manifest['latest']);
        $this->assertSame([], $manifest['history']);
    }

    public function test_artisan_snapshot_write_updates_manifest(): void
    {
        $exitCode = Artisan::call('reports:saved-view-diagnostics', [
            '--write' => true,
            '--format' => 'json',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists(ReportSavedViewDiagnosticSnapshotExporter::manifestPath());

        $manifest = ReportSavedViewDiagnosticSnapshotExporter::manifest();

        $this->assertArrayHasKey('json', $manifest['latest']);
        $this->assertSame('report-saved-view-diagnostics.json', $manifest['latest']['json']['filename']);
        $this->assertSame('report-saved-view-diagnostics/report-saved-view-diagnostics.json', $manifest['latest']['json']['relative_path']);
    }

    public function test_phase_58b_snapshot_manifest_is_documented(): void
    {
        $doc = base_path('docs/phase-58-report-saved-view-diagnostic-snapshot-manifest.md');
        $doc58a = base_path('docs/phase-58-report-saved-view-diagnostic-snapshot-export.md');

        $this->assertFileExists($doc);
        $this->assertFileExists($doc58a);

        $contents = file_get_contents($doc);
        $contents58a = file_get_contents($doc58a);

        $this->assertStringContainsString('Phase 58B', $contents);
        $this->assertStringContainsString('Report Saved View Diagnostic Snapshot Manifest', $contents);
        $this->assertStringContainsString('manifest.json', $contents);
        $this->assertStringContainsString('manifestPath', $contents);
        $this->assertStringContainsString('manifestRelativePath', $contents);
        $this->assertStringContainsString('ReportSavedViewDiagnosticSnapshotManifestTest', $contents);

        $this->assertStringContainsString('Phase 58B manifest integration', $contents58a);
        $this->assertStringContainsString('storage/app/report-saved-view-diagnostics/manifest.json', $contents58a);
    }

    private function deleteDiagnosticSnapshotDirectory(): void
    {
        $directory = storage_path('app/report-saved-view-diagnostics');

        if (! is_dir($directory)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($directory);
    }
}
