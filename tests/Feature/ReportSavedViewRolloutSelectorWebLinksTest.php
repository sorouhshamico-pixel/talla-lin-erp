<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Reports\ReportSavedViewRolloutSelectorWebLinks;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewRolloutSelectorWebLinksTest extends TestCase
{
    public function test_rollout_selector_web_links_helper_exposes_expected_routes_and_labels(): void
    {
        $this->assertSame([
            'index' => 'reports.saved-view-rollout-selector.index',
            'markdown' => 'reports.saved-view-rollout-selector.markdown',
            'json' => 'reports.saved-view-rollout-selector.json',
            'candidates' => 'reports.saved-view-candidates.index',
            'diagnostics' => 'reports.saved-view-diagnostics.index',
        ], ReportSavedViewRolloutSelectorWebLinks::routes());

        $this->assertSame([
            'index' => 'Rollout Selector Page',
            'markdown' => 'Rollout Selector Markdown Export',
            'json' => 'Rollout Selector JSON Export',
            'candidates' => 'Candidate Scanner Page',
            'diagnostics' => 'Diagnostics Page',
        ], ReportSavedViewRolloutSelectorWebLinks::labels());

        $this->assertCount(5, ReportSavedViewRolloutSelectorWebLinks::items());
    }

    public function test_rollout_selector_web_links_helper_exposes_cli_commands(): void
    {
        $this->assertSame([
            'php artisan reports:saved-view-rollout-selector',
            'php artisan reports:saved-view-rollout-selector --json',
            'php artisan reports:saved-view-candidates',
            'php artisan reports:saved-view-diagnostics',
        ], ReportSavedViewRolloutSelectorWebLinks::commandExamples());
    }

    public function test_rollout_selector_linked_routes_are_registered_and_protected(): void
    {
        foreach (ReportSavedViewRolloutSelectorWebLinks::routes() as $routeName) {
            $this->assertTrue(Route::has($routeName));

            $route = Route::getRoutes()->getByName($routeName);

            $this->assertContains('auth', $route->middleware());
        }
    }

    public function test_rollout_selector_page_displays_web_links_and_cli_commands(): void
    {
        $user = User::factory()->make([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-view-rollout-selector.index'))
            ->assertOk()
            ->assertSee('Web Links')
            ->assertSee('CLI Commands')
            ->assertSee('Rollout Selector Page')
            ->assertSee('Rollout Selector Markdown Export')
            ->assertSee('Rollout Selector JSON Export')
            ->assertSee('Candidate Scanner Page')
            ->assertSee('Diagnostics Page')
            ->assertSee('reports.saved-view-rollout-selector.index')
            ->assertSee('reports.saved-view-rollout-selector.markdown')
            ->assertSee('reports.saved-view-rollout-selector.json')
            ->assertSee('reports.saved-view-candidates.index')
            ->assertSee('reports.saved-view-diagnostics.index')
            ->assertSee('php artisan reports:saved-view-rollout-selector')
            ->assertSee('php artisan reports:saved-view-rollout-selector --json')
            ->assertSee('report-saved-view-rollout-selector-web-links', false)
            ->assertSee('report-saved-view-rollout-selector-cli-commands', false);
    }

    public function test_candidate_scanner_page_links_to_rollout_selector(): void
    {
        $user = User::factory()->make([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-view-candidates.index'))
            ->assertOk()
            ->assertSee('Rollout Selector')
            ->assertSee(route('reports.saved-view-rollout-selector.index'), false);
    }

    public function test_phase_62b_rollout_selector_web_links_are_documented(): void
    {
        $helper = base_path('app/Support/Reports/ReportSavedViewRolloutSelectorWebLinks.php');
        $doc = base_path('docs/phase-62-report-saved-view-rollout-selector-web-links.md');
        $doc62a = base_path('docs/phase-62-report-saved-view-rollout-selector.md');
        $view = base_path('resources/views/reports/saved-view-rollout-selector.blade.php');

        $this->assertFileExists($helper);
        $this->assertFileExists($doc);
        $this->assertFileExists($doc62a);
        $this->assertFileExists($view);

        $contents = file_get_contents($doc);
        $contents62a = file_get_contents($doc62a);
        $viewContents = file_get_contents($view);

        $this->assertStringContainsString('Phase 62B', $contents);
        $this->assertStringContainsString('Rollout Selector Web Links And Navigation', $contents);
        $this->assertStringContainsString('ReportSavedViewRolloutSelectorWebLinksTest', $contents);
        $this->assertStringContainsString('Phase 62B web links and navigation', $contents62a);
        $this->assertStringContainsString('report-saved-view-rollout-selector-web-links', $viewContents);
        $this->assertStringContainsString('report-saved-view-rollout-selector-cli-commands', $viewContents);
    }
}
