<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewControlsPartialInventoryTest extends TestCase
{
    public function test_saved_view_controls_partial_inventory_is_complete_and_wired(): void
    {
        $partials = [
            'reportConfig' => resource_path('views/reports/partials/sales-invoice-aging-saved-view-controls-config.blade.php'),
            'controls' => resource_path('views/reports/partials/saved-view-controls.blade.php'),
            'sectionCard' => resource_path('views/reports/partials/saved-view-section-card.blade.php'),
            'section' => resource_path('views/reports/partials/saved-view-section.blade.php'),
            'formCard' => resource_path('views/reports/partials/saved-view-form-card.blade.php'),
            'hiddenFields' => resource_path('views/reports/partials/saved-view-hidden-fields.blade.php'),
            'formFields' => resource_path('views/reports/partials/saved-view-form-fields.blade.php'),
            'list' => resource_path('views/reports/partials/saved-view-list.blade.php'),
            'listStyles' => resource_path('views/reports/partials/saved-view-list-styles.blade.php'),
            'helpText' => resource_path('views/reports/partials/saved-view-help-text.blade.php'),
            'activeBanner' => resource_path('views/reports/partials/active-saved-view-banner.blade.php'),
        ];

        foreach ($partials as $partialPath) {
            $this->assertFileExists($partialPath);
        }

        $reportView = file_get_contents(resource_path('views/reports/sales-invoice-aging.blade.php'));
        $reportConfig = file_get_contents($partials['reportConfig']);
        $controls = file_get_contents($partials['controls']);
        $sectionCard = file_get_contents($partials['sectionCard']);
        $section = file_get_contents($partials['section']);
        $formCard = file_get_contents($partials['formCard']);
        $hiddenFields = file_get_contents($partials['hiddenFields']);
        $formFields = file_get_contents($partials['formFields']);

        $this->assertStringContainsString("@include('reports.partials.sales-invoice-aging-saved-view-controls-config')", $reportView);
        $this->assertStringNotContainsString("@include('reports.partials.saved-view-controls', \$salesInvoiceAgingSavedViewControlsConfig)", $reportView);

        $this->assertStringContainsString('$salesInvoiceAgingSavedViewControlsConfig = [', $reportConfig);
        $this->assertStringContainsString("@include('reports.partials.saved-view-controls', \$salesInvoiceAgingSavedViewControlsConfig)", $reportConfig);

        $this->assertStringContainsString("@include('reports.partials.saved-view-section-card'", $controls);
        $this->assertStringContainsString("@include('reports.partials.saved-view-form-card'", $controls);

        $this->assertStringContainsString("@include('reports.partials.saved-view-section'", $sectionCard);
        $this->assertStringContainsString("@include('reports.partials.saved-view-list-styles')", $section);
        $this->assertStringContainsString("@include('reports.partials.saved-view-help-text')", $section);
        $this->assertStringContainsString("@include('reports.partials.active-saved-view-banner'", $section);
        $this->assertStringContainsString("@include('reports.partials.saved-view-list'", $section);

        $this->assertStringContainsString("@include('reports.partials.saved-view-hidden-fields'", $formCard);
        $this->assertStringContainsString("@include('reports.partials.saved-view-form-fields'", $formCard);

        $this->assertStringContainsString('type="hidden"', $hiddenFields);
        $this->assertStringContainsString('name="{{ $hiddenFieldName }}"', $hiddenFields);
        $this->assertStringContainsString('value="{{ $hiddenFieldValue }}"', $hiddenFields);

        $this->assertStringContainsString('name="name"', $formFields);
        $this->assertStringContainsString('name="is_default"', $formFields);
        $this->assertStringContainsString('type="submit"', $formFields);
    }
}
