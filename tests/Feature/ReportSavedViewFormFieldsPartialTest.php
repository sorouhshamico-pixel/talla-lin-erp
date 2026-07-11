<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewFormFieldsPartialTest extends TestCase
{
    public function test_saved_view_form_fields_partial_is_nested_inside_form_card_partial(): void
    {
        $reportView = resource_path('views/reports/sales-invoice-aging.blade.php');
        $formCardPartial = resource_path('views/reports/partials/saved-view-form-card.blade.php');
        $formFieldsPartial = resource_path('views/reports/partials/saved-view-form-fields.blade.php');

        $this->assertFileExists($reportView);
        $this->assertFileExists($formCardPartial);
        $this->assertFileExists($formFieldsPartial);

        $reportContents = file_get_contents($reportView);
        $formCardContents = file_get_contents($formCardPartial);
        $formFieldsContents = file_get_contents($formFieldsPartial);

        $this->assertStringContainsString("@include('reports.partials.saved-view-form-card'", $reportContents);
        $this->assertStringNotContainsString("@include('reports.partials.saved-view-form-fields'", $reportContents);

        $this->assertStringContainsString("@include('reports.partials.saved-view-form-fields'", $formCardContents);

        $this->assertStringContainsString('name="name"', $formFieldsContents);
        $this->assertStringContainsString('name="is_default"', $formFieldsContents);
        $this->assertStringContainsString('type="submit"', $formFieldsContents);

        $this->assertStringContainsString('$nameInputId', $formFieldsContents);
        $this->assertStringContainsString('$namePlaceholder', $formFieldsContents);
        $this->assertStringContainsString('$nameInputTestId', $formFieldsContents);
        $this->assertStringContainsString('$defaultCheckboxTestId', $formFieldsContents);
        $this->assertStringContainsString('$saveButtonTestId', $formFieldsContents);
    }
}
