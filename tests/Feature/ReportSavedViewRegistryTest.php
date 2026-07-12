<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewRegistry;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewRegistryTest extends TestCase
{
    public function test_registry_contains_sales_invoice_aging_report(): void
    {
        $this->assertTrue(ReportSavedViewRegistry::has('sales-invoice-aging'));

        $report = ReportSavedViewRegistry::find('sales-invoice-aging');

        $this->assertIsArray($report);
        $this->assertSame('sales-invoice-aging', $report['key']);
        $this->assertSame('تقرير أعمار ذمم فواتير المبيعات', $report['label']);
        $this->assertSame('reports.sales-invoice-aging', $report['view']);
        $this->assertSame('reports.sales-invoice-aging.index', $report['index_route']);
        $this->assertSame('reports.sales-invoice-aging.export', $report['export_route']);
        $this->assertSame('reports.sales-invoice-aging.saved-views.store', $report['saved_view_store_route']);
        $this->assertSame('reports.partials.sales-invoice-aging-saved-view-controls-config', $report['config_partial']);
    }

    public function test_registry_paths_and_routes_exist(): void
    {
        $report = ReportSavedViewRegistry::find('sales-invoice-aging');

        $this->assertFileExists(base_path($report['view_path']));
        $this->assertFileExists(base_path($report['config_partial_path']));

        $this->assertTrue(Route::has($report['index_route']));
        $this->assertTrue(Route::has($report['export_route']));
        $this->assertTrue(Route::has($report['saved_view_store_route']));
    }

    public function test_registry_hidden_fields_match_sales_invoice_aging_config_partial(): void
    {
        $hiddenFields = ReportSavedViewRegistry::hiddenFields('sales-invoice-aging');

        $this->assertSame([
            'customer_id',
            'payment_status',
            'aging_bucket',
        ], $hiddenFields);

        $report = ReportSavedViewRegistry::find('sales-invoice-aging');
        $configContents = file_get_contents(base_path($report['config_partial_path']));

        foreach ($hiddenFields as $hiddenField) {
            $this->assertStringContainsString("'{$hiddenField}'", $configContents);
        }
    }

    public function test_registry_test_ids_match_rendered_config_contract(): void
    {
        $report = ReportSavedViewRegistry::find('sales-invoice-aging');
        $configContents = file_get_contents(base_path($report['config_partial_path']));

        foreach ($report['test_ids'] as $testId) {
            $this->assertStringContainsString($testId, $configContents);
        }
    }

    public function test_registry_documentation_exists(): void
    {
        $doc = base_path('docs/phase-55-report-saved-view-registry.md');

        $this->assertFileExists($doc);

        $contents = file_get_contents($doc);

        $this->assertStringContainsString('Phase 55B', $contents);
        $this->assertStringContainsString('Report Saved View Registry', $contents);
        $this->assertStringContainsString('ReportSavedViewRegistry.php', $contents);
        $this->assertStringContainsString('sales-invoice-aging', $contents);
        $this->assertStringContainsString('customer_id', $contents);
        $this->assertStringContainsString('payment_status', $contents);
        $this->assertStringContainsString('aging_bucket', $contents);
        $this->assertStringContainsString('ReportSavedViewRegistryTest', $contents);
    }

    public function test_unknown_report_returns_safe_empty_values(): void
    {
        $this->assertFalse(ReportSavedViewRegistry::has('unknown-report'));
        $this->assertNull(ReportSavedViewRegistry::find('unknown-report'));
        $this->assertSame([], ReportSavedViewRegistry::hiddenFields('unknown-report'));
        $this->assertNull(ReportSavedViewRegistry::configPartial('unknown-report'));
    }
}
