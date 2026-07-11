<?php

namespace Tests\Feature;

use Tests\TestCase;

class SalesInvoiceAgingSavedViewControlsConfigPartialTest extends TestCase
{
    public function test_sales_invoice_aging_saved_view_controls_config_partial_contains_report_specific_configuration_and_renders_controls(): void
    {
        $reportView = resource_path('views/reports/sales-invoice-aging.blade.php');
        $configPartial = resource_path('views/reports/partials/sales-invoice-aging-saved-view-controls-config.blade.php');

        $this->assertFileExists($reportView);
        $this->assertFileExists($configPartial);

        $reportContents = file_get_contents($reportView);
        $configContents = file_get_contents($configPartial);

        $this->assertStringContainsString("@include('reports.partials.sales-invoice-aging-saved-view-controls-config')", $reportContents);
        $this->assertStringContainsString('$salesInvoiceAgingSavedViewControlsConfig = [', $configContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-controls', \$salesInvoiceAgingSavedViewControlsConfig)", $configContents);

        $this->assertStringContainsString("'section' => [", $configContents);
        $this->assertStringContainsString("'form' => [", $configContents);
        $this->assertStringContainsString("'hiddenFields' => [", $configContents);

        $this->assertStringContainsString("'routeName' => 'reports.sales-invoice-aging.index'", $configContents);
        $this->assertStringContainsString("'storeRouteName' => 'reports.sales-invoice-aging.saved-views.store'", $configContents);

        $this->assertStringContainsString("'customer_id' => \$customerFilter", $configContents);
        $this->assertStringContainsString("'payment_status' => \$paymentStatusFilter", $configContents);
        $this->assertStringContainsString("'aging_bucket' => \$agingBucketFilter", $configContents);

        $this->assertStringNotContainsString('<html', $configContents);
        $this->assertStringNotContainsString('<form', $configContents);
        $this->assertStringNotContainsString('<div class="card"', $configContents);
    }
}
