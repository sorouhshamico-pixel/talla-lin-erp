<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewRegistry;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewRegistryMetadataHelpersTest extends TestCase
{
    public function test_registry_metadata_helpers_return_expected_report_values(): void
    {
        $this->assertSame(3, ReportSavedViewRegistry::count());

        $keys = ReportSavedViewRegistry::keys();

        $this->assertContains('sales-invoice-aging', $keys);
        $this->assertContains('customer-sales-invoice-aging', $keys);
        $this->assertContains('customer-sales-invoice-aging-drilldown', $keys);

        $labels = ReportSavedViewRegistry::labels();

        $this->assertSame('تقرير أعمار ذمم فواتير المبيعات', $labels['sales-invoice-aging']);
        $this->assertSame('تقرير أعمار ذمم العملاء', $labels['customer-sales-invoice-aging']);
        $this->assertSame('تفاصيل فواتير العملاء المفتوحة', $labels['customer-sales-invoice-aging-drilldown']);

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
            'resources/views/reports/partials/customer-sales-invoice-aging-saved-view-controls-config.blade.php',
            $configPartialPaths['customer-sales-invoice-aging']
        );

        $this->assertSame(
            'resources/views/reports/partials/customer-sales-invoice-aging-drilldown-saved-view-controls-config.blade.php',
            $configPartialPaths['customer-sales-invoice-aging-drilldown']
        );

        foreach ($configPartialPaths as $configPartialPath) {
            $this->assertFileExists(base_path($configPartialPath));
        }
    }

    public function test_registry_documentation_rows_are_complete_and_asset_backed(): void
    {
        $rows = ReportSavedViewRegistry::documentationRows();

        $this->assertCount(3, $rows);

        $rowsByKey = collect($rows)->keyBy('key');

        $this->assertTrue($rowsByKey->has('sales-invoice-aging'));
        $this->assertTrue($rowsByKey->has('customer-sales-invoice-aging'));
        $this->assertTrue($rowsByKey->has('customer-sales-invoice-aging-drilldown'));

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
