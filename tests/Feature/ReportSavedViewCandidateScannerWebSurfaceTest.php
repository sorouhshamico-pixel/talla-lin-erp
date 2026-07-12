<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewCandidateScannerWebSurfaceTest extends TestCase
{
    public function test_candidate_scanner_web_routes_are_registered_and_protected(): void
    {
        $routeNames = [
            'reports.saved-view-candidates.index',
            'reports.saved-view-candidates.markdown',
            'reports.saved-view-candidates.json',
        ];

        foreach ($routeNames as $routeName) {
            $this->assertTrue(Route::has($routeName));

            $route = Route::getRoutes()->getByName($routeName);

            $this->assertContains('auth', $route->middleware());
        }

        $this->assertSame(
            'reports/saved-view-candidates',
            Route::getRoutes()->getByName('reports.saved-view-candidates.index')->uri()
        );

        $this->assertSame(
            'reports/saved-view-candidates/markdown',
            Route::getRoutes()->getByName('reports.saved-view-candidates.markdown')->uri()
        );

        $this->assertSame(
            'reports/saved-view-candidates/json',
            Route::getRoutes()->getByName('reports.saved-view-candidates.json')->uri()
        );
    }

    public function test_candidate_scanner_web_routes_require_authentication(): void
    {
        $this->get(route('reports.saved-view-candidates.index'))
            ->assertRedirect();

        $this->get(route('reports.saved-view-candidates.markdown'))
            ->assertRedirect();

        $this->get(route('reports.saved-view-candidates.json'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_view_candidate_scanner_page(): void
    {
        $user = User::factory()->make([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-view-candidates.index'))
            ->assertOk()
            ->assertSee('Report Saved View Candidates')
            ->assertSee('قائمة التقارير المرشحة لتفعيل Saved Views')
            ->assertSee('Candidate Count')
            ->assertSee('Registered Count')
            ->assertSee('Unregistered Count')
            ->assertSee('Candidate Rows')
            ->assertSee('sales-invoice-aging')
            ->assertSee('resources/views/reports/sales-invoice-aging.blade.php')
            ->assertSee('Priority Score')
            ->assertSee('View Markdown')
            ->assertSee('View JSON')
            ->assertSee('Diagnostics');
    }

    public function test_authenticated_user_can_view_candidate_scanner_markdown_export(): void
    {
        $user = User::factory()->make([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-view-candidates.markdown'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertSee('# Report Saved View Candidate Scanner', false)
            ->assertSee('## Summary', false)
            ->assertSee('### sales-invoice-aging', false);
    }

    public function test_authenticated_user_can_view_candidate_scanner_json_export(): void
    {
        $user = User::factory()->make([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-view-candidates.json'))
            ->assertOk()
            ->assertJsonStructure([
                'summary' => [
                    'candidate_count',
                    'registered_count',
                    'unregistered_count',
                    'registered_keys',
                    'unregistered_keys',
                ],
                'candidates' => [
                    '*' => [
                        'key',
                        'view_path',
                        'registered',
                        'has_get_form',
                        'has_filter_terms',
                        'has_saved_view_controls',
                        'priority_score',
                    ],
                ],
            ])
            ->assertJsonPath('summary.registered_keys.0', 'sales-invoice-aging');
    }

    public function test_candidate_scanner_web_view_contains_expected_test_ids(): void
    {
        $view = base_path('resources/views/reports/saved-view-candidates.blade.php');

        $this->assertFileExists($view);

        $contents = file_get_contents($view);

        $this->assertStringContainsString('report-saved-view-candidates-page', $contents);
        $this->assertStringContainsString('report-saved-view-candidates-summary', $contents);
        $this->assertStringContainsString('report-saved-view-candidates-export-actions', $contents);
        $this->assertStringContainsString('report-saved-view-candidates-table', $contents);
        $this->assertStringContainsString('report-saved-view-candidates-markdown', $contents);
    }

    public function test_phase_61b_candidate_scanner_web_surface_is_documented(): void
    {
        $doc = base_path('docs/phase-61-report-saved-view-candidate-scanner-web-surface.md');
        $doc61a = base_path('docs/phase-61-report-saved-view-candidate-scanner.md');
        $view = base_path('resources/views/reports/saved-view-candidates.blade.php');
        $routes = base_path('routes/web.php');

        $this->assertFileExists($doc);
        $this->assertFileExists($doc61a);
        $this->assertFileExists($view);

        $contents = file_get_contents($doc);
        $contents61a = file_get_contents($doc61a);
        $routesContents = file_get_contents($routes);

        $this->assertStringContainsString('Phase 61B', $contents);
        $this->assertStringContainsString('Report Saved View Candidate Scanner Web Surface', $contents);
        $this->assertStringContainsString('reports.saved-view-candidates.index', $contents);
        $this->assertStringContainsString('reports.saved-view-candidates.markdown', $contents);
        $this->assertStringContainsString('reports.saved-view-candidates.json', $contents);
        $this->assertStringContainsString('ReportSavedViewCandidateScannerWebSurfaceTest', $contents);

        $this->assertStringContainsString('Phase 61B web surface', $contents61a);

        $this->assertStringContainsString('reports.saved-view-candidates.index', $routesContents);
        $this->assertStringContainsString('reports.saved-view-candidates.markdown', $routesContents);
        $this->assertStringContainsString('reports.saved-view-candidates.json', $routesContents);
    }
}
