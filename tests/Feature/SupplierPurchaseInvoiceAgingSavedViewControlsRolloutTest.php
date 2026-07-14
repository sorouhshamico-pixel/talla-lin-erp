<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Reports\ReportSavedViewRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierPurchaseInvoiceAgingSavedViewControlsRolloutTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_aging_saved_view_controls_config_partial_exists_and_uses_shared_controls(): void
    {
        $configPartial = resource_path('views/reports/partials/supplier-purchase-invoice-aging-saved-view-controls-config.blade.php');

        $this->assertFileExists($configPartial);

        $contents = file_get_contents($configPartial);

        $this->assertStringContainsString('$supplierPurchaseInvoiceAgingSavedViewControlsConfig = [', $contents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-controls'", $contents);

        $this->assertStringContainsString("'routeName' => 'reports.supplier-purchase-invoice-aging.index'", $contents);
        $this->assertStringContainsString("'storeRouteName' => 'reports.supplier-purchase-invoice-aging.saved-views.store'", $contents);

        foreach ([
            'supplier-aging-saved-views-selector',
            'supplier-aging-saved-views-empty',
            'supplier-aging-save-view-card',
            'supplier-aging-save-view-form',
            'supplier-aging-saved-view-name-input',
            'supplier-aging-saved-view-default-checkbox',
            'supplier-aging-save-view-button',
            'supplier-aging-saved-views-list',
            'supplier-aging-saved-view-item',
            'supplier-aging-saved-view-open-link',
            'supplier-aging-saved-view-active-badge',
            'supplier-aging-saved-view-default-badge',
        ] as $testId) {
            $this->assertStringContainsString($testId, $contents);
        }

        foreach ([
            "'supplier_id' => request('supplier_id')",
            "'aging_bucket' => request('aging_bucket')",
        ] as $hiddenField) {
            $this->assertStringContainsString($hiddenField, $contents);
        }
    }

    public function test_supplier_aging_report_loads_config_partial_instead_of_inline_saved_view_markup(): void
    {
        $targetView = resource_path('views/reports/supplier-purchase-invoice-aging.blade.php');

        $this->assertFileExists($targetView);

        $contents = file_get_contents($targetView);

        $this->assertStringContainsString(
            "@include('reports.partials.supplier-purchase-invoice-aging-saved-view-controls-config')",
            $contents
        );

        $this->assertStringNotContainsString("@include('reports.partials.saved-view-controls'", $contents);
        $this->assertStringNotContainsString("@include('reports.partials.saved-view-section'", $contents);

        $this->assertStringNotContainsString('data-testid="supplier-aging-save-view-card"', $contents);
        $this->assertStringNotContainsString('data-testid="supplier-aging-save-view-form"', $contents);
        $this->assertStringNotContainsString('data-testid="supplier-aging-saved-view-name-input"', $contents);
        $this->assertStringNotContainsString('data-testid="supplier-aging-saved-view-default-checkbox"', $contents);
        $this->assertStringNotContainsString('data-testid="supplier-aging-save-view-button"', $contents);

        $this->assertStringContainsString('data-testid="supplier-aging-report-date"', $contents);
        $this->assertStringContainsString('data-testid="supplier-aging-summary"', $contents);
        $this->assertStringContainsString('data-testid="supplier-aging-table"', $contents);
    }

    public function test_supplier_aging_registry_contains_rollout_contract(): void
    {
        $this->assertTrue(ReportSavedViewRegistry::has('supplier-purchase-invoice-aging'));

        $report = ReportSavedViewRegistry::find('supplier-purchase-invoice-aging');

        $this->assertSame('supplier-purchase-invoice-aging', $report['key']);
        $this->assertSame('تقرير أعمار ذمم الموردين', $report['label']);
        $this->assertSame('reports.supplier-purchase-invoice-aging.index', $report['index_route']);
        $this->assertSame('reports.supplier-purchase-invoice-aging.export', $report['export_route']);
        $this->assertSame('reports.supplier-purchase-invoice-aging.saved-views.store', $report['saved_view_store_route']);
        $this->assertSame('reports.partials.supplier-purchase-invoice-aging-saved-view-controls-config', $report['config_partial']);
        $this->assertSame('resources/views/reports/partials/supplier-purchase-invoice-aging-saved-view-controls-config.blade.php', $report['config_partial_path']);

        $this->assertSame([
            'supplier_id',
            'aging_bucket',
        ], $report['hidden_fields']);

        $this->assertSame('supplier-aging-saved-views-selector', $report['test_ids']['section_card']);
        $this->assertSame('supplier-aging-saved-view-item', $report['test_ids']['item']);
    }

    public function test_supplier_aging_report_renders_saved_view_controls_from_config_partial(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.index'));

        $response->assertOk();

        $response->assertSee('data-testid="supplier-aging-saved-views-selector"', false);
        $response->assertSee('data-testid="supplier-aging-saved-views-empty"', false);
        $response->assertSee('data-testid="supplier-aging-save-view-card"', false);
        $response->assertSee('data-testid="supplier-aging-save-view-form"', false);
        $response->assertSee('data-testid="supplier-aging-saved-view-name-input"', false);
        $response->assertSee('data-testid="supplier-aging-saved-view-default-checkbox"', false);
        $response->assertSee('data-testid="supplier-aging-save-view-button"', false);

        $response->assertSee('name="supplier_id"', false);
        $response->assertSee('name="aging_bucket"', false);
    }
}
