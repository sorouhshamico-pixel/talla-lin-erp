<?php

namespace Tests\Feature;

use Tests\TestCase;

class SalesInvoiceAgingSavedViewInlineMarkupGuardTest extends TestCase
{
    public function test_sales_invoice_aging_report_does_not_inline_saved_view_controls_markup_or_config(): void
    {
        $reportView = resource_path('views/reports/sales-invoice-aging.blade.php');

        $this->assertFileExists($reportView);

        $contents = file_get_contents($reportView);

        $this->assertStringContainsString("@include('reports.partials.sales-invoice-aging-saved-view-controls-config')", $contents);

        $this->assertStringNotContainsString('$salesInvoiceAgingSavedViewControlsConfig = [', $contents);
        $this->assertStringNotContainsString('$salesInvoiceAgingSavedViews = $savedViews ?? collect();', $contents);

        $this->assertStringNotContainsString("@include('reports.partials.saved-view-controls'", $contents);
        $this->assertStringNotContainsString("@include('reports.partials.saved-view-section-card'", $contents);
        $this->assertStringNotContainsString("@include('reports.partials.saved-view-section'", $contents);
        $this->assertStringNotContainsString("@include('reports.partials.saved-view-form-card'", $contents);
        $this->assertStringNotContainsString("@include('reports.partials.saved-view-form-fields'", $contents);
        $this->assertStringNotContainsString("@include('reports.partials.saved-view-hidden-fields'", $contents);

        $this->assertStringNotContainsString('data-testid="sales-invoice-aging-saved-views-selector"', $contents);
        $this->assertStringNotContainsString('data-testid="sales-invoice-aging-save-view-card"', $contents);
        $this->assertStringNotContainsString('data-testid="sales-invoice-aging-save-view-form"', $contents);
        $this->assertStringNotContainsString('data-testid="sales-invoice-aging-saved-view-name-input"', $contents);
        $this->assertStringNotContainsString('data-testid="sales-invoice-aging-saved-view-default-checkbox"', $contents);
        $this->assertStringNotContainsString('data-testid="sales-invoice-aging-save-view-button"', $contents);

        $this->assertStringContainsString('data-testid="sales-invoice-aging-total-card"', $contents);
        $this->assertStringContainsString('data-testid="sales-invoice-aging-summary-card"', $contents);
        $this->assertStringContainsString('data-testid="sales-invoice-aging-invoices-card"', $contents);
    }
}
