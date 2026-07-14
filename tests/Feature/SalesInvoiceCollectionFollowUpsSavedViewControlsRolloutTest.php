<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Reports\ReportSavedViewRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SalesInvoiceCollectionFollowUpsSavedViewControlsRolloutTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_invoice_collection_follow_ups_saved_view_controls_config_partial_exists_and_uses_shared_controls(): void
    {
        $configPartial = resource_path('views/reports/partials/sales-invoice-collection-follow-ups-saved-view-controls-config.blade.php');

        $this->assertFileExists($configPartial);

        $contents = file_get_contents($configPartial);

        $this->assertStringContainsString('$salesInvoiceCollectionFollowUpsSavedViewControlsConfig = [', $contents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-controls'", $contents);

        $this->assertStringContainsString("'routeName' => 'reports.sales-invoice-collection-follow-ups.index'", $contents);
        $this->assertStringContainsString("'storeRouteName' => 'reports.sales-invoice-collection-follow-ups.saved-views.store'", $contents);

        foreach ([
            'sales-invoice-collection-follow-ups-saved-views-selector',
            'sales-invoice-collection-follow-ups-saved-views-empty',
            'sales-invoice-collection-follow-ups-save-view-card',
            'sales-invoice-collection-follow-ups-save-view-form',
            'sales-invoice-collection-follow-ups-saved-view-name-input',
            'sales-invoice-collection-follow-ups-saved-view-default-checkbox',
            'sales-invoice-collection-follow-ups-save-view-button',
            'sales-invoice-collection-follow-ups-saved-views-list',
            'sales-invoice-collection-follow-ups-saved-view-item',
            'sales-invoice-collection-follow-ups-saved-view-open-link',
            'sales-invoice-collection-follow-ups-saved-view-active-badge',
            'sales-invoice-collection-follow-ups-saved-view-default-badge',
        ] as $testId) {
            $this->assertStringContainsString($testId, $contents);
        }

        foreach ([
            "'customer_id' => \$customerFilter ?? null",
            "'follow_up_from' => \$followUpFromFilter ?? null",
            "'follow_up_to' => \$followUpToFilter ?? null",
        ] as $hiddenField) {
            $this->assertStringContainsString($hiddenField, $contents);
        }
    }

    public function test_sales_invoice_collection_follow_ups_route_controller_and_view_are_wired_for_saved_views(): void
    {
        $this->assertTrue(Route::has('reports.sales-invoice-collection-follow-ups.saved-views.store'));

        $controller = file_get_contents(app_path('Http/Controllers/SalesInvoiceCollectionFollowUpReportController.php'));
        $view = file_get_contents(resource_path('views/reports/sales-invoice-collection-follow-ups.blade.php'));

        $this->assertStringContainsString("private const REPORT_KEY = 'sales-invoice-collection-follow-ups';", $controller);
        $this->assertStringContainsString('ReportSavedViewService', $controller);
        $this->assertStringContainsString('function storeSavedView', $controller);
        $this->assertStringContainsString('requestWithDefaultSavedView', $controller);
        $this->assertStringContainsString('function index', $controller);
        $this->assertStringContainsString('function export', $controller);

        foreach ([
            'customer_id',
            'follow_up_from',
            'follow_up_to',
        ] as $field) {
            $this->assertStringContainsString("'{$field}'", $controller);
        }

        $this->assertStringContainsString("@include('reports.partials.sales-invoice-collection-follow-ups-saved-view-controls-config')", $view);
        $this->assertStringContainsString('data-testid="sales-invoice-collection-follow-ups-status"', $view);
        $this->assertStringContainsString('data-testid="sales-invoice-collection-follow-up-report-page"', $view);
        $this->assertStringContainsString('data-testid="collection-follow-up-report-export-link"', $view);
    }

    public function test_sales_invoice_collection_follow_ups_registry_contains_rollout_contract(): void
    {
        $this->assertTrue(ReportSavedViewRegistry::has('sales-invoice-collection-follow-ups'));

        $report = ReportSavedViewRegistry::find('sales-invoice-collection-follow-ups');

        $this->assertSame('sales-invoice-collection-follow-ups', $report['key']);
        $this->assertSame('تقرير متابعات تحصيل فواتير المبيعات', $report['label']);
        $this->assertSame('reports.sales-invoice-collection-follow-ups.index', $report['index_route']);
        $this->assertSame('reports.sales-invoice-collection-follow-ups.export', $report['export_route']);
        $this->assertSame('reports.sales-invoice-collection-follow-ups.saved-views.store', $report['saved_view_store_route']);
        $this->assertSame('reports.partials.sales-invoice-collection-follow-ups-saved-view-controls-config', $report['config_partial']);
        $this->assertSame('resources/views/reports/partials/sales-invoice-collection-follow-ups-saved-view-controls-config.blade.php', $report['config_partial_path']);

        $this->assertSame([
            'customer_id',
            'follow_up_from',
            'follow_up_to',
        ], $report['hidden_fields']);

        $this->assertSame('sales-invoice-collection-follow-ups-saved-views-selector', $report['test_ids']['section_card']);
        $this->assertSame('sales-invoice-collection-follow-ups-save-view-form', $report['test_ids']['form']);
    }

    public function test_sales_invoice_collection_follow_ups_renders_and_saves_saved_view_controls(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.sales-invoice-collection-follow-ups.index'))
            ->assertOk()
            ->assertSee('data-testid="sales-invoice-collection-follow-ups-saved-views-selector"', false)
            ->assertSee('data-testid="sales-invoice-collection-follow-ups-saved-views-empty"', false)
            ->assertSee('data-testid="sales-invoice-collection-follow-ups-save-view-card"', false)
            ->assertSee('data-testid="sales-invoice-collection-follow-ups-save-view-form"', false)
            ->assertSee('data-testid="sales-invoice-collection-follow-ups-saved-view-name-input"', false)
            ->assertSee('data-testid="sales-invoice-collection-follow-ups-saved-view-default-checkbox"', false)
            ->assertSee('data-testid="sales-invoice-collection-follow-ups-save-view-button"', false)
            ->assertSee('name="customer_id"', false)
            ->assertSee('name="follow_up_from"', false)
            ->assertSee('name="follow_up_to"', false);

        $this->actingAs($user)
            ->post(route('reports.sales-invoice-collection-follow-ups.saved-views.store'), [
                'name' => 'متابعات تحصيل تجريبي',
                'follow_up_from' => '2026-07-01',
                'follow_up_to' => '2026-07-31',
                'is_default' => '1',
            ])
            ->assertRedirect(route('reports.sales-invoice-collection-follow-ups.index', [
                'follow_up_from' => '2026-07-01',
                'follow_up_to' => '2026-07-31',
            ]));

        $this->assertDatabaseHas('report_saved_views', [
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-collection-follow-ups',
            'name' => 'متابعات تحصيل تجريبي',
            'is_default' => true,
        ]);
    }
}
