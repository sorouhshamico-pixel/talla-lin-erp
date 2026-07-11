<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewControlsConfigTest extends TestCase
{
    public function test_sales_invoice_aging_saved_view_controls_config_uses_grouped_sections(): void
    {
        $reportView = resource_path('views/reports/sales-invoice-aging.blade.php');

        $this->assertFileExists($reportView);

        $reportContents = file_get_contents($reportView);

        $this->assertStringContainsString('$salesInvoiceAgingSavedViewControlsConfig = [', $reportContents);
        $this->assertStringContainsString("'savedViews' => \$savedViews ?? collect()", $reportContents);
        $this->assertStringContainsString("'section' => [", $reportContents);
        $this->assertStringContainsString("'form' => [", $reportContents);
        $this->assertStringContainsString("'hiddenFields' => [", $reportContents);

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
            $this->assertStringContainsString($sectionKey, $reportContents);
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
            $this->assertStringContainsString($formKey, $reportContents);
        }

        $this->assertStringContainsString("'customer_id' => \$customerFilter", $reportContents);
        $this->assertStringContainsString("'payment_status' => \$paymentStatusFilter", $reportContents);
        $this->assertStringContainsString("'aging_bucket' => \$agingBucketFilter", $reportContents);

        $this->assertStringNotContainsString("'sectionRouteName'", $reportContents);
        $this->assertStringNotContainsString("'formStoreRouteName'", $reportContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-controls', \$salesInvoiceAgingSavedViewControlsConfig)", $reportContents);
    }
}
