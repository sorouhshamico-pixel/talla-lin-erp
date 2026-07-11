<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewSectionCardPartialTest extends TestCase
{
    public function test_saved_view_config_partial_uses_saved_view_controls_and_section_card_partial(): void
    {
        $reportView = resource_path('views/reports/sales-invoice-aging.blade.php');
        $configPartial = resource_path('views/reports/partials/sales-invoice-aging-saved-view-controls-config.blade.php');
        $controlsPartial = resource_path('views/reports/partials/saved-view-controls.blade.php');
        $sectionCardPartial = resource_path('views/reports/partials/saved-view-section-card.blade.php');
        $sectionPartial = resource_path('views/reports/partials/saved-view-section.blade.php');

        $this->assertFileExists($reportView);
        $this->assertFileExists($configPartial);
        $this->assertFileExists($controlsPartial);
        $this->assertFileExists($sectionCardPartial);
        $this->assertFileExists($sectionPartial);

        $reportContents = file_get_contents($reportView);
        $configContents = file_get_contents($configPartial);
        $controlsContents = file_get_contents($controlsPartial);
        $sectionCardContents = file_get_contents($sectionCardPartial);

        $this->assertStringContainsString("@include('reports.partials.sales-invoice-aging-saved-view-controls-config')", $reportContents);
        $this->assertStringNotContainsString("@include('reports.partials.saved-view-section-card'", $reportContents);

        $this->assertStringContainsString("@include('reports.partials.saved-view-controls'", $configContents);

        $this->assertStringContainsString('$sectionConfig = array_replace([', $controlsContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-section-card'", $controlsContents);
        $this->assertStringContainsString('$sectionConfig[\'cardTestId\']', $controlsContents);
        $this->assertStringContainsString('$sectionConfig[\'routeName\']', $controlsContents);
        $this->assertStringContainsString('$sectionConfig[\'manageLinkTestId\']', $controlsContents);

        $this->assertStringContainsString('data-testid="{{ $cardTestId', $sectionCardContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-section'", $sectionCardContents);
        $this->assertStringContainsString("'savedViews' => \$savedViews ?? collect()", $sectionCardContents);
        $this->assertStringContainsString("'routeName' => \$routeName", $sectionCardContents);
        $this->assertStringContainsString("'manageLinkTestId' => \$manageLinkTestId", $sectionCardContents);
    }
}
