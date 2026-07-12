<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewControlsFinalizationTest extends TestCase
{
    public function test_phase_53_saved_view_controls_refactor_has_final_documentation_and_guards(): void
    {
        $summaryDoc = base_path('docs/phase-53-report-saved-view-controls-refactor.md');
        $refactorDoc = base_path('docs/report-saved-view-controls-refactor.md');
        $reportView = resource_path('views/reports/sales-invoice-aging.blade.php');
        $configPartial = resource_path('views/reports/partials/sales-invoice-aging-saved-view-controls-config.blade.php');
        $controlsPartial = resource_path('views/reports/partials/saved-view-controls.blade.php');

        $this->assertFileExists($summaryDoc);
        $this->assertFileExists($refactorDoc);
        $this->assertFileExists($reportView);
        $this->assertFileExists($configPartial);
        $this->assertFileExists($controlsPartial);

        $summaryContents = file_get_contents($summaryDoc);
        $refactorContents = file_get_contents($refactorDoc);
        $reportContents = file_get_contents($reportView);
        $configContents = file_get_contents($configPartial);
        $controlsContents = file_get_contents($controlsPartial);

        $this->assertStringContainsString('Phase 53 is finalized.', $summaryContents);
        $this->assertStringContainsString('saved-view-controls.blade.php', $summaryContents);
        $this->assertStringContainsString('sales-invoice-aging-saved-view-controls-config.blade.php', $summaryContents);
        $this->assertStringContainsString('Report views should not inline saved view controls markup.', $summaryContents);
        $this->assertStringContainsString('customer_id', $summaryContents);
        $this->assertStringContainsString('payment_status', $summaryContents);
        $this->assertStringContainsString('aging_bucket', $summaryContents);
        $this->assertStringContainsString('ReportSavedViewControlsFinalizationTest', $summaryContents);

        $this->assertStringContainsString('Phase 53 finalization', $refactorContents);
        $this->assertStringContainsString('docs/phase-53-report-saved-view-controls-refactor.md', $refactorContents);

        $this->assertStringContainsString("@include('reports.partials.sales-invoice-aging-saved-view-controls-config')", $reportContents);
        $this->assertStringNotContainsString('$salesInvoiceAgingSavedViewControlsConfig = [', $reportContents);
        $this->assertStringNotContainsString("@include('reports.partials.saved-view-controls'", $reportContents);

        $this->assertStringContainsString('$salesInvoiceAgingSavedViewControlsConfig = [', $configContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-controls', \$salesInvoiceAgingSavedViewControlsConfig)", $configContents);

        $this->assertStringContainsString('$sectionConfig = array_replace([', $controlsContents);
        $this->assertStringContainsString('$formConfig = array_replace([', $controlsContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-section-card'", $controlsContents);
        $this->assertStringContainsString("@include('reports.partials.saved-view-form-card'", $controlsContents);
    }
}
