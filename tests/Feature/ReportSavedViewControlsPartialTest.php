<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewControlsPartialTest extends TestCase
{
    public function test_sales_invoice_aging_report_uses_report_specific_saved_view_controls_config_partial(): void
    {
        $reportView = resource_path('views/reports/sales-invoice-aging.blade.php');
        $configPartial = resource_path('views/reports/partials/sales-invoice-aging-saved-view-controls-config.blade.php');
        $controlsPartial = resource_path('views/reports/partials/saved-view-controls.blade.php');
        $sectionCardPartial = resource_path('views/reports/partials/saved-view-section-card.blade.php');
        $formCardPartial = resource_path('views/reports/partials/saved-view-form-card.blade.php');

        $this->assertFileExists($reportView);
        $this->assertFileExists($configPartial);
        $this->assertFileExists($controlsPartial);
        $this->assertFileExists($sectionCardPartial);
        $this->assertFileExists($formCardPartial);

        $reportContents = file_get_contents($reportView);
        $configContents = file_get_contents($configPartial);
        $controlsContents = file_get_contents($controlsPartial);

        $this->assertStringContainsString("@include('reports.partials.sales-invoice-aging-saved-view-controls-config')", $reportContents);

        $this->assertStringNotContainsString('$salesInvoiceAgingSavedViewControlsConfig = [', $reportContents);
        $this->assertStringNotContainsString("@include('reports.partials.saved-view-controls', \$salesInvoiceAgingSavedViewControlsConfig)", $reportContents);
        $this->assertStringNotContainsString("@include('reports.partials.saved-view-controls', [", $reportContents);
        $this->assertStringNotContainsString("@include('reports.partials.saved-view-section-card'", $reportContents);
        $this->assertStringNotContainsString("@include('reports.partials.saved-view-form-card'", $reportContents);

        $this->assertStringContainsString('$salesInvoiceAgingSavedViewControlsConfig = [', $configContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-controls', \$salesInvoiceAgingSavedViewControlsConfig)", $configContents);
        $this->assertStringContainsString("'savedViews' => \$savedViews ?? collect()", $configContents);
        $this->assertStringContainsString("'section' => [", $configContents);
        $this->assertStringContainsString("'form' => [", $configContents);
        $this->assertStringContainsString("'hiddenFields' => [", $configContents);
        $this->assertStringContainsString("'routeName' => 'reports.sales-invoice-aging.index'", $configContents);
        $this->assertStringContainsString("'storeRouteName' => 'reports.sales-invoice-aging.saved-views.store'", $configContents);

        $this->assertStringContainsString('$savedViewControlsCollection = $savedViews ?? collect();', $controlsContents);
        $this->assertStringContainsString('$sectionConfig = array_replace([', $controlsContents);
        $this->assertStringContainsString('$formConfig = array_replace([', $controlsContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-section-card'", $controlsContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-form-card'", $controlsContents);
    }
}
