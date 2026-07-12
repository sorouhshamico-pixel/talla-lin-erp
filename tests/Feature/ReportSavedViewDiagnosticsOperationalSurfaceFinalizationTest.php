<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Reports\ReportSavedViewDiagnosticsWebLinks;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewDiagnosticsOperationalSurfaceFinalizationTest extends TestCase
{
    public function test_web_links_helper_exposes_snapshot_action_routes(): void
    {
        $this->assertSame([
            'write_markdown' => 'reports.saved-view-diagnostics.snapshots.markdown',
            'write_json' => 'reports.saved-view-diagnostics.snapshots.json',
            'prune' => 'reports.saved-view-diagnostics.snapshots.prune',
        ], ReportSavedViewDiagnosticsWebLinks::snapshotActionRoutes());

        $this->assertSame([
            'write_markdown' => 'Write Markdown Snapshot',
            'write_json' => 'Write JSON Snapshot',
            'prune' => 'Prune Snapshots',
        ], ReportSavedViewDiagnosticsWebLinks::snapshotActionLabels());

        $this->assertCount(3, ReportSavedViewDiagnosticsWebLinks::snapshotActionItems());
    }

    public function test_all_diagnostics_operational_routes_are_registered_and_protected(): void
    {
        foreach (ReportSavedViewDiagnosticsWebLinks::allRoutes() as $routeName) {
            $this->assertTrue(Route::has($routeName));

            $route = Route::getRoutes()->getByName($routeName);

            $this->assertContains('auth', $route->middleware());
        }
    }

    public function test_diagnostics_page_displays_snapshot_route_metadata(): void
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
            ->assertSee('reports.saved-view-diagnostics.snapshots.markdown')
            ->assertSee('reports.saved-view-diagnostics.snapshots.json')
            ->assertSee('reports.saved-view-diagnostics.snapshots.prune')
            ->assertSee('report-saved-view-diagnostics-snapshot-action-links', false);
    }

    public function test_phase_60_operational_surface_finalization_is_documented(): void
    {
        $helper = base_path('app/Support/Reports/ReportSavedViewDiagnosticsWebLinks.php');
        $finalDoc = base_path('docs/phase-60-report-saved-view-diagnostics-operational-surface-finalization.md');
        $doc60a = base_path('docs/phase-60-report-saved-view-diagnostics-navigation-entry.md');
        $doc60b = base_path('docs/phase-60-report-saved-view-diagnostics-web-snapshot-actions.md');
        $view = base_path('resources/views/reports/saved-view-diagnostics.blade.php');

        $this->assertFileExists($helper);
        $this->assertFileExists($finalDoc);
        $this->assertFileExists($doc60a);
        $this->assertFileExists($doc60b);
        $this->assertFileExists($view);

        $finalContents = file_get_contents($finalDoc);
        $doc60aContents = file_get_contents($doc60a);
        $doc60bContents = file_get_contents($doc60b);
        $viewContents = file_get_contents($view);

        $this->assertStringContainsString('Phase 60 is finalized.', $finalContents);
        $this->assertStringContainsString('reports.saved-view-diagnostics.snapshots.markdown', $finalContents);
        $this->assertStringContainsString('ReportSavedViewDiagnosticsOperationalSurfaceFinalizationTest', $finalContents);

        $this->assertStringContainsString('Phase 60C operational finalization', $doc60aContents);
        $this->assertStringContainsString('Phase 60C snapshot action metadata', $doc60bContents);

        $this->assertStringContainsString('report-saved-view-diagnostics-snapshot-action-links', $viewContents);
    }
}
