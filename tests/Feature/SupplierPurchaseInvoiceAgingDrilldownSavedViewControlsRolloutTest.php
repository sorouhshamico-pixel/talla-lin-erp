<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Reports\ReportSavedViewRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierPurchaseInvoiceAgingDrilldownSavedViewControlsRolloutTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_aging_drilldown_saved_view_controls_config_partial_exists_and_uses_shared_controls(): void
    {
        $configPartial = resource_path('views/reports/partials/supplier-purchase-invoice-aging-drilldown-saved-view-controls-config.blade.php');

        $this->assertFileExists($configPartial);

        $contents = file_get_contents($configPartial);

        $this->assertStringContainsString('$supplierPurchaseInvoiceAgingDrilldownSavedViewControlsConfig = [', $contents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-controls'", $contents);

        $this->assertStringContainsString("'routeName' => 'reports.supplier-purchase-invoice-aging.drilldown'", $contents);
        $this->assertStringContainsString("'storeRouteName' => 'reports.supplier-purchase-invoice-aging.drilldown.saved-views.store'", $contents);

        foreach ([
            'supplier-aging-drilldown-saved-views-selector',
            'supplier-aging-drilldown-saved-views-empty',
            'supplier-aging-drilldown-save-view-card',
            'supplier-aging-drilldown-save-view-form',
            'supplier-aging-drilldown-saved-view-name-input',
            'supplier-aging-drilldown-saved-view-default-checkbox',
            'supplier-aging-drilldown-save-view-button',
            'supplier-aging-drilldown-saved-views-list',
            'supplier-aging-drilldown-saved-view-item',
            'supplier-aging-drilldown-saved-view-open-link',
            'supplier-aging-drilldown-saved-view-active-badge',
            'supplier-aging-drilldown-saved-view-default-badge',
        ] as $testId) {
            $this->assertStringContainsString($testId, $contents);
        }

        foreach ([
            "'supplier_id' => \$selectedSupplierId",
            "'branch_id' => \$selectedBranchId",
            "'as_of_date' => \$selectedAsOfDate",
            "'aging_bucket' => \$selectedAgingBucket",
        ] as $hiddenField) {
            $this->assertStringContainsString($hiddenField, $contents);
        }
    }

    public function test_supplier_aging_drilldown_report_loads_config_partial_instead_of_inline_saved_view_markup(): void
    {
        $targetView = resource_path('views/reports/supplier-purchase-invoice-aging-drilldown.blade.php');

        $this->assertFileExists($targetView);

        $contents = file_get_contents($targetView);

        $this->assertStringContainsString(
            "@include('reports.partials.supplier-purchase-invoice-aging-drilldown-saved-view-controls-config')",
            $contents
        );

        $this->assertStringNotContainsString("@include('reports.partials.saved-view-controls'", $contents);
        $this->assertStringNotContainsString("@include('reports.partials.saved-view-section'", $contents);

        $this->assertStringNotContainsString('data-testid="supplier-aging-drilldown-save-view-card"', $contents);
        $this->assertStringNotContainsString('data-testid="supplier-aging-drilldown-save-view-form"', $contents);
        $this->assertStringNotContainsString('data-testid="supplier-aging-drilldown-saved-view-name-input"', $contents);
        $this->assertStringNotContainsString('data-testid="supplier-aging-drilldown-saved-view-default-checkbox"', $contents);
        $this->assertStringNotContainsString('data-testid="supplier-aging-drilldown-save-view-button"', $contents);

        $this->assertStringContainsString('data-testid="supplier-aging-drilldown-report-date"', $contents);
        $this->assertStringContainsString('data-testid="supplier-aging-drilldown-summary"', $contents);
        $this->assertStringContainsString('data-testid="supplier-aging-drilldown-table"', $contents);
    }

    public function test_supplier_aging_drilldown_registry_contains_rollout_contract(): void
    {
        $this->assertTrue(ReportSavedViewRegistry::has('supplier-purchase-invoice-aging-drilldown'));

        $report = ReportSavedViewRegistry::find('supplier-purchase-invoice-aging-drilldown');

        $this->assertSame('supplier-purchase-invoice-aging-drilldown', $report['key']);
        $this->assertSame('تفاصيل فواتير الموردين المفتوحة', $report['label']);
        $this->assertSame('reports.supplier-purchase-invoice-aging.drilldown', $report['index_route']);
        $this->assertSame('reports.supplier-purchase-invoice-aging.drilldown.export', $report['export_route']);
        $this->assertSame('reports.supplier-purchase-invoice-aging.drilldown.saved-views.store', $report['saved_view_store_route']);
        $this->assertSame('reports.partials.supplier-purchase-invoice-aging-drilldown-saved-view-controls-config', $report['config_partial']);
        $this->assertSame('resources/views/reports/partials/supplier-purchase-invoice-aging-drilldown-saved-view-controls-config.blade.php', $report['config_partial_path']);

        $this->assertSame([
            'supplier_id',
            'branch_id',
            'as_of_date',
            'aging_bucket',
        ], $report['hidden_fields']);

        $this->assertSame('supplier-aging-drilldown-saved-views-selector', $report['test_ids']['section_card']);
        $this->assertSame('supplier-aging-drilldown-saved-view-item', $report['test_ids']['item']);
    }

    public function test_supplier_aging_drilldown_report_renders_saved_view_controls_from_config_partial(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.drilldown'));

        $response->assertOk();

        $response->assertSee('data-testid="supplier-aging-drilldown-saved-views-selector"', false);
        $response->assertSee('data-testid="supplier-aging-drilldown-saved-views-empty"', false);
        $response->assertSee('data-testid="supplier-aging-drilldown-save-view-card"', false);
        $response->assertSee('data-testid="supplier-aging-drilldown-save-view-form"', false);
        $response->assertSee('data-testid="supplier-aging-drilldown-saved-view-name-input"', false);
        $response->assertSee('data-testid="supplier-aging-drilldown-saved-view-default-checkbox"', false);
        $response->assertSee('data-testid="supplier-aging-drilldown-save-view-button"', false);

        $response->assertSee('name="supplier_id"', false);
        $response->assertSee('name="branch_id"', false);
        $response->assertSee('name="as_of_date"', false);
        $response->assertSee('name="aging_bucket"', false);
    }
}
