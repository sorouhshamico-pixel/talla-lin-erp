<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Reports\ReportSavedViewRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProfitLossSavedViewControlsRolloutTest extends TestCase
{
    use RefreshDatabase;

    public function test_profit_loss_saved_view_controls_config_partial_exists_and_uses_shared_controls(): void
    {
        $configPartial = resource_path('views/reports/partials/profit-loss-saved-view-controls-config.blade.php');

        $this->assertFileExists($configPartial);

        $contents = file_get_contents($configPartial);

        $this->assertStringContainsString('$profitLossSavedViewControlsConfig = [', $contents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-controls'", $contents);

        $this->assertStringContainsString("'routeName' => 'reports.profit-loss'", $contents);
        $this->assertStringContainsString("'storeRouteName' => 'reports.profit-loss.saved-views.store'", $contents);

        foreach ([
            'profit-loss-saved-views-selector',
            'profit-loss-saved-views-empty',
            'profit-loss-save-view-card',
            'profit-loss-save-view-form',
            'profit-loss-saved-view-name-input',
            'profit-loss-saved-view-default-checkbox',
            'profit-loss-save-view-button',
            'profit-loss-saved-views-list',
            'profit-loss-saved-view-item',
            'profit-loss-saved-view-open-link',
            'profit-loss-saved-view-active-badge',
            'profit-loss-saved-view-default-badge',
        ] as $testId) {
            $this->assertStringContainsString($testId, $contents);
        }

        foreach ([
            "'from_date' => \$filters['from_date'] ?? null",
            "'to_date' => \$filters['to_date'] ?? null",
            "'branch_id' => \$filters['branch_id'] ?? null",
        ] as $hiddenField) {
            $this->assertStringContainsString($hiddenField, $contents);
        }
    }

    public function test_profit_loss_route_controller_and_view_are_wired_for_saved_views(): void
    {
        $this->assertTrue(Route::has('reports.profit-loss.saved-views.store'));

        $controller = file_get_contents(app_path('Http/Controllers/ProfitLossReportController.php'));
        $view = file_get_contents(resource_path('views/reports/profit-loss.blade.php'));

        $this->assertStringContainsString("private const REPORT_KEY = 'profit-loss';", $controller);
        $this->assertStringContainsString('ReportSavedViewService', $controller);
        $this->assertStringContainsString('function storeSavedView', $controller);
        $this->assertStringContainsString('requestWithDefaultSavedView', $controller);
        $this->assertStringContainsString('function export', $controller);

        foreach ([
            'from_date',
            'to_date',
            'branch_id',
        ] as $field) {
            $this->assertStringContainsString("'{$field}'", $controller);
        }

        $this->assertStringContainsString("@include('reports.partials.profit-loss-saved-view-controls-config')", $view);
        $this->assertStringContainsString('data-testid="profit-loss-status"', $view);
        $this->assertStringContainsString('data-testid="profit-loss-report"', $view);
        $this->assertStringContainsString('data-testid="profit-loss-export"', $view);
    }

    public function test_profit_loss_registry_contains_rollout_contract(): void
    {
        $this->assertTrue(ReportSavedViewRegistry::has('profit-loss'));

        $report = ReportSavedViewRegistry::find('profit-loss');

        $this->assertSame('profit-loss', $report['key']);
        $this->assertSame('تقرير الأرباح والخسائر', $report['label']);
        $this->assertSame('reports.profit-loss', $report['index_route']);
        $this->assertSame('reports.profit-loss.export', $report['export_route']);
        $this->assertSame('reports.profit-loss.saved-views.store', $report['saved_view_store_route']);
        $this->assertSame('reports.partials.profit-loss-saved-view-controls-config', $report['config_partial']);
        $this->assertSame('resources/views/reports/partials/profit-loss-saved-view-controls-config.blade.php', $report['config_partial_path']);

        $this->assertSame([
            'from_date',
            'to_date',
            'branch_id',
        ], $report['hidden_fields']);

        $this->assertSame('profit-loss-saved-views-selector', $report['test_ids']['section_card']);
        $this->assertSame('profit-loss-save-view-form', $report['test_ids']['form']);
    }

    public function test_profit_loss_renders_and_saves_saved_view_controls(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.profit-loss'))
            ->assertOk()
            ->assertSee('data-testid="profit-loss-saved-views-selector"', false)
            ->assertSee('data-testid="profit-loss-saved-views-empty"', false)
            ->assertSee('data-testid="profit-loss-save-view-card"', false)
            ->assertSee('data-testid="profit-loss-save-view-form"', false)
            ->assertSee('data-testid="profit-loss-saved-view-name-input"', false)
            ->assertSee('data-testid="profit-loss-saved-view-default-checkbox"', false)
            ->assertSee('data-testid="profit-loss-save-view-button"', false)
            ->assertSee('name="from_date"', false)
            ->assertSee('name="to_date"', false)
            ->assertSee('name="branch_id"', false);

        $this->actingAs($user)
            ->post(route('reports.profit-loss.saved-views.store'), [
                'name' => 'أرباح وخسائر تجريبي',
                'from_date' => '2026-07-01',
                'to_date' => '2026-07-31',
                'branch_id' => '',
                'is_default' => '1',
            ])
            ->assertRedirect(route('reports.profit-loss', [
                'from_date' => '2026-07-01',
                'to_date' => '2026-07-31',
            ]));

        $this->assertDatabaseHas('report_saved_views', [
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'أرباح وخسائر تجريبي',
            'is_default' => true,
        ]);
    }
}
