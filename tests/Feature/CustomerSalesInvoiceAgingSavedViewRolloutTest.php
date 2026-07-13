<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewCandidateScanner;
use App\Support\Reports\ReportSavedViewRegistry;
use App\Support\Reports\ReportSavedViewRegistryValidator;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CustomerSalesInvoiceAgingSavedViewRolloutTest extends TestCase
{
    public function test_customer_sales_invoice_aging_is_registered_for_saved_views(): void
    {
        $report = ReportSavedViewRegistry::find('customer-sales-invoice-aging');

        $this->assertIsArray($report);
        $this->assertSame('customer-sales-invoice-aging', $report['key']);
        $this->assertSame('تقرير أعمار ذمم العملاء', $report['label']);
        $this->assertSame('reports.customer-sales-invoice-aging', $report['view']);
        $this->assertSame('resources/views/reports/customer-sales-invoice-aging.blade.php', $report['view_path']);
        $this->assertSame('reports.customer-sales-invoice-aging.index', $report['index_route']);
        $this->assertSame('reports.customer-sales-invoice-aging.export', $report['export_route']);
        $this->assertSame('reports.customer-sales-invoice-aging.saved-views.store', $report['saved_view_store_route']);
        $this->assertSame(
            'reports.partials.customer-sales-invoice-aging-saved-view-controls-config',
            $report['config_partial']
        );
        $this->assertSame([
            'customer_id',
            'aging_bucket',
        ], $report['hidden_fields']);
    }

    public function test_customer_sales_invoice_aging_registry_routes_exist(): void
    {
        $report = ReportSavedViewRegistry::find('customer-sales-invoice-aging');

        $this->assertTrue(Route::has($report['index_route']));
        $this->assertTrue(Route::has($report['export_route']));
        $this->assertTrue(Route::has($report['saved_view_store_route']));
    }

    public function test_customer_sales_invoice_aging_config_partial_exists_and_uses_shared_controls(): void
    {
        $partial = base_path('resources/views/reports/partials/customer-sales-invoice-aging-saved-view-controls-config.blade.php');

        $this->assertFileExists($partial);

        $contents = file_get_contents($partial);

        $this->assertStringContainsString('$customerSalesInvoiceAgingSavedViewControlsConfig', $contents);
        $this->assertStringContainsString("'routeName' => 'reports.customer-sales-invoice-aging.index'", $contents);
        $this->assertStringContainsString("'storeRouteName' => 'reports.customer-sales-invoice-aging.saved-views.store'", $contents);
        $this->assertStringContainsString("'customer_id' => \$customerFilter", $contents);
        $this->assertStringContainsString("'aging_bucket' => \$agingBucketFilter", $contents);
        $this->assertStringContainsString("reports.partials.saved-view-controls", $contents);
        $this->assertStringContainsString("customer-aging-saved-views-selector", $contents);
        $this->assertStringContainsString("customer-aging-save-view-card", $contents);
        $this->assertStringContainsString("customer-aging-save-view-form", $contents);
    }

    public function test_customer_sales_invoice_aging_view_uses_config_partial_without_inline_saved_view_markup(): void
    {
        $view = base_path('resources/views/reports/customer-sales-invoice-aging.blade.php');

        $this->assertFileExists($view);

        $contents = file_get_contents($view);

        $this->assertStringContainsString(
            "@include('reports.partials.customer-sales-invoice-aging-saved-view-controls-config')",
            $contents
        );

        $this->assertStringNotContainsString("@include('reports.partials.saved-view-section'", $contents);
        $this->assertStringNotContainsString('customer-aging-saved-view-name-input" name="name"', $contents);
    }

    public function test_customer_sales_invoice_aging_candidate_scanner_marks_report_as_registered(): void
    {
        $candidate = collect(ReportSavedViewCandidateScanner::candidates())
            ->firstWhere('key', 'customer-sales-invoice-aging');

        $this->assertNotNull($candidate);
        $this->assertTrue($candidate['registered']);

        $this->assertNotContains(
            'customer-sales-invoice-aging',
            array_column(ReportSavedViewCandidateScanner::unregisteredCandidates(), 'key')
        );
    }

    public function test_customer_sales_invoice_aging_registry_validator_has_no_errors_for_report(): void
    {
        $this->assertSame([], ReportSavedViewRegistryValidator::errorsFor('customer-sales-invoice-aging'));
    }

    public function test_phase_63d_customer_sales_invoice_aging_rollout_is_documented(): void
    {
        $doc = base_path('docs/phase-63-customer-sales-invoice-aging-saved-view-rollout.md');

        $this->assertFileExists($doc);

        $contents = file_get_contents($doc);

        $this->assertStringContainsString('Phase 63D', $contents);
        $this->assertStringContainsString('Customer Sales Invoice Aging Saved View Rollout', $contents);
        $this->assertStringContainsString('customer-sales-invoice-aging', $contents);
        $this->assertStringContainsString('CustomerSalesInvoiceAgingSavedViewRolloutTest', $contents);
    }
}
