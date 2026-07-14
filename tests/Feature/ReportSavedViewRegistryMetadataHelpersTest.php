<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewRegistry;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewRegistryMetadataHelpersTest extends TestCase
{
    public function test_registry_metadata_helpers_return_expected_report_values(): void
    {
        $this->assertSame(11, ReportSavedViewRegistry::count());

        $keys = ReportSavedViewRegistry::keys();

        $this->assertContains('sales-invoice-aging', $keys);
        $this->assertContains('customer-sales-invoice-aging', $keys);
        $this->assertContains('customer-sales-invoice-aging-drilldown', $keys);
        $this->assertContains('supplier-purchase-invoice-aging', $keys);
        $this->assertContains('supplier-purchase-invoice-aging-drilldown', $keys);
        $this->assertContains('cash-flow-dashboard', $keys);
        $this->assertContains('index', $keys);
        $this->assertContains('profit-loss', $keys);
        $this->assertContains('receivable-payable-aging-dashboard', $keys);
        $this->assertContains('sales-invoice-collection-follow-ups', $keys);
        $this->assertContains('saved-view-candidates', $keys);

        $labels = ReportSavedViewRegistry::labels();

        $this->assertSame('تقرير أعمار ذمم فواتير المبيعات', $labels['sales-invoice-aging']);
        $this->assertSame('تقرير أعمار ذمم العملاء', $labels['customer-sales-invoice-aging']);
        $this->assertSame('تفاصيل فواتير العملاء المفتوحة', $labels['customer-sales-invoice-aging-drilldown']);
        $this->assertSame('تقرير أعمار ذمم الموردين', $labels['supplier-purchase-invoice-aging']);
        $this->assertSame('تفاصيل فواتير الموردين المفتوحة', $labels['supplier-purchase-invoice-aging-drilldown']);
        $this->assertSame('لوحة التدفق النقدي المتوقع', $labels['cash-flow-dashboard']);
        $this->assertSame('التقارير المالية الأساسية', $labels['index']);
        $this->assertSame('تقرير الأرباح والخسائر', $labels['profit-loss']);
        $this->assertSame('لوحة أعمار الذمم', $labels['receivable-payable-aging-dashboard']);
        $this->assertSame('تقرير متابعات تحصيل فواتير المبيعات', $labels['sales-invoice-collection-follow-ups']);
        $this->assertSame('مرشحو عروض التقارير المحفوظة', $labels['saved-view-candidates']);

        $viewPaths = ReportSavedViewRegistry::viewPaths();

        $this->assertSame(
            'resources/views/reports/sales-invoice-aging.blade.php',
            $viewPaths['sales-invoice-aging']
        );

        $this->assertSame(
            'resources/views/reports/customer-sales-invoice-aging.blade.php',
            $viewPaths['customer-sales-invoice-aging']
        );

        $this->assertSame(
            'resources/views/reports/customer-sales-invoice-aging-drilldown.blade.php',
            $viewPaths['customer-sales-invoice-aging-drilldown']
        );

        $this->assertSame(
            'resources/views/reports/supplier-purchase-invoice-aging.blade.php',
            $viewPaths['supplier-purchase-invoice-aging']
        );

        $this->assertSame(
            'resources/views/reports/supplier-purchase-invoice-aging-drilldown.blade.php',
            $viewPaths['supplier-purchase-invoice-aging-drilldown']
        );

        $this->assertSame(
            'resources/views/reports/cash-flow-dashboard.blade.php',
            $viewPaths['cash-flow-dashboard']
        );

        $this->assertSame(
            'resources/views/reports/index.blade.php',
            $viewPaths['index']
        );

        $this->assertSame(
            'resources/views/reports/profit-loss.blade.php',
            $viewPaths['profit-loss']
        );

        $this->assertSame(
            'resources/views/reports/receivable-payable-aging-dashboard.blade.php',
            $viewPaths['receivable-payable-aging-dashboard']
        );

        $this->assertSame(
            'resources/views/reports/sales-invoice-collection-follow-ups.blade.php',
            $viewPaths['sales-invoice-collection-follow-ups']
        );

        $this->assertSame(
            'resources/views/reports/saved-view-candidates.blade.php',
            $viewPaths['saved-view-candidates']
        );

        foreach ($viewPaths as $viewPath) {
            $this->assertFileExists(base_path($viewPath));
        }
    }

    public function test_registry_route_helpers_return_asset_backed_routes(): void
    {
        foreach (ReportSavedViewRegistry::indexRoutes() as $routeName) {
            $this->assertTrue(Route::has($routeName), $routeName);
        }

        foreach (ReportSavedViewRegistry::exportRoutes() as $routeName) {
            $this->assertTrue(Route::has($routeName), $routeName);
        }

        foreach (ReportSavedViewRegistry::savedViewStoreRoutes() as $routeName) {
            $this->assertTrue(Route::has($routeName), $routeName);
        }

        $this->assertSame(
            'reports.customer-sales-invoice-aging.index',
            ReportSavedViewRegistry::indexRoute('customer-sales-invoice-aging')
        );

        $this->assertSame(
            'reports.customer-sales-invoice-aging.saved-views.store',
            ReportSavedViewRegistry::savedViewStoreRoute('customer-sales-invoice-aging')
        );

        $this->assertSame(
            'reports.customer-sales-invoice-aging.drilldown',
            ReportSavedViewRegistry::indexRoute('customer-sales-invoice-aging-drilldown')
        );

        $this->assertSame(
            'reports.customer-sales-invoice-aging.drilldown.saved-views.store',
            ReportSavedViewRegistry::savedViewStoreRoute('customer-sales-invoice-aging-drilldown')
        );

        $this->assertSame(
            'reports.supplier-purchase-invoice-aging.index',
            ReportSavedViewRegistry::indexRoute('supplier-purchase-invoice-aging')
        );

        $this->assertSame(
            'reports.supplier-purchase-invoice-aging.saved-views.store',
            ReportSavedViewRegistry::savedViewStoreRoute('supplier-purchase-invoice-aging')
        );

        $this->assertSame(
            'reports.supplier-purchase-invoice-aging.drilldown',
            ReportSavedViewRegistry::indexRoute('supplier-purchase-invoice-aging-drilldown')
        );

        $this->assertSame(
            'reports.supplier-purchase-invoice-aging.drilldown.saved-views.store',
            ReportSavedViewRegistry::savedViewStoreRoute('supplier-purchase-invoice-aging-drilldown')
        );

        $this->assertSame(
            'reports.cash-flow-dashboard.index',
            ReportSavedViewRegistry::indexRoute('cash-flow-dashboard')
        );

        $this->assertSame(
            'reports.cash-flow-dashboard.saved-views.store',
            ReportSavedViewRegistry::savedViewStoreRoute('cash-flow-dashboard')
        );

        $exportRoutes = ReportSavedViewRegistry::exportRoutes();

        $this->assertSame('reports.index', $exportRoutes['index']);

        $this->assertSame(
            'reports.index',
            ReportSavedViewRegistry::indexRoute('index')
        );

        $this->assertSame(
            'reports.index.saved-views.store',
            ReportSavedViewRegistry::savedViewStoreRoute('index')
        );

        $this->assertSame('reports.profit-loss.export', $exportRoutes['profit-loss']);

        $this->assertSame(
            'reports.profit-loss',
            ReportSavedViewRegistry::indexRoute('profit-loss')
        );

        $this->assertSame(
            'reports.profit-loss.saved-views.store',
            ReportSavedViewRegistry::savedViewStoreRoute('profit-loss')
        );

        $this->assertSame('reports.receivable-payable-aging-dashboard.export', $exportRoutes['receivable-payable-aging-dashboard']);

        $this->assertSame(
            'reports.receivable-payable-aging-dashboard.index',
            ReportSavedViewRegistry::indexRoute('receivable-payable-aging-dashboard')
        );

        $this->assertSame(
            'reports.receivable-payable-aging-dashboard.saved-views.store',
            ReportSavedViewRegistry::savedViewStoreRoute('receivable-payable-aging-dashboard')
        );

        $this->assertSame('reports.sales-invoice-collection-follow-ups.export', $exportRoutes['sales-invoice-collection-follow-ups']);

        $this->assertSame(
            'reports.sales-invoice-collection-follow-ups.index',
            ReportSavedViewRegistry::indexRoute('sales-invoice-collection-follow-ups')
        );

        $this->assertSame(
            'reports.sales-invoice-collection-follow-ups.saved-views.store',
            ReportSavedViewRegistry::savedViewStoreRoute('sales-invoice-collection-follow-ups')
        );

        $this->assertSame('reports.saved-view-candidates.json', $exportRoutes['saved-view-candidates']);

        $this->assertSame(
            'reports.saved-view-candidates.index',
            ReportSavedViewRegistry::indexRoute('saved-view-candidates')
        );

        $this->assertSame(
            'reports.saved-view-candidates.saved-views.store',
            ReportSavedViewRegistry::savedViewStoreRoute('saved-view-candidates')
        );
    }

    public function test_registry_map_helpers_return_hidden_fields_and_test_ids(): void
    {
        $hiddenFieldMap = ReportSavedViewRegistry::hiddenFieldMap();

        $this->assertSame([
            'customer_id',
            'payment_status',
            'aging_bucket',
        ], $hiddenFieldMap['sales-invoice-aging']);

        $this->assertSame([
            'customer_id',
            'aging_bucket',
        ], $hiddenFieldMap['customer-sales-invoice-aging']);

        $this->assertSame([
            'customer_id',
            'branch_id',
            'as_of_date',
            'aging_bucket',
        ], $hiddenFieldMap['customer-sales-invoice-aging-drilldown']);

        $this->assertSame([
            'supplier_id',
            'aging_bucket',
        ], $hiddenFieldMap['supplier-purchase-invoice-aging']);

        $this->assertSame([
            'supplier_id',
            'branch_id',
            'as_of_date',
            'aging_bucket',
        ], $hiddenFieldMap['supplier-purchase-invoice-aging-drilldown']);

        $this->assertSame([
            'branch_id',
            'date_from',
            'date_to',
        ], $hiddenFieldMap['cash-flow-dashboard']);

        $this->assertSame([
            'from_date',
            'to_date',
            'branch_id',
            'expense_category_id',
            'payment_method',
        ], $hiddenFieldMap['index']);

        $this->assertSame([
            'from_date',
            'to_date',
            'branch_id',
        ], $hiddenFieldMap['profit-loss']);

        $this->assertSame([
            'branch_id',
            'as_of_date',
        ], $hiddenFieldMap['receivable-payable-aging-dashboard']);

        $this->assertSame([
            'customer_id',
            'follow_up_from',
            'follow_up_to',
        ], $hiddenFieldMap['sales-invoice-collection-follow-ups']);

        $this->assertSame([], $hiddenFieldMap['saved-view-candidates']);

        $testIdMap = ReportSavedViewRegistry::testIdMap();

        $this->assertSame(
            'sales-invoice-aging-save-view-form',
            $testIdMap['sales-invoice-aging']['form']
        );

        $this->assertSame(
            'customer-aging-save-view-form',
            $testIdMap['customer-sales-invoice-aging']['form']
        );

        $this->assertSame(
            'customer-aging-drilldown-save-view-form',
            $testIdMap['customer-sales-invoice-aging-drilldown']['form']
        );

        $this->assertSame(
            'supplier-aging-save-view-form',
            $testIdMap['supplier-purchase-invoice-aging']['form']
        );

        $this->assertSame(
            'supplier-aging-drilldown-save-view-form',
            $testIdMap['supplier-purchase-invoice-aging-drilldown']['form']
        );

        $this->assertSame(
            'cash-flow-dashboard-save-view-form',
            $testIdMap['cash-flow-dashboard']['form']
        );

        $this->assertSame(
            'reports-index-save-view-form',
            $testIdMap['index']['form']
        );

        $this->assertSame(
            'profit-loss-save-view-form',
            $testIdMap['profit-loss']['form']
        );

        $this->assertSame(
            'receivable-payable-aging-dashboard-save-view-form',
            $testIdMap['receivable-payable-aging-dashboard']['form']
        );

        $this->assertSame(
            'sales-invoice-collection-follow-ups-save-view-form',
            $testIdMap['sales-invoice-collection-follow-ups']['form']
        );

        $this->assertSame(
            'saved-view-candidates-save-view-form',
            $testIdMap['saved-view-candidates']['form']
        );
    }

    public function test_registry_config_partial_helpers_return_existing_partials(): void
    {
        $configPartials = ReportSavedViewRegistry::configPartials();
        $configPartialPaths = ReportSavedViewRegistry::configPartialPaths();

        $this->assertSame(
            'reports.partials.sales-invoice-aging-saved-view-controls-config',
            $configPartials['sales-invoice-aging']
        );

        $this->assertSame(
            'reports.partials.customer-sales-invoice-aging-saved-view-controls-config',
            $configPartials['customer-sales-invoice-aging']
        );

        $this->assertSame(
            'reports.partials.customer-sales-invoice-aging-drilldown-saved-view-controls-config',
            $configPartials['customer-sales-invoice-aging-drilldown']
        );

        $this->assertSame(
            'reports.partials.supplier-purchase-invoice-aging-saved-view-controls-config',
            $configPartials['supplier-purchase-invoice-aging']
        );

        $this->assertSame(
            'reports.partials.supplier-purchase-invoice-aging-drilldown-saved-view-controls-config',
            $configPartials['supplier-purchase-invoice-aging-drilldown']
        );

        $this->assertSame(
            'reports.partials.cash-flow-dashboard-saved-view-controls-config',
            $configPartials['cash-flow-dashboard']
        );

        $this->assertSame(
            'reports.partials.index-saved-view-controls-config',
            $configPartials['index']
        );

        $this->assertSame(
            'reports.partials.profit-loss-saved-view-controls-config',
            $configPartials['profit-loss']
        );

        $this->assertSame(
            'reports.partials.receivable-payable-aging-dashboard-saved-view-controls-config',
            $configPartials['receivable-payable-aging-dashboard']
        );

        $this->assertSame(
            'reports.partials.sales-invoice-collection-follow-ups-saved-view-controls-config',
            $configPartials['sales-invoice-collection-follow-ups']
        );

        $this->assertSame(
            'reports.partials.saved-view-candidates-saved-view-controls-config',
            $configPartials['saved-view-candidates']
        );

        $this->assertSame(
            'resources/views/reports/partials/customer-sales-invoice-aging-saved-view-controls-config.blade.php',
            $configPartialPaths['customer-sales-invoice-aging']
        );

        $this->assertSame(
            'resources/views/reports/partials/customer-sales-invoice-aging-drilldown-saved-view-controls-config.blade.php',
            $configPartialPaths['customer-sales-invoice-aging-drilldown']
        );

        $this->assertSame(
            'resources/views/reports/partials/supplier-purchase-invoice-aging-saved-view-controls-config.blade.php',
            $configPartialPaths['supplier-purchase-invoice-aging']
        );

        $this->assertSame(
            'resources/views/reports/partials/supplier-purchase-invoice-aging-drilldown-saved-view-controls-config.blade.php',
            $configPartialPaths['supplier-purchase-invoice-aging-drilldown']
        );

        $this->assertSame(
            'resources/views/reports/partials/cash-flow-dashboard-saved-view-controls-config.blade.php',
            $configPartialPaths['cash-flow-dashboard']
        );

        $this->assertSame(
            'resources/views/reports/partials/index-saved-view-controls-config.blade.php',
            $configPartialPaths['index']
        );

        $this->assertSame(
            'resources/views/reports/partials/profit-loss-saved-view-controls-config.blade.php',
            $configPartialPaths['profit-loss']
        );

        $this->assertSame(
            'resources/views/reports/partials/receivable-payable-aging-dashboard-saved-view-controls-config.blade.php',
            $configPartialPaths['receivable-payable-aging-dashboard']
        );

        $this->assertSame(
            'resources/views/reports/partials/sales-invoice-collection-follow-ups-saved-view-controls-config.blade.php',
            $configPartialPaths['sales-invoice-collection-follow-ups']
        );

        $this->assertSame(
            'resources/views/reports/partials/saved-view-candidates-saved-view-controls-config.blade.php',
            $configPartialPaths['saved-view-candidates']
        );

        foreach ($configPartialPaths as $configPartialPath) {
            $this->assertFileExists(base_path($configPartialPath));
        }
    }

    public function test_registry_documentation_rows_are_complete_and_asset_backed(): void
    {
        $rows = ReportSavedViewRegistry::documentationRows();

        $this->assertCount(11, $rows);

        $rowsByKey = collect($rows)->keyBy('key');

        $this->assertTrue($rowsByKey->has('sales-invoice-aging'));
        $this->assertTrue($rowsByKey->has('customer-sales-invoice-aging'));
        $this->assertTrue($rowsByKey->has('customer-sales-invoice-aging-drilldown'));
        $this->assertTrue($rowsByKey->has('supplier-purchase-invoice-aging'));
        $this->assertTrue($rowsByKey->has('supplier-purchase-invoice-aging-drilldown'));
        $this->assertTrue($rowsByKey->has('cash-flow-dashboard'));
        $this->assertTrue($rowsByKey->has('index'));
        $this->assertTrue($rowsByKey->has('profit-loss'));
        $this->assertTrue($rowsByKey->has('receivable-payable-aging-dashboard'));
        $this->assertTrue($rowsByKey->has('sales-invoice-collection-follow-ups'));
        $this->assertTrue($rowsByKey->has('saved-view-candidates'));

        foreach ($rowsByKey as $row) {
            $this->assertNotEmpty($row['key']);
            $this->assertNotEmpty($row['label']);
            $this->assertNotEmpty($row['view_path']);
            $this->assertNotEmpty($row['config_partial_path']);
            $this->assertNotEmpty($row['index_route']);
            $this->assertNotEmpty($row['saved_view_store_route']);
            $this->assertFileExists(base_path($row['view_path']));
            $this->assertFileExists(base_path($row['config_partial_path']));
            $this->assertTrue(Route::has($row['index_route']));
            $this->assertTrue(Route::has($row['saved_view_store_route']));
        }
    }

    public function test_unknown_report_helpers_return_safe_empty_values(): void
    {
        $this->assertNull(ReportSavedViewRegistry::find('unknown-report'));
        $this->assertFalse(ReportSavedViewRegistry::has('unknown-report'));
        $this->assertSame([], ReportSavedViewRegistry::hiddenFields('unknown-report'));
        $this->assertArrayNotHasKey('unknown-report', ReportSavedViewRegistry::testIdMap());
        $this->assertNull(ReportSavedViewRegistry::configPartial('unknown-report'));
        $this->assertNull(ReportSavedViewRegistry::configPartialPath('unknown-report'));
        $this->assertNull(ReportSavedViewRegistry::indexRoute('unknown-report'));
        $this->assertNull(ReportSavedViewRegistry::savedViewStoreRoute('unknown-report'));
    }
}
