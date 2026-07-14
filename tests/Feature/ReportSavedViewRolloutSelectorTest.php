<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Reports\ReportSavedViewRolloutSelector;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewRolloutSelectorTest extends TestCase
{
    public function test_rollout_selector_returns_prioritized_candidates(): void
    {
        $candidates = ReportSavedViewRolloutSelector::prioritizedCandidates();

        $this->assertIsArray($candidates);

        foreach ($candidates as $candidate) {
            $this->assertFalse($candidate['registered']);
            $this->assertArrayHasKey('key', $candidate);
            $this->assertArrayHasKey('view_path', $candidate);
            $this->assertArrayHasKey('priority_score', $candidate);
        }
    }

    public function test_rollout_selector_plan_is_consistent(): void
    {
        $plan = ReportSavedViewRolloutSelector::plan();

        $this->assertArrayHasKey('has_next_candidate', $plan);
        $this->assertArrayHasKey('next_candidate', $plan);
        $this->assertArrayHasKey('candidate_count', $plan);
        $this->assertArrayHasKey('unregistered_candidate_count', $plan);
        $this->assertArrayHasKey('registered_candidate_count', $plan);
        $this->assertArrayHasKey('prioritized_candidates', $plan);
        $this->assertArrayHasKey('excluded_print_candidate_count', $plan);
        $this->assertArrayHasKey('excluded_print_candidates', $plan);
        $this->assertArrayHasKey('excluded_tooling_candidate_count', $plan);
        $this->assertArrayHasKey('excluded_tooling_candidates', $plan);
        $this->assertArrayHasKey('recommended_steps', $plan);

        $this->assertSame(count($plan['prioritized_candidates']), $plan['unregistered_candidate_count']);

        if ($plan['has_next_candidate']) {
            $this->assertIsArray($plan['next_candidate']);
            $this->assertSame($plan['prioritized_candidates'][0]['key'], $plan['next_candidate']['key']);
        }
    }

    public function test_rollout_selector_markdown_contains_summary_and_steps(): void
    {
        $markdown = ReportSavedViewRolloutSelector::markdown();

        $this->assertStringContainsString('# Report Saved View Rollout Selector', $markdown);
        $this->assertStringContainsString('## Summary', $markdown);
        $this->assertStringContainsString('## Next Candidate', $markdown);
        $this->assertStringContainsString('## Recommended Steps', $markdown);
        $this->assertStringContainsString('## Prioritized Candidates', $markdown);
    }

    public function test_rollout_selector_command_outputs_markdown_and_json(): void
    {
        $exitCode = Artisan::call('reports:saved-view-rollout-selector');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('# Report Saved View Rollout Selector', Artisan::output());

        $exitCode = Artisan::call('reports:saved-view-rollout-selector', [
            '--json' => true,
        ]);

        $this->assertSame(0, $exitCode);

        $decoded = json_decode(Artisan::output(), true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('has_next_candidate', $decoded);
        $this->assertArrayHasKey('prioritized_candidates', $decoded);
    }

    public function test_rollout_selector_web_routes_are_registered_and_protected(): void
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

    public function test_authenticated_user_can_view_rollout_selector_page_and_exports(): void
    {
        $user = User::factory()->make([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-view-rollout-selector.index'))
            ->assertOk()
            ->assertSee('Report Saved View Rollout Selector')
            ->assertSee('اختيار التقرير التالي المرشح لتفعيل Saved Views')
            ->assertSee('Next Candidate')
            ->assertSee('Recommended Steps')
            ->assertSee('Prioritized Candidates')
            ->assertSee('View Markdown')
            ->assertSee('View JSON')
            ->assertSee('Candidates');

        $this->actingAs($user)
            ->get(route('reports.saved-view-rollout-selector.markdown'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertSee('# Report Saved View Rollout Selector', false);

        $this->actingAs($user)
            ->get(route('reports.saved-view-rollout-selector.json'))
            ->assertOk()
            ->assertJsonStructure([
                'has_next_candidate',
                'next_candidate',
                'candidate_count',
                'unregistered_candidate_count',
                'registered_candidate_count',
                'excluded_print_candidate_count',
                'excluded_print_candidates',
                'excluded_tooling_candidate_count',
                'excluded_tooling_candidates',
                'prioritized_candidates',
                'recommended_steps',
            ]);
    }

    public function test_phase_62a_rollout_selector_is_documented(): void
    {
        $doc = base_path('docs/phase-62-report-saved-view-rollout-selector.md');
        $selector = base_path('app/Support/Reports/ReportSavedViewRolloutSelector.php');
        $view = base_path('resources/views/reports/saved-view-rollout-selector.blade.php');

        $this->assertFileExists($doc);
        $this->assertFileExists($selector);
        $this->assertFileExists($view);

        $docContents = file_get_contents($doc);
        $routesContents = file_get_contents(base_path('routes/web.php'));
        $consoleContents = file_get_contents(base_path('routes/console.php'));

        $this->assertStringContainsString('Phase 62A', $docContents);
        $this->assertStringContainsString('Report Saved View Rollout Selector', $docContents);
        $this->assertStringContainsString('php artisan reports:saved-view-rollout-selector', $docContents);
        $this->assertStringContainsString('reports.saved-view-rollout-selector.index', $docContents);
        $this->assertStringContainsString('ReportSavedViewRolloutSelectorTest', $docContents);

        $this->assertStringContainsString('reports.saved-view-rollout-selector.index', $routesContents);
        $this->assertStringContainsString('reports:saved-view-rollout-selector', $consoleContents);
    }
}
