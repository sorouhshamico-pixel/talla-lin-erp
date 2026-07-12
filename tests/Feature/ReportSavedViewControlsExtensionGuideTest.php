<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewControlsExtensionGuideTest extends TestCase
{
    public function test_report_saved_view_controls_extension_guide_exists_and_documents_the_contract(): void
    {
        $guide = base_path('docs/report-saved-view-controls-extension-guide.md');
        $rolloutDoc = base_path('docs/phase-54-report-saved-view-controls-rollout.md');

        $this->assertFileExists($guide);
        $this->assertFileExists($rolloutDoc);

        $guideContents = file_get_contents($guide);
        $rolloutContents = file_get_contents($rolloutDoc);

        $this->assertStringContainsString('Report Saved View Controls Extension Guide', $guideContents);
        $this->assertStringContainsString('Naming convention', $guideContents);
        $this->assertStringContainsString('Config partial structure', $guideContents);
        $this->assertStringContainsString('Required config keys', $guideContents);
        $this->assertStringContainsString('Safety rules', $guideContents);

        $this->assertStringContainsString('savedViews', $guideContents);
        $this->assertStringContainsString('section', $guideContents);
        $this->assertStringContainsString('form', $guideContents);
        $this->assertStringContainsString('hiddenFields', $guideContents);

        $this->assertStringContainsString('customer_id', $guideContents);
        $this->assertStringContainsString('payment_status', $guideContents);
        $this->assertStringContainsString('aging_bucket', $guideContents);

        $this->assertStringContainsString('sales-invoice-aging-saved-view-controls-config.blade.php', $guideContents);
        $this->assertStringContainsString('ReportSavedViewControlsExtensionGuideTest', $guideContents);

        $this->assertStringContainsString('Extension guide', $rolloutContents);
        $this->assertStringContainsString('docs/report-saved-view-controls-extension-guide.md', $rolloutContents);
    }

    public function test_report_specific_saved_view_controls_config_partials_follow_the_extension_contract(): void
    {
        $configPartials = glob(resource_path('views/reports/partials/*-saved-view-controls-config.blade.php'));

        $this->assertNotEmpty($configPartials);

        foreach ($configPartials as $configPartial) {
            $contents = file_get_contents($configPartial);

            $this->assertStringContainsString(
                'SavedViewControlsConfig = [',
                $contents,
                "{$configPartial} must define a saved view controls config array."
            );

            $this->assertStringContainsString(
                "@include('reports.partials.saved-view-controls'",
                $contents,
                "{$configPartial} must render shared saved-view-controls inside the same partial scope."
            );

            foreach (["'savedViews'", "'section'", "'form'", "'hiddenFields'"] as $requiredKey) {
                $this->assertStringContainsString(
                    $requiredKey,
                    $contents,
                    "{$configPartial} is missing required config key {$requiredKey}."
                );
            }
        }
    }

    public function test_report_pages_keep_saved_view_controls_out_of_inline_markup(): void
    {
        $reportViews = glob(resource_path('views/reports/*.blade.php'));

        $this->assertNotEmpty($reportViews);

        foreach ($reportViews as $reportView) {
            $contents = file_get_contents($reportView);

            $this->assertStringNotContainsString(
                "@include('reports.partials.saved-view-controls'",
                $contents,
                "{$reportView} should not render shared saved-view-controls directly."
            );

            $this->assertStringNotContainsString(
                'SavedViewControlsConfig = [',
                $contents,
                "{$reportView} should not inline a saved view controls config array."
            );
        }
    }
}
