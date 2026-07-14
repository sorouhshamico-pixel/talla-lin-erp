<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Reports\ReportSavedViewRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportsIndexSavedViewControlsRolloutTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_index_saved_view_controls_config_partial_exists_and_uses_shared_controls(): void
    {
        $configPartial = resource_path('views/reports/partials/index-saved-view-controls-config.blade.php');

        $this->assertFileExists($configPartial);

        $contents = file_get_contents($configPartial);

        $this->assertStringContainsString('$reportsIndexSavedViewControlsConfig = [', $contents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-controls'", $contents);

        $this->assertStringContainsString("'routeName' => 'reports.index'", $contents);
        $this->assertStringContainsString("'storeRouteName' => 'reports.index.saved-views.store'", $contents);

        foreach ([
            'reports-index-saved-views-selector',
            'reports-index-saved-views-empty',
            'reports-index-save-view-card',
            'reports-index-save-view-form',
            'reports-index-saved-view-name-input',
            'reports-index-saved-view-default-checkbox',
            'reports-index-save-view-button',
            'reports-index-saved-views-list',
            'reports-index-saved-view-item',
            'reports-index-saved-view-open-link',
            'reports-index-saved-view-active-badge',
            'reports-index-saved-view-default-badge',
        ] as $testId) {
            $this->assertStringContainsString($testId, $contents);
        }

        foreach ([
            "'from_date' => \$filters['from_date'] ?? null",
            "'to_date' => \$filters['to_date'] ?? null",
            "'branch_id' => \$filters['branch_id'] ?? null",
            "'expense_category_id' => \$filters['expense_category_id'] ?? null",
            "'payment_method' => \$filters['payment_method'] ?? null",
        ] as $hiddenField) {
            $this->assertStringContainsString($hiddenField, $contents);
        }
    }

    public function test_reports_index_route_controller_and_view_are_wired_for_saved_views(): void
    {
        $this->assertTrue(Route::has('reports.index.saved-views.store'));

        $controller = file_get_contents(app_path('Http/Controllers/ReportController.php'));
        $view = file_get_contents(resource_path('views/reports/index.blade.php'));

        $this->assertStringContainsString("private const REPORT_KEY = 'index';", $controller);
        $this->assertStringContainsString('ReportSavedViewService', $controller);
        $this->assertStringContainsString('function storeSavedView', $controller);
        $this->assertStringContainsString('requestWithDefaultSavedView', $controller);

        foreach ([
            'from_date',
            'to_date',
            'branch_id',
            'expense_category_id',
            'payment_method',
        ] as $field) {
            $this->assertStringContainsString("'{$field}'", $controller);
        }

        $this->assertStringContainsString("@include('reports.partials.index-saved-view-controls-config')", $view);
        $this->assertStringContainsString('data-testid="reports-index-status"', $view);
    }

    public function test_reports_index_registry_contains_rollout_contract(): void
    {
        $this->assertTrue(ReportSavedViewRegistry::has('index'));

        $report = ReportSavedViewRegistry::find('index');

        $this->assertSame('index', $report['key']);
        $this->assertSame('التقارير المالية الأساسية', $report['label']);
        $this->assertSame('reports.index', $report['index_route']);
        $this->assertSame('reports.index', $report['export_route']);
        $this->assertSame('reports.index.saved-views.store', $report['saved_view_store_route']);
        $this->assertSame('reports.partials.index-saved-view-controls-config', $report['config_partial']);
        $this->assertSame('resources/views/reports/partials/index-saved-view-controls-config.blade.php', $report['config_partial_path']);

        $this->assertSame([
            'from_date',
            'to_date',
            'branch_id',
            'expense_category_id',
            'payment_method',
        ], $report['hidden_fields']);

        $this->assertSame('reports-index-saved-views-selector', $report['test_ids']['section_card']);
        $this->assertSame('reports-index-save-view-form', $report['test_ids']['form']);
    }

    public function test_reports_index_renders_and_saves_saved_view_controls(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('data-testid="reports-index-saved-views-selector"', false)
            ->assertSee('data-testid="reports-index-saved-views-empty"', false)
            ->assertSee('data-testid="reports-index-save-view-card"', false)
            ->assertSee('data-testid="reports-index-save-view-form"', false)
            ->assertSee('data-testid="reports-index-saved-view-name-input"', false)
            ->assertSee('data-testid="reports-index-saved-view-default-checkbox"', false)
            ->assertSee('data-testid="reports-index-save-view-button"', false)
            ->assertSee('name="from_date"', false)
            ->assertSee('name="to_date"', false)
            ->assertSee('name="branch_id"', false)
            ->assertSee('name="expense_category_id"', false)
            ->assertSee('name="payment_method"', false);

        $this->actingAs($user)
            ->post(route('reports.index.saved-views.store'), [
                'name' => 'ملخص مالي تجريبي',
                'from_date' => '2026-07-01',
                'to_date' => '2026-07-31',
                'branch_id' => '',
                'expense_category_id' => '',
                'payment_method' => 'cash',
                'is_default' => '1',
            ])
            ->assertRedirect(route('reports.index', [
                'from_date' => '2026-07-01',
                'to_date' => '2026-07-31',
                'payment_method' => 'cash',
            ]));

        $this->assertDatabaseHas('report_saved_views', [
            'user_id' => $user->id,
            'report_key' => 'index',
            'name' => 'ملخص مالي تجريبي',
            'is_default' => true,
        ]);
    }
}
