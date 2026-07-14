<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Support\Reports\ReportSavedViewCandidateScanner;
use App\Support\Reports\ReportSavedViewRegistry;
use App\Support\Reports\ReportSavedViewRegistryValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SavedViewCandidatesSavedViewControlsRolloutTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_view_candidates_config_partial_exists_and_uses_shared_controls(): void
    {
        $configPartial = resource_path('views/reports/partials/saved-view-candidates-saved-view-controls-config.blade.php');

        $this->assertFileExists($configPartial);

        $contents = file_get_contents($configPartial);

        $this->assertStringContainsString('$savedViewCandidatesSavedViewControlsConfig = [', $contents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-controls'", $contents);
        $this->assertStringContainsString("'routeName' => 'reports.saved-view-candidates.index'", $contents);
        $this->assertStringContainsString("'storeRouteName' => 'reports.saved-view-candidates.saved-views.store'", $contents);
        $this->assertStringContainsString("'hiddenFields' => []", $contents);

        foreach ([
            'saved-view-candidates-saved-views-selector',
            'saved-view-candidates-saved-views-empty',
            'saved-view-candidates-save-view-card',
            'saved-view-candidates-save-view-form',
            'saved-view-candidates-saved-view-name-input',
            'saved-view-candidates-saved-view-default-checkbox',
            'saved-view-candidates-save-view-button',
            'saved-view-candidates-saved-views-list',
            'saved-view-candidates-saved-view-item',
            'saved-view-candidates-saved-view-open-link',
            'saved-view-candidates-saved-view-active-badge',
            'saved-view-candidates-saved-view-default-badge',
        ] as $testId) {
            $this->assertStringContainsString($testId, $contents);
        }
    }

    public function test_saved_view_candidates_routes_view_registry_and_validator_are_wired(): void
    {
        $this->assertTrue(Route::has('reports.saved-view-candidates.index'));
        $this->assertTrue(Route::has('reports.saved-view-candidates.json'));
        $this->assertTrue(Route::has('reports.saved-view-candidates.saved-views.store'));

        $routes = file_get_contents(base_path('routes/web.php'));
        $view = file_get_contents(resource_path('views/reports/saved-view-candidates.blade.php'));

        $this->assertStringContainsString("Schema::hasTable('report_saved_views')", $routes);
        $this->assertStringContainsString("'savedViews' => \$savedViewsForReport", $routes);
        $this->assertStringContainsString("@include('reports.partials.saved-view-candidates-saved-view-controls-config')", $view);
        $this->assertStringContainsString('data-testid="saved-view-candidates-status"', $view);

        $this->assertTrue(ReportSavedViewRegistry::has('saved-view-candidates'));

        $report = ReportSavedViewRegistry::find('saved-view-candidates');

        $this->assertSame('saved-view-candidates', $report['key']);
        $this->assertSame('مرشحو عروض التقارير المحفوظة', $report['label']);
        $this->assertSame('reports.saved-view-candidates.index', $report['index_route']);
        $this->assertSame('reports.saved-view-candidates.json', $report['export_route']);
        $this->assertSame('reports.saved-view-candidates.saved-views.store', $report['saved_view_store_route']);
        $this->assertSame([], $report['hidden_fields']);
        $this->assertSame('saved-view-candidates-save-view-form', $report['test_ids']['form']);
        $this->assertSame([], ReportSavedViewRegistryValidator::errorsFor('saved-view-candidates'));
    }

    public function test_saved_view_candidates_renders_and_saves_empty_filter_saved_view(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.saved-view-candidates.index'))
            ->assertOk()
            ->assertSee('data-testid="saved-view-candidates-saved-views-selector"', false)
            ->assertSee('data-testid="saved-view-candidates-saved-views-empty"', false)
            ->assertSee('data-testid="saved-view-candidates-save-view-card"', false)
            ->assertSee('data-testid="saved-view-candidates-save-view-form"', false)
            ->assertSee('data-testid="saved-view-candidates-saved-view-name-input"', false)
            ->assertSee('data-testid="saved-view-candidates-saved-view-default-checkbox"', false)
            ->assertSee('data-testid="saved-view-candidates-save-view-button"', false);

        $this->actingAs($user)
            ->post(route('reports.saved-view-candidates.saved-views.store'), [
                'name' => 'مرشحو Saved Views',
                'is_default' => '1',
            ])
            ->assertRedirect(route('reports.saved-view-candidates.index'));

        $this->assertDatabaseHas('report_saved_views', [
            'user_id' => $user->id,
            'report_key' => 'saved-view-candidates',
            'name' => 'مرشحو Saved Views',
            'is_default' => true,
        ]);

        $savedView = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('report_key', 'saved-view-candidates')
            ->first();

        $this->assertNotNull($savedView);
        $this->assertSame([], $savedView->filters);
    }

    public function test_saved_view_candidates_candidate_scanner_marks_target_registered(): void
    {
        $candidate = collect(ReportSavedViewCandidateScanner::candidates())
            ->firstWhere('key', 'saved-view-candidates');

        $this->assertNotNull($candidate);
        $this->assertTrue($candidate['registered']);
        $this->assertTrue($candidate['has_saved_view_controls']);
    }
}
