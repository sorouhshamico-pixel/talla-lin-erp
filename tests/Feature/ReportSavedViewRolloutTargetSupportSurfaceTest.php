<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Reports\ReportSavedViewRolloutTarget;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewRolloutTargetSupportSurfaceTest extends TestCase
{
    public function test_rollout_target_support_reads_locked_target_summary(): void
    {
        $summary = ReportSavedViewRolloutTarget::summary();

        $this->assertTrue($summary['has_lock']);
        $this->assertTrue($summary['has_inspection']);
        $this->assertNotEmpty($summary['key']);
        $this->assertNotEmpty($summary['view_path']);
        $this->assertTrue($summary['view_exists']);
        $this->assertIsArray($summary['candidate_filter_fields']);
        $this->assertIsArray($summary['route_names']);
        $this->assertIsArray($summary['include_names']);
        $this->assertNotEmpty($summary['recommended_config_partial']);
        $this->assertNotEmpty($summary['recommended_config_partial_path']);
    }

    public function test_rollout_target_markdown_contains_target_details(): void
    {
        $markdown = ReportSavedViewRolloutTarget::markdown();

        $this->assertStringContainsString('# Report Saved View Rollout Target', $markdown);
        $this->assertStringContainsString('## Summary', $markdown);
        $this->assertStringContainsString('## Candidate Filter Fields', $markdown);
        $this->assertStringContainsString('## Route Names', $markdown);
        $this->assertStringContainsString((string) ReportSavedViewRolloutTarget::key(), $markdown);
    }

    public function test_rollout_target_command_outputs_markdown_and_json(): void
    {
        $exitCode = Artisan::call('reports:saved-view-rollout-target');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('# Report Saved View Rollout Target', Artisan::output());

        $exitCode = Artisan::call('reports:saved-view-rollout-target', [
            '--json' => true,
        ]);

        $this->assertSame(0, $exitCode);

        $decoded = json_decode(Artisan::output(), true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('key', $decoded);
        $this->assertArrayHasKey('view_path', $decoded);
        $this->assertArrayHasKey('candidate_filter_fields', $decoded);
    }

    public function test_rollout_target_web_routes_are_registered_and_protected(): void
    {
        foreach ([
            'reports.saved-view-rollout-target.index',
            'reports.saved-view-rollout-target.markdown',
            'reports.saved-view-rollout-target.json',
        ] as $routeName) {
            $this->assertTrue(Route::has($routeName));

            $route = Route::getRoutes()->getByName($routeName);

            $this->assertContains('auth', $route->middleware());
        }
    }

    public function test_authenticated_user_can_view_rollout_target_page_and_exports(): void
    {
        $user = User::factory()->make([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-view-rollout-target.index'))
            ->assertOk()
            ->assertSee('Report Saved View Rollout Target')
            ->assertSee('التقرير المقفل حاليًا لتنفيذ Saved View rollout')
            ->assertSee('Locked Target')
            ->assertSee('Candidate Filter Fields')
            ->assertSee('Route Names')
            ->assertSee('Includes')
            ->assertSee('View Markdown')
            ->assertSee('View JSON')
            ->assertSee('Rollout Selector')
            ->assertSee((string) ReportSavedViewRolloutTarget::key());

        $this->actingAs($user)
            ->get(route('reports.saved-view-rollout-target.markdown'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertSee('# Report Saved View Rollout Target', false);

        $this->actingAs($user)
            ->get(route('reports.saved-view-rollout-target.json'))
            ->assertOk()
            ->assertJsonStructure([
                'has_lock',
                'has_inspection',
                'key',
                'view_path',
                'priority_score',
                'view_exists',
                'candidate_filter_fields',
                'route_names',
                'include_names',
                'recommended_config_partial',
                'recommended_config_partial_path',
            ]);
    }

    public function test_phase_63c_rollout_target_support_surface_is_documented(): void
    {
        $doc = base_path('docs/phase-63-report-saved-view-rollout-target-support-surface.md');
        $support = base_path('app/Support/Reports/ReportSavedViewRolloutTarget.php');
        $view = base_path('resources/views/reports/saved-view-rollout-target.blade.php');

        $this->assertFileExists($doc);
        $this->assertFileExists($support);
        $this->assertFileExists($view);

        $docContents = file_get_contents($doc);
        $routesContents = file_get_contents(base_path('routes/web.php'));
        $consoleContents = file_get_contents(base_path('routes/console.php'));

        $this->assertStringContainsString('Phase 63C', $docContents);
        $this->assertStringContainsString('Locked Rollout Target Support Surface', $docContents);
        $this->assertStringContainsString('ReportSavedViewRolloutTargetSupportSurfaceTest', $docContents);
        $this->assertStringContainsString('reports.saved-view-rollout-target.index', $routesContents);
        $this->assertStringContainsString('reports:saved-view-rollout-target', $consoleContents);
    }
}
