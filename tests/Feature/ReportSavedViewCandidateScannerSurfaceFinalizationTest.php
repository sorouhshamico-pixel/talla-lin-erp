<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Reports\ReportSavedViewCandidateScannerWebLinks;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewCandidateScannerSurfaceFinalizationTest extends TestCase
{
    public function test_candidate_scanner_web_links_helper_exposes_expected_routes_and_labels(): void
    {
        $this->assertSame([
            'index' => 'reports.saved-view-candidates.index',
            'markdown' => 'reports.saved-view-candidates.markdown',
            'json' => 'reports.saved-view-candidates.json',
            'diagnostics' => 'reports.saved-view-diagnostics.index',
        ], ReportSavedViewCandidateScannerWebLinks::routes());

        $this->assertSame([
            'index' => 'Candidate Scanner Page',
            'markdown' => 'Candidate Scanner Markdown Export',
            'json' => 'Candidate Scanner JSON Export',
            'diagnostics' => 'Diagnostics Page',
        ], ReportSavedViewCandidateScannerWebLinks::labels());

        $this->assertCount(4, ReportSavedViewCandidateScannerWebLinks::items());
    }

    public function test_candidate_scanner_web_links_helper_exposes_cli_commands(): void
    {
        $this->assertSame([
            'php artisan reports:saved-view-candidates',
            'php artisan reports:saved-view-candidates --json',
        ], ReportSavedViewCandidateScannerWebLinks::commandExamples());
    }

    public function test_candidate_scanner_routes_are_registered_and_protected(): void
    {
        foreach (ReportSavedViewCandidateScannerWebLinks::routes() as $routeName) {
            $this->assertTrue(Route::has($routeName));

            $route = Route::getRoutes()->getByName($routeName);

            $this->assertContains('auth', $route->middleware());
        }
    }

    public function test_candidate_scanner_page_displays_web_links_and_cli_commands(): void
    {
        $user = User::factory()->make([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-view-candidates.index'))
            ->assertOk()
            ->assertSee('Web Links')
            ->assertSee('CLI Commands')
            ->assertSee('Candidate Scanner Page')
            ->assertSee('Candidate Scanner Markdown Export')
            ->assertSee('Candidate Scanner JSON Export')
            ->assertSee('Diagnostics Page')
            ->assertSee('reports.saved-view-candidates.index')
            ->assertSee('reports.saved-view-candidates.markdown')
            ->assertSee('reports.saved-view-candidates.json')
            ->assertSee('reports.saved-view-diagnostics.index')
            ->assertSee('php artisan reports:saved-view-candidates')
            ->assertSee('php artisan reports:saved-view-candidates --json')
            ->assertSee('report-saved-view-candidates-web-links', false)
            ->assertSee('report-saved-view-candidates-cli-commands', false);
    }

    public function test_phase_61_candidate_scanner_surface_finalization_is_documented(): void
    {
        $helper = base_path('app/Support/Reports/ReportSavedViewCandidateScannerWebLinks.php');
        $finalDoc = base_path('docs/phase-61-report-saved-view-candidate-scanner-surface-finalization.md');
        $doc61a = base_path('docs/phase-61-report-saved-view-candidate-scanner.md');
        $doc61b = base_path('docs/phase-61-report-saved-view-candidate-scanner-web-surface.md');
        $view = base_path('resources/views/reports/saved-view-candidates.blade.php');

        $this->assertFileExists($helper);
        $this->assertFileExists($finalDoc);
        $this->assertFileExists($doc61a);
        $this->assertFileExists($doc61b);
        $this->assertFileExists($view);

        $finalContents = file_get_contents($finalDoc);
        $doc61aContents = file_get_contents($doc61a);
        $doc61bContents = file_get_contents($doc61b);
        $viewContents = file_get_contents($view);

        $this->assertStringContainsString('Phase 61 is finalized.', $finalContents);
        $this->assertStringContainsString('ReportSavedViewCandidateScannerWebLinks.php', $finalContents);
        $this->assertStringContainsString('ReportSavedViewCandidateScannerSurfaceFinalizationTest', $finalContents);

        $this->assertStringContainsString('Phase 61C candidate scanner finalization', $doc61aContents);
        $this->assertStringContainsString('Phase 61C web link metadata', $doc61bContents);

        $this->assertStringContainsString('report-saved-view-candidates-web-links', $viewContents);
        $this->assertStringContainsString('report-saved-view-candidates-cli-commands', $viewContents);
    }
}
