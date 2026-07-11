<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewHiddenFieldsPartialTest extends TestCase
{
    public function test_saved_view_hidden_fields_are_extracted_to_shared_partial(): void
    {
        $formCardPartial = resource_path('views/reports/partials/saved-view-form-card.blade.php');
        $hiddenFieldsPartial = resource_path('views/reports/partials/saved-view-hidden-fields.blade.php');

        $this->assertFileExists($formCardPartial);
        $this->assertFileExists($hiddenFieldsPartial);

        $formCardContents = file_get_contents($formCardPartial);
        $hiddenFieldsContents = file_get_contents($hiddenFieldsPartial);

        $this->assertStringContainsString("@include('reports.partials.saved-view-hidden-fields'", $formCardContents);
        $this->assertStringContainsString("'hiddenFields' => \$hiddenFields ?? []", $formCardContents);

        $this->assertStringContainsString('@foreach (($hiddenFields ?? []) as $hiddenFieldName => $hiddenFieldValue)', $hiddenFieldsContents);
        $this->assertStringContainsString('type="hidden"', $hiddenFieldsContents);
        $this->assertStringContainsString('name="{{ $hiddenFieldName }}"', $hiddenFieldsContents);
        $this->assertStringContainsString('value="{{ $hiddenFieldValue }}"', $hiddenFieldsContents);
        $this->assertStringContainsString('@endforeach', $hiddenFieldsContents);
    }
}
