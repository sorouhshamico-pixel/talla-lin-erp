<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewControlsConfigTest extends TestCase
{
    public function test_sales_invoice_aging_saved_view_controls_config_is_loaded_and_rendered_from_report_specific_partial(): void
    {
        $reportView = resource_path('views/reports/sales-invoice-aging.blade.php');
        $configPartial = resource_path('views/reports/partials/sales-invoice-aging-saved-view-controls-config.blade.php');

        $this->assertFileExists($reportView);
        $this->assertFileExists($configPartial);

        $reportContents = file_get_contents($reportView);
        $configContents = file_get_contents($configPartial);

        $this->assertStringContainsString("@include('reports.partials.sales-invoice-aging-saved-view-controls-config')", $reportContents);
        $this->assertStringNotContainsString('$salesInvoiceAgingSavedViewControlsConfig = [', $reportContents);
        $this->assertStringNotContainsString("@include('reports.partials.saved-view-controls', \$salesInvoiceAgingSavedViewControlsConfig)", $reportContents);

        $this->assertStringContainsString('$salesInvoiceAgingSavedViewControlsConfig = [', $configContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-controls', \$salesInvoiceAgingSavedViewControlsConfig)", $configContents);
        $this->assertStringContainsString("'savedViews' => \$savedViews ?? collect()", $configContents);
        $this->assertStringContainsString("'section' => [", $configContents);
        $this->assertStringContainsString("'form' => [", $configContents);
        $this->assertStringContainsString("'hiddenFields' => [", $configContents);

        $sectionKeys = [
            "'cardTestId' => 'sales-invoice-aging-saved-views-selector'",
            "'routeName' => 'reports.sales-invoice-aging.index'",
            "'emptyTestId' => 'sales-invoice-aging-saved-views-empty'",
            "'listTestId' => 'sales-invoice-aging-saved-views-list'",
            "'itemTestId' => 'sales-invoice-aging-saved-view-item'",
            "'openLinkTestId' => 'sales-invoice-aging-saved-view-open-link'",
            "'activeBadgeTestId' => 'sales-invoice-aging-saved-view-active-badge'",
            "'defaultBadgeTestId' => 'sales-invoice-aging-saved-view-default-badge'",
            "'manageLinkTestId' => 'sales-invoice-aging-manage-saved-views-link'",
        ];

        foreach ($sectionKeys as $sectionKey) {
            $this->assertStringContainsString($sectionKey, $configContents);
        }

        $formKeys = [
            "'cardTestId' => 'sales-invoice-aging-save-view-card'",
            "'title' => 'حفظ عرض التقرير'",
            "'storeRouteName' => 'reports.sales-invoice-aging.saved-views.store'",
            "'testId' => 'sales-invoice-aging-save-view-form'",
            "'nameInputId' => 'sales_invoice_aging_saved_view_name'",
            "'namePlaceholder' => 'مثال: متابعة التحصيل الجزئي'",
            "'nameInputTestId' => 'sales-invoice-aging-saved-view-name-input'",
            "'defaultCheckboxTestId' => 'sales-invoice-aging-saved-view-default-checkbox'",
            "'saveButtonTestId' => 'sales-invoice-aging-save-view-button'",
        ];

        foreach ($formKeys as $formKey) {
            $this->assertStringContainsString($formKey, $configContents);
        }

        $this->assertStringContainsString("'customer_id' => \$customerFilter", $configContents);
        $this->assertStringContainsString("'payment_status' => \$paymentStatusFilter", $configContents);
        $this->assertStringContainsString("'aging_bucket' => \$agingBucketFilter", $configContents);

        $this->assertStringNotContainsString("'sectionRouteName'", $configContents);
        $this->assertStringNotContainsString("'formStoreRouteName'", $configContents);
    }
}
