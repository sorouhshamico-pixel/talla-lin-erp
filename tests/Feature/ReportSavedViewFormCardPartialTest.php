<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewFormCardPartialTest extends TestCase
{
    public function test_saved_view_controls_partial_uses_saved_view_form_card_partial_with_grouped_form_config(): void
    {
        $reportView = resource_path('views/reports/sales-invoice-aging.blade.php');
        $controlsPartial = resource_path('views/reports/partials/saved-view-controls.blade.php');
        $formCardPartial = resource_path('views/reports/partials/saved-view-form-card.blade.php');
        $formFieldsPartial = resource_path('views/reports/partials/saved-view-form-fields.blade.php');
        $hiddenFieldsPartial = resource_path('views/reports/partials/saved-view-hidden-fields.blade.php');

        $this->assertFileExists($controlsPartial);
        $this->assertFileExists($formCardPartial);
        $this->assertFileExists($formFieldsPartial);
        $this->assertFileExists($hiddenFieldsPartial);

        $reportContents = file_get_contents($reportView);
        $controlsContents = file_get_contents($controlsPartial);
        $formCardContents = file_get_contents($formCardPartial);

        $this->assertStringContainsString("@include('reports.partials.saved-view-controls'", $reportContents);
        $this->assertStringNotContainsString("@include('reports.partials.saved-view-form-card'", $reportContents);

        $this->assertStringContainsString('$formConfig = array_replace([', $controlsContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-form-card'", $controlsContents);
        $this->assertStringContainsString('$formConfig[\'cardTestId\']', $controlsContents);
        $this->assertStringContainsString('$formConfig[\'storeRouteName\']', $controlsContents);
        $this->assertStringContainsString('$formConfig[\'testId\']', $controlsContents);
        $this->assertStringContainsString('$formConfig[\'saveButtonTestId\']', $controlsContents);
        $this->assertStringContainsString("'hiddenFields' => \$hiddenFields ?? []", $controlsContents);

        $this->assertStringContainsString('data-testid="{{ $cardTestId', $formCardContents);
        $this->assertStringContainsString('action="{{ route($storeRouteName) }}"', $formCardContents);
        $this->assertStringContainsString('data-testid="{{ $formTestId', $formCardContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-hidden-fields'", $formCardContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-form-fields'", $formCardContents);
    }
}
