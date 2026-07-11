<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewFormFieldsPartialTest extends TestCase
{
    public function test_sales_invoice_aging_report_uses_saved_view_form_fields_partial(): void
    {
        $reportView = resource_path('views/reports/sales-invoice-aging.blade.php');
        $partialView = resource_path('views/reports/partials/saved-view-form-fields.blade.php');

        $this->assertFileExists($partialView);

        $reportContents = file_get_contents($reportView);
        $partialContents = file_get_contents($partialView);

        $this->assertStringContainsString("@include('reports.partials.saved-view-form-fields'", $reportContents);

        $this->assertStringContainsString("'nameInputId' => 'sales_invoice_aging_saved_view_name'", $reportContents);
        $this->assertStringContainsString("'nameInputTestId' => 'sales-invoice-aging-saved-view-name-input'", $reportContents);
        $this->assertStringContainsString("'defaultCheckboxTestId' => 'sales-invoice-aging-saved-view-default-checkbox'", $reportContents);
        $this->assertStringContainsString("'saveButtonTestId' => 'sales-invoice-aging-save-view-button'", $reportContents);

        $this->assertStringNotContainsString('id="sales_invoice_aging_saved_view_name"', $reportContents);
        $this->assertStringNotContainsString('data-testid="sales-invoice-aging-saved-view-name-input"', $reportContents);
        $this->assertStringNotContainsString('data-testid="sales-invoice-aging-saved-view-default-checkbox"', $reportContents);
        $this->assertStringNotContainsString('data-testid="sales-invoice-aging-save-view-button"', $reportContents);

        $this->assertStringContainsString('name="name"', $partialContents);
        $this->assertStringContainsString('name="is_default"', $partialContents);
        $this->assertStringContainsString('type="submit"', $partialContents);

        $this->assertStringContainsString('$nameInputId', $partialContents);
        $this->assertStringContainsString('$namePlaceholder', $partialContents);
        $this->assertStringContainsString('$nameInputTestId', $partialContents);
        $this->assertStringContainsString('$defaultCheckboxTestId', $partialContents);
        $this->assertStringContainsString('$saveButtonTestId', $partialContents);
    }
}
