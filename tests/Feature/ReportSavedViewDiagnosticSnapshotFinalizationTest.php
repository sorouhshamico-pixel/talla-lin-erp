<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewDiagnosticSnapshotExporter;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ReportSavedViewDiagnosticSnapshotFinalizationTest extends TestCase
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

    public function test_snapshot_exporter_prunes_snapshots_while_preserving_manifest_by_default(): void
    {
        $markdown = ReportSavedViewDiagnosticSnapshotExporter::exportMarkdown('finalization-prune.md');
        $json = ReportSavedViewDiagnosticSnapshotExporter::exportJson('finalization-prune.json');

        $this->assertFileExists($markdown['absolute_path']);
        $this->assertFileExists($json['absolute_path']);
        $this->assertFileExists(ReportSavedViewDiagnosticSnapshotExporter::manifestPath());

        $result = ReportSavedViewDiagnosticSnapshotExporter::pruneSnapshots();

        $this->assertSame('report-saved-view-diagnostics', $result['directory']);
        $this->assertSame(2, $result['deleted_count']);
        $this->assertTrue($result['manifest_preserved']);
        $this->assertContains('report-saved-view-diagnostics/finalization-prune.md', $result['deleted_files']);
        $this->assertContains('report-saved-view-diagnostics/finalization-prune.json', $result['deleted_files']);

        $this->assertFileDoesNotExist($markdown['absolute_path']);
        $this->assertFileDoesNotExist($json['absolute_path']);
        $this->assertFileExists(ReportSavedViewDiagnosticSnapshotExporter::manifestPath());
    }

    public function test_snapshot_exporter_can_prune_manifest_when_requested(): void
    {
        ReportSavedViewDiagnosticSnapshotExporter::exportMarkdown('finalization-prune-all.md');
        ReportSavedViewDiagnosticSnapshotExporter::exportJson('finalization-prune-all.json');

        $this->assertFileExists(ReportSavedViewDiagnosticSnapshotExporter::manifestPath());

        $result = ReportSavedViewDiagnosticSnapshotExporter::pruneSnapshots(includeManifest: true);

        $this->assertSame(3, $result['deleted_count']);
        $this->assertFalse($result['manifest_preserved']);
        $this->assertContains('report-saved-view-diagnostics/manifest.json', $result['deleted_files']);
        $this->assertFileDoesNotExist(ReportSavedViewDiagnosticSnapshotExporter::manifestPath());
    }

    public function test_artisan_command_prunes_snapshots_and_preserves_manifest(): void
    {
        ReportSavedViewDiagnosticSnapshotExporter::exportMarkdown('command-prune.md');

        $this->assertFileExists(storage_path('app/report-saved-view-diagnostics/command-prune.md'));
        $this->assertFileExists(ReportSavedViewDiagnosticSnapshotExporter::manifestPath());

        $exitCode = Artisan::call('reports:saved-view-diagnostics', [
            '--prune' => true,
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Report saved view diagnostic snapshots pruned: 1', $output);
        $this->assertStringContainsString('Manifest preserved: yes', $output);
        $this->assertFileDoesNotExist(storage_path('app/report-saved-view-diagnostics/command-prune.md'));
        $this->assertFileExists(ReportSavedViewDiagnosticSnapshotExporter::manifestPath());
    }

    public function test_artisan_command_can_prune_manifest(): void
    {
        ReportSavedViewDiagnosticSnapshotExporter::exportJson('command-prune-all.json');

        $exitCode = Artisan::call('reports:saved-view-diagnostics', [
            '--prune' => true,
            '--include-manifest' => true,
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Report saved view diagnostic snapshots pruned: 2', $output);
        $this->assertStringContainsString('Manifest preserved: no', $output);
        $this->assertFileDoesNotExist(ReportSavedViewDiagnosticSnapshotExporter::manifestPath());
    }

    public function test_phase_58_snapshot_finalization_is_documented(): void
    {
        $finalDoc = base_path('docs/phase-58-report-saved-view-diagnostic-snapshot-finalization.md');
        $doc58a = base_path('docs/phase-58-report-saved-view-diagnostic-snapshot-export.md');
        $doc58b = base_path('docs/phase-58-report-saved-view-diagnostic-snapshot-manifest.md');
        $consoleRoutes = base_path('routes/console.php');

        $this->assertFileExists($finalDoc);
        $this->assertFileExists($doc58a);
        $this->assertFileExists($doc58b);
        $this->assertFileExists($consoleRoutes);

        $finalContents = file_get_contents($finalDoc);
        $doc58aContents = file_get_contents($doc58a);
        $doc58bContents = file_get_contents($doc58b);
        $consoleContents = file_get_contents($consoleRoutes);

        $this->assertStringContainsString('Phase 58 is finalized.', $finalContents);
        $this->assertStringContainsString('snapshot pruning', $finalContents);
        $this->assertStringContainsString('--prune', $finalContents);
        $this->assertStringContainsString('--include-manifest', $finalContents);
        $this->assertStringContainsString('ReportSavedViewDiagnosticSnapshotFinalizationTest', $finalContents);

        $this->assertStringContainsString('Phase 58C pruning integration', $doc58aContents);
        $this->assertStringContainsString('Manifest preservation during pruning', $doc58bContents);

        $this->assertStringContainsString('--prune', $consoleContents);
        $this->assertStringContainsString('--include-manifest', $consoleContents);
        $this->assertStringContainsString('pruneSnapshots', $consoleContents);
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
