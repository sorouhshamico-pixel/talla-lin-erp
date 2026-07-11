<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewControlsConfigTest extends TestCase
{
    public function test_sales_invoice_aging_saved_view_controls_config_keeps_all_report_specific_options_together(): void
    {
        $reportView = resource_path('views/reports/sales-invoice-aging.blade.php');

        $this->assertFileExists($reportView);

        $reportContents = file_get_contents($reportView);

        $this->assertStringContainsString('$salesInvoiceAgingSavedViewControlsConfig = [', $reportContents);

        $expectedKeys = [
            "'savedViews'",
            "'sectionCardTestId'",
            "'sectionRouteName'",
            "'sectionEmptyTestId'",
            "'sectionListTestId'",
            "'sectionItemTestId'",
            "'sectionOpenLinkTestId'",
            "'sectionActiveBadgeTestId'",
            "'sectionDefaultBadgeTestId'",
            "'sectionManageLinkTestId'",
            "'formCardTestId'",
            "'formTitle'",
            "'formStoreRouteName'",
            "'formTestId'",
            "'hiddenFields'",
            "'nameInputId'",
            "'namePlaceholder'",
            "'nameInputTestId'",
            "'defaultCheckboxTestId'",
            "'saveButtonTestId'",
        ];

        foreach ($expectedKeys as $expectedKey) {
            $this->assertStringContainsString($expectedKey, $reportContents);
        }

        $this->assertStringContainsString("@include('reports.partials.saved-view-controls', \$salesInvoiceAgingSavedViewControlsConfig)", $reportContents);
    }
}
