<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportsSavedViewFoundationAuditTest extends TestCase
{
    public function test_reports_saved_view_foundation_audit_document_exists(): void
    {
        $auditDoc = base_path('docs/phase-55-reports-saved-view-foundation-audit.md');

        $this->assertFileExists($auditDoc);

        $contents = file_get_contents($auditDoc);

        $this->assertStringContainsString('Phase 55A', $contents);
        $this->assertStringContainsString('Reports Saved View Foundation Final Audit', $contents);
        $this->assertStringContainsString('Report pages under resources/views/reports must stay focused on report layout.', $contents);
        $this->assertStringContainsString('They must not inline saved view controls markup.', $contents);
        $this->assertStringContainsString('They must not define saved view controls config arrays directly.', $contents);
        $this->assertStringContainsString('ReportsSavedViewFoundationAuditTest', $contents);
    }

    public function test_all_saved_view_foundation_documents_exist(): void
    {
        $documents = [
            'docs/report-saved-view-controls-refactor.md',
            'docs/phase-53-report-saved-view-controls-refactor.md',
            'docs/phase-54-report-saved-view-controls-rollout.md',
            'docs/report-saved-view-controls-extension-guide.md',
            'docs/phase-54-report-saved-view-controls-finalization.md',
            'docs/phase-55-reports-saved-view-foundation-audit.md',
        ];

        foreach ($documents as $document) {
            $this->assertFileExists(base_path($document), "{$document} should exist.");
        }
    }

    public function test_report_views_do_not_inline_saved_view_controls_or_config_arrays(): void
    {
        $reportViews = glob(resource_path('views/reports/*.blade.php'));

        $this->assertNotEmpty($reportViews);

        foreach ($reportViews as $reportView) {
            $contents = file_get_contents($reportView);

            $this->assertStringNotContainsString(
                "@include('reports.partials.saved-view-controls'",
                $contents,
                "{$reportView} should not directly render saved-view-controls."
            );

            $this->assertStringNotContainsString(
                'SavedViewControlsConfig = [',
                $contents,
                "{$reportView} should not inline saved view controls config arrays."
            );
        }
    }

    public function test_report_specific_saved_view_controls_config_partials_follow_contract(): void
    {
        $configPartials = glob(resource_path('views/reports/partials/*-saved-view-controls-config.blade.php'));

        $this->assertNotEmpty($configPartials);

        foreach ($configPartials as $configPartial) {
            $contents = file_get_contents($configPartial);

            $this->assertStringContainsString(
                'SavedViewControlsConfig = [',
                $contents,
                "{$configPartial} should define a SavedViewControlsConfig array."
            );

            $this->assertStringContainsString(
                "@include('reports.partials.saved-view-controls'",
                $contents,
                "{$configPartial} should render saved-view-controls in the same scope."
            );

            foreach (["'savedViews'", "'section'", "'form'", "'hiddenFields'"] as $requiredKey) {
                $this->assertStringContainsString(
                    $requiredKey,
                    $contents,
                    "{$configPartial} is missing required key {$requiredKey}."
                );
            }
        }
    }

    public function test_sales_invoice_aging_remains_the_reference_implementation(): void
    {
        $report = resource_path('views/reports/sales-invoice-aging.blade.php');
        $configPartial = resource_path('views/reports/partials/sales-invoice-aging-saved-view-controls-config.blade.php');

        $this->assertFileExists($report);
        $this->assertFileExists($configPartial);

        $reportContents = file_get_contents($report);
        $configContents = file_get_contents($configPartial);

        $this->assertStringContainsString(
            "@include('reports.partials.sales-invoice-aging-saved-view-controls-config')",
            $reportContents
        );

        $this->assertStringContainsString("'customer_id'", $configContents);
        $this->assertStringContainsString("'payment_status'", $configContents);
        $this->assertStringContainsString("'aging_bucket'", $configContents);
    }
}
