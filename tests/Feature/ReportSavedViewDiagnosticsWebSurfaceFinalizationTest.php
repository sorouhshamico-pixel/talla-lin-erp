<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Reports\ReportSavedViewDiagnosticsWebLinks;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewDiagnosticsWebSurfaceFinalizationTest extends TestCase
{
    public function test_diagnostics_web_links_helper_exposes_expected_routes_and_labels(): void
    {
        $this->assertSame([
            'index' => 'reports.saved-view-diagnostics.index',
            'markdown' => 'reports.saved-view-diagnostics.markdown',
            'json' => 'reports.saved-view-diagnostics.json',
        ], ReportSavedViewDiagnosticsWebLinks::routes());

        $this->assertSame([
            'index' => 'Diagnostics Page',
            'markdown' => 'Markdown Export',
            'json' => 'JSON Export',
        ], ReportSavedViewDiagnosticsWebLinks::labels());

        $items = ReportSavedViewDiagnosticsWebLinks::items();

        $this->assertCount(3, $items);
        $this->assertSame('index', $items[0]['key']);
        $this->assertSame('Diagnostics Page', $items[0]['label']);
        $this->assertSame('reports.saved-view-diagnostics.index', $items[0]['route']);
    }

    public function test_diagnostics_web_links_helper_exposes_cli_command_examples(): void
    {
        $commands = ReportSavedViewDiagnosticsWebLinks::commandExamples();

        $this->assertContains('php artisan reports:saved-view-diagnostics', $commands);
        $this->assertContains('php artisan reports:saved-view-diagnostics --json', $commands);
        $this->assertContains('php artisan reports:saved-view-diagnostics --write', $commands);
        $this->assertContains('php artisan reports:saved-view-diagnostics --write --format=json', $commands);
        $this->assertContains('php artisan reports:saved-view-diagnostics --prune', $commands);
        $this->assertContains('php artisan reports:saved-view-diagnostics --prune --include-manifest', $commands);
    }

    public function test_diagnostics_web_surface_routes_are_registered_and_protected(): void
    {
        foreach (ReportSavedViewDiagnosticsWebLinks::routes() as $routeName) {
            $this->assertTrue(Route::has($routeName));

            $route = Route::getRoutes()->getByName($routeName);

            $this->assertContains('auth', $route->middleware());
        }
    }

    public function test_diagnostics_web_page_displays_link_metadata_and_cli_commands(): void
    {
        $user = User::factory()->make([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-view-diagnostics.index'))
            ->assertOk()
            ->assertSee('Web Links')
            ->assertSee('CLI Commands')
            ->assertSee('Diagnostics Page')
            ->assertSee('Markdown Export')
            ->assertSee('JSON Export')
            ->assertSee('reports.saved-view-diagnostics.index')
            ->assertSee('reports.saved-view-diagnostics.markdown')
            ->assertSee('reports.saved-view-diagnostics.json')
            ->assertSee('php artisan reports:saved-view-diagnostics')
            ->assertSee('php artisan reports:saved-view-diagnostics --json')
            ->assertSee('php artisan reports:saved-view-diagnostics --write')
            ->assertSee('php artisan reports:saved-view-diagnostics --write --format=json')
            ->assertSee('php artisan reports:saved-view-diagnostics --prune')
            ->assertSee('php artisan reports:saved-view-diagnostics --prune --include-manifest');
    }

    public function test_phase_59_web_surface_finalization_is_documented(): void
    {
        $helper = base_path('app/Support/Reports/ReportSavedViewDiagnosticsWebLinks.php');
        $finalDoc = base_path('docs/phase-59-report-saved-view-diagnostics-web-surface-finalization.md');
        $doc59a = base_path('docs/phase-59-report-saved-view-diagnostics-web-view.md');
        $doc59b = base_path('docs/phase-59-report-saved-view-diagnostics-web-export-endpoints.md');
        $view = base_path('resources/views/reports/saved-view-diagnostics.blade.php');

        $this->assertFileExists($helper);
        $this->assertFileExists($finalDoc);
        $this->assertFileExists($doc59a);
        $this->assertFileExists($doc59b);
        $this->assertFileExists($view);

        $finalContents = file_get_contents($finalDoc);
        $doc59aContents = file_get_contents($doc59a);
        $doc59bContents = file_get_contents($doc59b);
        $viewContents = file_get_contents($view);

        $this->assertStringContainsString('Phase 59 is finalized.', $finalContents);
        $this->assertStringContainsString('ReportSavedViewDiagnosticsWebLinks.php', $finalContents);
        $this->assertStringContainsString('reports.saved-view-diagnostics.markdown', $finalContents);
        $this->assertStringContainsString('ReportSavedViewDiagnosticsWebSurfaceFinalizationTest', $finalContents);

        $this->assertStringContainsString('Phase 59C web surface finalization', $doc59aContents);
        $this->assertStringContainsString('Phase 59C link integration', $doc59bContents);

        $this->assertStringContainsString('report-saved-view-diagnostics-web-links', $viewContents);
        $this->assertStringContainsString('report-saved-view-diagnostics-cli-commands', $viewContents);
    }
}
