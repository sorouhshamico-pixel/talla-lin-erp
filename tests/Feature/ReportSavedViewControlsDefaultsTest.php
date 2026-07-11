<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewControlsDefaultsTest extends TestCase
{
    public function test_saved_view_controls_partial_defines_section_and_form_defaults(): void
    {
        $controlsPartial = resource_path('views/reports/partials/saved-view-controls.blade.php');

        $this->assertFileExists($controlsPartial);

        $contents = file_get_contents($controlsPartial);

        $this->assertStringContainsString('$sectionConfig = array_replace([', $contents);
        $this->assertStringContainsString("'cardTestId' => 'saved-view-section-card'", $contents);
        $this->assertStringContainsString("'emptyTestId' => 'saved-view-empty'", $contents);
        $this->assertStringContainsString("'listTestId' => 'saved-view-list'", $contents);
        $this->assertStringContainsString("'itemTestId' => 'saved-view-item'", $contents);
        $this->assertStringContainsString("'openLinkTestId' => 'saved-view-open-link'", $contents);
        $this->assertStringContainsString("'activeBadgeTestId' => 'saved-view-active-badge'", $contents);
        $this->assertStringContainsString("'defaultBadgeTestId' => 'saved-view-default-badge'", $contents);
        $this->assertStringContainsString("'manageLinkTestId' => 'saved-view-manage-link'", $contents);
        $this->assertStringContainsString("'emptyMessage' => null", $contents);

        $this->assertStringContainsString('$formConfig = array_replace([', $contents);
        $this->assertStringContainsString("'cardTestId' => 'saved-view-form-card'", $contents);
        $this->assertStringContainsString("'title' => 'حفظ عرض التقرير'", $contents);
        $this->assertStringContainsString("'testId' => 'saved-view-form'", $contents);
        $this->assertStringContainsString("'nameInputId' => null", $contents);
        $this->assertStringContainsString("'namePlaceholder' => null", $contents);
        $this->assertStringContainsString("'nameInputTestId' => null", $contents);
        $this->assertStringContainsString("'defaultCheckboxTestId' => null", $contents);
        $this->assertStringContainsString("'saveButtonTestId' => null", $contents);

        $this->assertStringContainsString('], $section ?? []);', $contents);
        $this->assertStringContainsString('], $form ?? []);', $contents);
    }
}
