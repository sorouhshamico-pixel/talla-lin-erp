<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewSectionCardPartialTest extends TestCase
{
    public function test_sales_invoice_aging_report_uses_saved_view_section_card_partial(): void
    {
        $reportView = resource_path('views/reports/sales-invoice-aging.blade.php');
        $sectionCardPartial = resource_path('views/reports/partials/saved-view-section-card.blade.php');
        $sectionPartial = resource_path('views/reports/partials/saved-view-section.blade.php');

        $this->assertFileExists($reportView);
        $this->assertFileExists($sectionCardPartial);
        $this->assertFileExists($sectionPartial);

        $reportContents = file_get_contents($reportView);
        $sectionCardContents = file_get_contents($sectionCardPartial);

        $this->assertStringContainsString("@include('reports.partials.saved-view-section-card'", $reportContents);
        $this->assertStringContainsString("'cardTestId' => 'sales-invoice-aging-saved-views-selector'", $reportContents);
        $this->assertStringContainsString("'savedViews' => \$salesInvoiceAgingSavedViews", $reportContents);
        $this->assertStringContainsString("'routeName' => 'reports.sales-invoice-aging.index'", $reportContents);
        $this->assertStringContainsString("'emptyTestId' => 'sales-invoice-aging-saved-views-empty'", $reportContents);
        $this->assertStringContainsString("'listTestId' => 'sales-invoice-aging-saved-views-list'", $reportContents);
        $this->assertStringContainsString("'itemTestId' => 'sales-invoice-aging-saved-view-item'", $reportContents);
        $this->assertStringContainsString("'openLinkTestId' => 'sales-invoice-aging-saved-view-open-link'", $reportContents);
        $this->assertStringContainsString("'activeBadgeTestId' => 'sales-invoice-aging-saved-view-active-badge'", $reportContents);
        $this->assertStringContainsString("'defaultBadgeTestId' => 'sales-invoice-aging-saved-view-default-badge'", $reportContents);
        $this->assertStringContainsString("'manageLinkTestId' => 'sales-invoice-aging-manage-saved-views-link'", $reportContents);

        $this->assertStringNotContainsString('<div class="card" data-testid="sales-invoice-aging-saved-views-selector">', $reportContents);
        $this->assertStringNotContainsString("@include('reports.partials.saved-view-section', [", $reportContents);

        $this->assertStringContainsString('data-testid="{{ $cardTestId', $sectionCardContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-section'", $sectionCardContents);
        $this->assertStringContainsString("'savedViews' => \$savedViews ?? collect()", $sectionCardContents);
        $this->assertStringContainsString("'routeName' => \$routeName", $sectionCardContents);
        $this->assertStringContainsString("'manageLinkTestId' => \$manageLinkTestId", $sectionCardContents);
    }
}
