<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewSectionCardPartialTest extends TestCase
{
    public function test_saved_view_controls_partial_uses_saved_view_section_card_partial(): void
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

        $this->assertStringContainsString("@include('reports.partials.saved-view-section-card'", $controlsContents);
        $this->assertStringContainsString("'cardTestId' => \$sectionCardTestId ?? 'saved-view-section-card'", $controlsContents);
        $this->assertStringContainsString("'savedViews' => \$savedViewControlsCollection", $controlsContents);
        $this->assertStringContainsString("'routeName' => \$sectionRouteName", $controlsContents);
        $this->assertStringContainsString("'manageLinkTestId' => \$sectionManageLinkTestId", $controlsContents);

        $this->assertStringContainsString('data-testid="{{ $cardTestId', $sectionCardContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-section'", $sectionCardContents);
        $this->assertStringContainsString("'savedViews' => \$savedViews ?? collect()", $sectionCardContents);
        $this->assertStringContainsString("'routeName' => \$routeName", $sectionCardContents);
        $this->assertStringContainsString("'manageLinkTestId' => \$manageLinkTestId", $sectionCardContents);
    }
}
