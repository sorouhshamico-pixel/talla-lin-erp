<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Reports\ReportSavedViewDiagnosticSnapshotExporter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewDiagnosticsWebSnapshotActionsTest extends TestCase
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

    public function test_snapshot_action_routes_are_registered_and_protected(): void
    {
        $routeNames = [
            'reports.saved-view-diagnostics.snapshots.markdown',
            'reports.saved-view-diagnostics.snapshots.json',
            'reports.saved-view-diagnostics.snapshots.prune',
        ];

        foreach ($routeNames as $routeName) {
            $this->assertTrue(Route::has($routeName));

            $route = Route::getRoutes()->getByName($routeName);

            $this->assertContains('auth', $route->middleware());
        }
    }

    public function test_snapshot_action_routes_require_authentication(): void
    {
        $this->post(route('reports.saved-view-diagnostics.snapshots.markdown'))
            ->assertRedirect();

        $this->post(route('reports.saved-view-diagnostics.snapshots.json'))
            ->assertRedirect();

        $this->post(route('reports.saved-view-diagnostics.snapshots.prune'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_write_markdown_snapshot_from_web(): void
    {
        $user = User::factory()->make([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-view-diagnostics.snapshots.markdown'))
            ->assertRedirect(route('reports.saved-view-diagnostics.index'))
            ->assertSessionHas('status');

        $this->assertFileExists(storage_path('app/report-saved-view-diagnostics/report-saved-view-diagnostics.md'));

        $contents = file_get_contents(storage_path('app/report-saved-view-diagnostics/report-saved-view-diagnostics.md'));

        $this->assertStringContainsString('# Report Saved View Registry Diagnostic Report', $contents);
    }

    public function test_authenticated_user_can_write_json_snapshot_from_web(): void
    {
        $user = User::factory()->make([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-view-diagnostics.snapshots.json'))
            ->assertRedirect(route('reports.saved-view-diagnostics.index'))
            ->assertSessionHas('status');

        $path = storage_path('app/report-saved-view-diagnostics/report-saved-view-diagnostics.json');

        $this->assertFileExists($path);

        $decoded = json_decode(file_get_contents($path), true);

        $this->assertSame('Report Saved View Registry Diagnostic Report', $decoded['title']);
        $this->assertSame(11, $decoded['summary']['report_count']);
    }

    public function test_authenticated_user_can_prune_snapshots_from_web(): void
    {
        ReportSavedViewDiagnosticSnapshotExporter::exportMarkdown('web-prune.md');

        $this->assertFileExists(storage_path('app/report-saved-view-diagnostics/web-prune.md'));
        $this->assertFileExists(ReportSavedViewDiagnosticSnapshotExporter::manifestPath());

        $user = User::factory()->make([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-view-diagnostics.snapshots.prune'))
            ->assertRedirect(route('reports.saved-view-diagnostics.index'))
            ->assertSessionHas('status');

        $this->assertFileDoesNotExist(storage_path('app/report-saved-view-diagnostics/web-prune.md'));
        $this->assertFileExists(ReportSavedViewDiagnosticSnapshotExporter::manifestPath());
    }

    public function test_authenticated_user_can_prune_snapshots_and_manifest_from_web(): void
    {
        ReportSavedViewDiagnosticSnapshotExporter::exportJson('web-prune-all.json');

        $this->assertFileExists(ReportSavedViewDiagnosticSnapshotExporter::manifestPath());

        $user = User::factory()->make([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-view-diagnostics.snapshots.prune'), [
                'include_manifest' => '1',
            ])
            ->assertRedirect(route('reports.saved-view-diagnostics.index'))
            ->assertSessionHas('status');

        $this->assertFileDoesNotExist(ReportSavedViewDiagnosticSnapshotExporter::manifestPath());
    }

    public function test_diagnostics_page_displays_snapshot_action_forms(): void
    {
        $user = User::factory()->make([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-view-diagnostics.index'))
            ->assertOk()
            ->assertSee('Snapshot Actions')
            ->assertSee('Write Markdown Snapshot')
            ->assertSee('Write JSON Snapshot')
            ->assertSee('Prune Snapshots')
            ->assertSee('Prune Snapshots And Manifest')
            ->assertSee(route('reports.saved-view-diagnostics.snapshots.markdown'), false)
            ->assertSee(route('reports.saved-view-diagnostics.snapshots.json'), false)
            ->assertSee(route('reports.saved-view-diagnostics.snapshots.prune'), false);
    }

    public function test_phase_60b_web_snapshot_actions_are_documented(): void
    {
        $doc = base_path('docs/phase-60-report-saved-view-diagnostics-web-snapshot-actions.md');
        $doc60a = base_path('docs/phase-60-report-saved-view-diagnostics-navigation-entry.md');
        $view = base_path('resources/views/reports/saved-view-diagnostics.blade.php');

        $this->assertFileExists($doc);
        $this->assertFileExists($doc60a);
        $this->assertFileExists($view);

        $contents = file_get_contents($doc);
        $contents60a = file_get_contents($doc60a);
        $viewContents = file_get_contents($view);

        $this->assertStringContainsString('Phase 60B', $contents);
        $this->assertStringContainsString('Report Saved View Diagnostics Web Snapshot Actions', $contents);
        $this->assertStringContainsString('reports.saved-view-diagnostics.snapshots.markdown', $contents);
        $this->assertStringContainsString('reports.saved-view-diagnostics.snapshots.json', $contents);
        $this->assertStringContainsString('reports.saved-view-diagnostics.snapshots.prune', $contents);
        $this->assertStringContainsString('ReportSavedViewDiagnosticsWebSnapshotActionsTest', $contents);

        $this->assertStringContainsString('Phase 60B web snapshot actions', $contents60a);

        $this->assertStringContainsString('report-saved-view-diagnostics-snapshot-actions', $viewContents);
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
