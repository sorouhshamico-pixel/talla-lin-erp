<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewFormCardPartialTest extends TestCase
{
    public function test_saved_view_config_partial_uses_saved_view_controls_and_form_card_partial(): void
    {
        $reportView = resource_path('views/reports/sales-invoice-aging.blade.php');
        $configPartial = resource_path('views/reports/partials/sales-invoice-aging-saved-view-controls-config.blade.php');
        $controlsPartial = resource_path('views/reports/partials/saved-view-controls.blade.php');
        $formCardPartial = resource_path('views/reports/partials/saved-view-form-card.blade.php');
        $formFieldsPartial = resource_path('views/reports/partials/saved-view-form-fields.blade.php');
        $hiddenFieldsPartial = resource_path('views/reports/partials/saved-view-hidden-fields.blade.php');

        $this->assertFileExists($configPartial);
        $this->assertFileExists($controlsPartial);
        $this->assertFileExists($formCardPartial);
        $this->assertFileExists($formFieldsPartial);
        $this->assertFileExists($hiddenFieldsPartial);

        $reportContents = file_get_contents($reportView);
        $configContents = file_get_contents($configPartial);
        $controlsContents = file_get_contents($controlsPartial);
        $formCardContents = file_get_contents($formCardPartial);

        $this->assertStringContainsString("@include('reports.partials.sales-invoice-aging-saved-view-controls-config')", $reportContents);
        $this->assertStringNotContainsString("@include('reports.partials.saved-view-form-card'", $reportContents);

        $this->assertStringContainsString("@include('reports.partials.saved-view-controls'", $configContents);

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
