<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Reports\ReportSavedViewRolloutSelectorWebLinks;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewRolloutSelectorSurfaceFinalizationTest extends TestCase
{
    public function test_rollout_selector_web_links_helper_exposes_workflow_steps(): void
    {
        $steps = ReportSavedViewRolloutSelectorWebLinks::workflowSteps();

        $this->assertIsArray($steps);
        $this->assertCount(6, $steps);
        $this->assertContains('Open the rollout selector page.', $steps);
        $this->assertContains('Review the next candidate and its priority score.', $steps);
        $this->assertContains('Run diagnostics again after the rollout.', $steps);
    }

    public function test_rollout_selector_routes_remain_registered_and_protected(): void
    {
        foreach ([
            'reports.saved-view-rollout-selector.index',
            'reports.saved-view-rollout-selector.markdown',
            'reports.saved-view-rollout-selector.json',
        ] as $routeName) {
            $this->assertTrue(Route::has($routeName));

            $route = Route::getRoutes()->getByName($routeName);

            $this->assertContains('auth', $route->middleware());
        }
    }

    public function test_rollout_selector_page_displays_workflow_metadata(): void
    {
        $user = User::factory()->make([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-view-rollout-selector.index'))
            ->assertOk()
            ->assertSee('Rollout Workflow')
            ->assertSee('Open the rollout selector page.')
            ->assertSee('Review the next candidate and its priority score.')
            ->assertSee('Open the candidate scanner for full candidate context.')
            ->assertSee('Open diagnostics before implementation to confirm registry health.')
            ->assertSee('Implement saved view controls for the selected report.')
            ->assertSee('Run diagnostics again after the rollout.')
            ->assertSee('report-saved-view-rollout-selector-workflow', false);
    }

    public function test_phase_62_rollout_selector_surface_finalization_is_documented(): void
    {
        $finalDoc = base_path('docs/phase-62-report-saved-view-rollout-selector-surface-finalization.md');
        $doc62a = base_path('docs/phase-62-report-saved-view-rollout-selector.md');
        $doc62b = base_path('docs/phase-62-report-saved-view-rollout-selector-web-links.md');
        $helper = base_path('app/Support/Reports/ReportSavedViewRolloutSelectorWebLinks.php');
        $view = base_path('resources/views/reports/saved-view-rollout-selector.blade.php');

        $this->assertFileExists($finalDoc);
        $this->assertFileExists($doc62a);
        $this->assertFileExists($doc62b);
        $this->assertFileExists($helper);
        $this->assertFileExists($view);

        $finalContents = file_get_contents($finalDoc);
        $doc62aContents = file_get_contents($doc62a);
        $doc62bContents = file_get_contents($doc62b);
        $viewContents = file_get_contents($view);

        $this->assertStringContainsString('Phase 62 is finalized.', $finalContents);
        $this->assertStringContainsString('ReportSavedViewRolloutSelectorSurfaceFinalizationTest', $finalContents);
        $this->assertStringContainsString('reports.saved-view-rollout-selector.index', $finalContents);

        $this->assertStringContainsString('Phase 62C rollout selector finalization', $doc62aContents);
        $this->assertStringContainsString('Phase 62C workflow finalization', $doc62bContents);

        $this->assertStringContainsString('workflowSteps', file_get_contents($helper));
        $this->assertStringContainsString('report-saved-view-rollout-selector-workflow', $viewContents);
    }
}
