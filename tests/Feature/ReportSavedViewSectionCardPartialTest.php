<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewSectionCardPartialTest extends TestCase
{
    public function test_saved_view_controls_partial_uses_saved_view_section_card_partial_with_grouped_section_config(): void
    {
        $reportView = resource_path('views/reports/sales-invoice-aging.blade.php');
        $controlsPartial = resource_path('views/reports/partials/saved-view-controls.blade.php');
        $sectionCardPartial = resource_path('views/reports/partials/saved-view-section-card.blade.php');
        $sectionPartial = resource_path('views/reports/partials/saved-view-section.blade.php');

        $this->assertFileExists($reportView);
        $this->assertFileExists($controlsPartial);
        $this->assertFileExists($sectionCardPartial);
        $this->assertFileExists($sectionPartial);

        $reportContents = file_get_contents($reportView);
        $controlsContents = file_get_contents($controlsPartial);
        $sectionCardContents = file_get_contents($sectionCardPartial);

        $this->assertStringContainsString("@include('reports.partials.saved-view-controls'", $reportContents);
        $this->assertStringNotContainsString("@include('reports.partials.saved-view-section-card'", $reportContents);

        $this->assertStringContainsString('$sectionConfig = $section ?? [];', $controlsContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-section-card'", $controlsContents);
        $this->assertStringContainsString('$sectionConfig[\'cardTestId\'] ?? \'saved-view-section-card\'', $controlsContents);
        $this->assertStringContainsString('$sectionConfig[\'routeName\']', $controlsContents);
        $this->assertStringContainsString('$sectionConfig[\'manageLinkTestId\']', $controlsContents);

        $this->assertStringContainsString('data-testid="{{ $cardTestId', $sectionCardContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-section'", $sectionCardContents);
        $this->assertStringContainsString("'savedViews' => \$savedViews ?? collect()", $sectionCardContents);
        $this->assertStringContainsString("'routeName' => \$routeName", $sectionCardContents);
        $this->assertStringContainsString("'manageLinkTestId' => \$manageLinkTestId", $sectionCardContents);
    }
}
