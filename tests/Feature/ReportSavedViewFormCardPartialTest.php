<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewFormCardPartialTest extends TestCase
{
    public function test_sales_invoice_aging_report_uses_saved_view_form_card_partial(): void
    {
        $reportView = resource_path('views/reports/sales-invoice-aging.blade.php');
        $formCardPartial = resource_path('views/reports/partials/saved-view-form-card.blade.php');
        $formFieldsPartial = resource_path('views/reports/partials/saved-view-form-fields.blade.php');
        $hiddenFieldsPartial = resource_path('views/reports/partials/saved-view-hidden-fields.blade.php');

        $this->assertFileExists($formCardPartial);
        $this->assertFileExists($formFieldsPartial);
        $this->assertFileExists($hiddenFieldsPartial);

        $reportContents = file_get_contents($reportView);
        $formCardContents = file_get_contents($formCardPartial);

        $this->assertStringContainsString("@include('reports.partials.saved-view-form-card'", $reportContents);

        $this->assertStringContainsString("'cardTestId' => 'sales-invoice-aging-save-view-card'", $reportContents);
        $this->assertStringContainsString("'storeRouteName' => 'reports.sales-invoice-aging.saved-views.store'", $reportContents);
        $this->assertStringContainsString("'formTestId' => 'sales-invoice-aging-save-view-form'", $reportContents);
        $this->assertStringContainsString("'customer_id' => \$customerFilter", $reportContents);
        $this->assertStringContainsString("'payment_status' => \$paymentStatusFilter", $reportContents);
        $this->assertStringContainsString("'aging_bucket' => \$agingBucketFilter", $reportContents);
        $this->assertStringContainsString("'nameInputTestId' => 'sales-invoice-aging-saved-view-name-input'", $reportContents);
        $this->assertStringContainsString("'defaultCheckboxTestId' => 'sales-invoice-aging-saved-view-default-checkbox'", $reportContents);
        $this->assertStringContainsString("'saveButtonTestId' => 'sales-invoice-aging-save-view-button'", $reportContents);

        $this->assertStringNotContainsString('<form method="POST"', $reportContents);
        $this->assertStringNotContainsString("reports.partials.saved-view-form-fields", $reportContents);

        $this->assertStringContainsString('data-testid="{{ $cardTestId', $formCardContents);
        $this->assertStringContainsString('action="{{ route($storeRouteName) }}"', $formCardContents);
        $this->assertStringContainsString('data-testid="{{ $formTestId', $formCardContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-hidden-fields'", $formCardContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-form-fields'", $formCardContents);

        $this->assertStringNotContainsString('@foreach (($hiddenFields ?? []) as $hiddenFieldName => $hiddenFieldValue)', $formCardContents);
        $this->assertStringNotContainsString('name="{{ $hiddenFieldName }}"', $formCardContents);
        $this->assertStringNotContainsString('value="{{ $hiddenFieldValue }}"', $formCardContents);
    }
}
