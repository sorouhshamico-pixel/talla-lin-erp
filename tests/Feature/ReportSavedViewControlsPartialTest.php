<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewControlsPartialTest extends TestCase
{
    public function test_sales_invoice_aging_report_uses_saved_view_controls_partial_with_grouped_config(): void
    {
        $reportView = resource_path('views/reports/sales-invoice-aging.blade.php');
        $controlsPartial = resource_path('views/reports/partials/saved-view-controls.blade.php');
        $sectionCardPartial = resource_path('views/reports/partials/saved-view-section-card.blade.php');
        $formCardPartial = resource_path('views/reports/partials/saved-view-form-card.blade.php');

        $this->assertFileExists($reportView);
        $this->assertFileExists($controlsPartial);
        $this->assertFileExists($sectionCardPartial);
        $this->assertFileExists($formCardPartial);

        $reportContents = file_get_contents($reportView);
        $controlsContents = file_get_contents($controlsPartial);

        $this->assertStringContainsString('$salesInvoiceAgingSavedViewControlsConfig = [', $reportContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-controls', \$salesInvoiceAgingSavedViewControlsConfig)", $reportContents);

        $this->assertStringContainsString("'savedViews' => \$savedViews ?? collect()", $reportContents);
        $this->assertStringContainsString("'section' => [", $reportContents);
        $this->assertStringContainsString("'form' => [", $reportContents);
        $this->assertStringContainsString("'hiddenFields' => [", $reportContents);

        $this->assertStringContainsString("'routeName' => 'reports.sales-invoice-aging.index'", $reportContents);
        $this->assertStringContainsString("'storeRouteName' => 'reports.sales-invoice-aging.saved-views.store'", $reportContents);
        $this->assertStringContainsString("'customer_id' => \$customerFilter", $reportContents);
        $this->assertStringContainsString("'payment_status' => \$paymentStatusFilter", $reportContents);
        $this->assertStringContainsString("'aging_bucket' => \$agingBucketFilter", $reportContents);

        $this->assertStringNotContainsString("'sectionRouteName'", $reportContents);
        $this->assertStringNotContainsString("'formStoreRouteName'", $reportContents);
        $this->assertStringNotContainsString("@include('reports.partials.saved-view-controls', [", $reportContents);

        $this->assertStringContainsString('$savedViewControlsCollection = $savedViews ?? collect();', $controlsContents);
        $this->assertStringContainsString('$sectionConfig = $section ?? [];', $controlsContents);
        $this->assertStringContainsString('$formConfig = $form ?? [];', $controlsContents);

        $this->assertStringContainsString("@include('reports.partials.saved-view-section-card'", $controlsContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-form-card'", $controlsContents);
        $this->assertStringContainsString('$sectionConfig[\'routeName\']', $controlsContents);
        $this->assertStringContainsString('$formConfig[\'storeRouteName\']', $controlsContents);
        $this->assertStringContainsString("'hiddenFields' => \$hiddenFields ?? []", $controlsContents);
    }
}
