<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewRegistry;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewRegistryMetadataHelpersTest extends TestCase
{
    public function test_registry_metadata_helpers_return_expected_sales_invoice_aging_values(): void
    {
        $this->assertSame(1, ReportSavedViewRegistry::count());
        $this->assertSame(['sales-invoice-aging'], ReportSavedViewRegistry::keys());

        $this->assertSame([
            'sales-invoice-aging' => 'تقرير أعمار ذمم فواتير المبيعات',
        ], ReportSavedViewRegistry::labels());

        $this->assertSame([
            'sales-invoice-aging' => 'resources/views/reports/sales-invoice-aging.blade.php',
        ], ReportSavedViewRegistry::viewPaths());

        $this->assertSame([
            'sales-invoice-aging' => 'reports.partials.sales-invoice-aging-saved-view-controls-config',
        ], ReportSavedViewRegistry::configPartials());

        $this->assertSame([
            'sales-invoice-aging' => 'resources/views/reports/partials/sales-invoice-aging-saved-view-controls-config.blade.php',
        ], ReportSavedViewRegistry::configPartialPaths());

        $this->assertSame([
            'sales-invoice-aging' => 'reports.sales-invoice-aging.index',
        ], ReportSavedViewRegistry::indexRoutes());

        $this->assertSame([
            'sales-invoice-aging' => 'reports.sales-invoice-aging.export',
        ], ReportSavedViewRegistry::exportRoutes());

        $this->assertSame([
            'sales-invoice-aging' => 'reports.sales-invoice-aging.saved-views.store',
        ], ReportSavedViewRegistry::savedViewStoreRoutes());
    }

    public function test_registry_map_helpers_return_hidden_fields_and_test_ids(): void
    {
        $this->assertSame([
            'sales-invoice-aging' => [
                'customer_id',
                'payment_status',
                'aging_bucket',
            ],
        ], ReportSavedViewRegistry::hiddenFieldMap());

        $testIdMap = ReportSavedViewRegistry::testIdMap();

        $this->assertArrayHasKey('sales-invoice-aging', $testIdMap);
        $this->assertSame('sales-invoice-aging-saved-views-selector', $testIdMap['sales-invoice-aging']['section_card']);
        $this->assertSame('sales-invoice-aging-save-view-card', $testIdMap['sales-invoice-aging']['form_card']);
        $this->assertSame('sales-invoice-aging-save-view-form', $testIdMap['sales-invoice-aging']['form']);
        $this->assertSame('sales-invoice-aging-saved-view-name-input', $testIdMap['sales-invoice-aging']['name_input']);
        $this->assertSame('sales-invoice-aging-saved-view-default-checkbox', $testIdMap['sales-invoice-aging']['default_checkbox']);
        $this->assertSame('sales-invoice-aging-save-view-button', $testIdMap['sales-invoice-aging']['save_button']);
    }

    public function test_registry_single_report_helper_methods_return_safe_values(): void
    {
        $this->assertSame(
            'reports.partials.sales-invoice-aging-saved-view-controls-config',
            ReportSavedViewRegistry::configPartial('sales-invoice-aging')
        );

        $this->assertSame(
            'resources/views/reports/partials/sales-invoice-aging-saved-view-controls-config.blade.php',
            ReportSavedViewRegistry::configPartialPath('sales-invoice-aging')
        );

        $this->assertSame(
            'reports.sales-invoice-aging.index',
            ReportSavedViewRegistry::indexRoute('sales-invoice-aging')
        );

        $this->assertSame(
            'reports.sales-invoice-aging.saved-views.store',
            ReportSavedViewRegistry::savedViewStoreRoute('sales-invoice-aging')
        );

        $this->assertNull(ReportSavedViewRegistry::configPartialPath('missing-report'));
        $this->assertNull(ReportSavedViewRegistry::indexRoute('missing-report'));
        $this->assertNull(ReportSavedViewRegistry::savedViewStoreRoute('missing-report'));
    }

    public function test_registry_documentation_rows_are_complete_and_asset_backed(): void
    {
        $rows = ReportSavedViewRegistry::documentationRows();

        $this->assertCount(1, $rows);

        $row = $rows[0];

        $this->assertSame('sales-invoice-aging', $row['key']);
        $this->assertSame('تقرير أعمار ذمم فواتير المبيعات', $row['label']);
        $this->assertSame('resources/views/reports/sales-invoice-aging.blade.php', $row['view_path']);
        $this->assertSame('reports.sales-invoice-aging.index', $row['index_route']);
        $this->assertSame('reports.sales-invoice-aging.saved-views.store', $row['saved_view_store_route']);
        $this->assertSame('resources/views/reports/partials/sales-invoice-aging-saved-view-controls-config.blade.php', $row['config_partial_path']);
        $this->assertSame(['customer_id', 'payment_status', 'aging_bucket'], $row['hidden_fields']);

        $this->assertFileExists(base_path($row['view_path']));
        $this->assertFileExists(base_path($row['config_partial_path']));
        $this->assertTrue(Route::has($row['index_route']));
        $this->assertTrue(Route::has($row['saved_view_store_route']));
    }

    public function test_phase_56a_metadata_helpers_are_documented(): void
    {
        $doc = base_path('docs/phase-56-report-saved-view-registry-metadata-helpers.md');

        $this->assertFileExists($doc);

        $contents = file_get_contents($doc);

        $this->assertStringContainsString('Phase 56A', $contents);
        $this->assertStringContainsString('Report Saved View Registry Metadata Helpers', $contents);
        $this->assertStringContainsString('documentationRows', $contents);
        $this->assertStringContainsString('hiddenFieldMap', $contents);
        $this->assertStringContainsString('testIdMap', $contents);
        $this->assertStringContainsString('ReportSavedViewRegistryMetadataHelpersTest', $contents);
    }
}
