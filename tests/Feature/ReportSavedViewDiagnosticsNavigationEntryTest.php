<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewDiagnosticsNavigationEntryTest extends TestCase
{
    public function test_diagnostics_routes_remain_discoverable_by_name(): void
    {
        $this->assertTrue(Route::has('reports.saved-view-diagnostics.index'));
        $this->assertTrue(Route::has('reports.saved-view-diagnostics.markdown'));
        $this->assertTrue(Route::has('reports.saved-view-diagnostics.json'));

        $this->assertSame(
            'reports/saved-view-diagnostics',
            Route::getRoutes()->getByName('reports.saved-view-diagnostics.index')->uri()
        );

        $this->assertSame(
            'reports/saved-view-diagnostics/markdown',
            Route::getRoutes()->getByName('reports.saved-view-diagnostics.markdown')->uri()
        );

        $this->assertSame(
            'reports/saved-view-diagnostics/json',
            Route::getRoutes()->getByName('reports.saved-view-diagnostics.json')->uri()
        );
    }

    public function test_diagnostics_web_surface_remains_accessible_to_authenticated_users(): void
    {
        $user = User::factory()->make([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-view-diagnostics.index'))
            ->assertOk()
            ->assertSee('Report Saved View Diagnostics')
            ->assertSee('Web Links')
            ->assertSee('CLI Commands')
            ->assertSee('reports.saved-view-diagnostics.index')
            ->assertSee('reports.saved-view-diagnostics.markdown')
            ->assertSee('reports.saved-view-diagnostics.json');
    }

    public function test_diagnostics_navigation_entry_is_present_or_documented(): void
    {
        $candidateFiles = [
            base_path('resources/views/reports/index.blade.php'),
            base_path('resources/views/reports/center.blade.php'),
            base_path('resources/views/reports/dashboard.blade.php'),
        ];

        $foundNavigationEntry = false;

        foreach ($candidateFiles as $candidateFile) {
            if (! file_exists($candidateFile)) {
                continue;
            }

            $contents = file_get_contents($candidateFile);

            if (str_contains($contents, 'reports.saved-view-diagnostics.index')) {
                $foundNavigationEntry = true;
                break;
            }
        }

        $doc = base_path('docs/phase-60-report-saved-view-diagnostics-navigation-entry.md');

        $this->assertFileExists($doc);

        $docContents = file_get_contents($doc);

        $this->assertTrue(
            $foundNavigationEntry || str_contains($docContents, 'No existing report navigation view required an update.')
        );

        $this->assertStringContainsString('reports.saved-view-diagnostics.index', $docContents);
    }

    public function test_phase_60a_navigation_entry_is_documented(): void
    {
        $doc = base_path('docs/phase-60-report-saved-view-diagnostics-navigation-entry.md');
        $doc59c = base_path('docs/phase-59-report-saved-view-diagnostics-web-surface-finalization.md');

        $this->assertFileExists($doc);
        $this->assertFileExists($doc59c);

        $contents = file_get_contents($doc);
        $contents59c = file_get_contents($doc59c);

        $this->assertStringContainsString('Phase 60A', $contents);
        $this->assertStringContainsString('Report Saved View Diagnostics Navigation Entry', $contents);
        $this->assertStringContainsString('reports.saved-view-diagnostics.index', $contents);
        $this->assertStringContainsString('ReportSavedViewDiagnosticsNavigationEntryTest', $contents);

        $this->assertStringContainsString('Phase 60A navigation handoff', $contents59c);
        $this->assertStringContainsString('reports.saved-view-diagnostics.markdown', $contents59c);
        $this->assertStringContainsString('reports.saved-view-diagnostics.json', $contents59c);
    }
}
