<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewControlsRefactorDocumentationTest extends TestCase
{
    public function test_report_saved_view_controls_refactor_is_documented(): void
    {
        $docPath = base_path('docs/report-saved-view-controls-refactor.md');

        $this->assertFileExists($docPath);

        $contents = file_get_contents($docPath);

        $this->assertStringContainsString('Report Saved View Controls Refactor', $contents);
        $this->assertStringContainsString('sales-invoice-aging-saved-view-controls-config.blade.php', $contents);
        $this->assertStringContainsString('saved-view-controls.blade.php', $contents);
        $this->assertStringContainsString('saved-view-section-card.blade.php', $contents);
        $this->assertStringContainsString('saved-view-section.blade.php', $contents);
        $this->assertStringContainsString('saved-view-form-card.blade.php', $contents);
        $this->assertStringContainsString('saved-view-hidden-fields.blade.php', $contents);
        $this->assertStringContainsString('saved-view-form-fields.blade.php', $contents);

        $this->assertStringContainsString('customer_id', $contents);
        $this->assertStringContainsString('payment_status', $contents);
        $this->assertStringContainsString('aging_bucket', $contents);

        $this->assertStringContainsString('Partial inventory', $contents);
        $this->assertStringContainsString('The final saved view controls chain is:', $contents);
        $this->assertStringContainsString('ReportSavedViewControlsPartialInventoryTest', $contents);

        $this->assertStringContainsString('Do not define a config variable in a child partial and then use it in the parent view.', $contents);
        $this->assertStringContainsString('The config partial must render saved-view-controls inside the same partial where the config array is defined.', $contents);
    }
}
