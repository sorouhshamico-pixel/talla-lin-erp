<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewSectionPartialTest extends TestCase
{
    public function test_saved_view_section_partial_contains_shared_saved_view_content_without_card_wrapper(): void
    {
        $reportView = resource_path('views/reports/sales-invoice-aging.blade.php');
        $configPartial = resource_path('views/reports/partials/sales-invoice-aging-saved-view-controls-config.blade.php');
        $controlsPartial = resource_path('views/reports/partials/saved-view-controls.blade.php');
        $sectionCardPartial = resource_path('views/reports/partials/saved-view-section-card.blade.php');
        $sectionPartial = resource_path('views/reports/partials/saved-view-section.blade.php');

        $this->assertFileExists($configPartial);
        $this->assertFileExists($controlsPartial);
        $this->assertFileExists($sectionCardPartial);
        $this->assertFileExists($sectionPartial);

        $reportContents = file_get_contents($reportView);
        $configContents = file_get_contents($configPartial);
        $controlsContents = file_get_contents($controlsPartial);
        $sectionCardContents = file_get_contents($sectionCardPartial);
        $sectionContents = file_get_contents($sectionPartial);

        $this->assertStringContainsString("@include('reports.partials.sales-invoice-aging-saved-view-controls-config')", $reportContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-controls'", $configContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-section-card'", $controlsContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-section'", $sectionCardContents);

        $this->assertStringContainsString('<h2>العروض المحفوظة</h2>', $sectionContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-list-styles')", $sectionContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-help-text')", $sectionContents);
        $this->assertStringContainsString("@include('reports.partials.active-saved-view-banner'", $sectionContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-list'", $sectionContents);
        $this->assertStringContainsString('data-testid="{{ $manageLinkTestId }}"', $sectionContents);

        $this->assertStringNotContainsString('class="card"', $sectionContents);
    }
}
