<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewControlsRolloutFinalizationTest extends TestCase
{
    public function test_phase_54_rollout_finalization_is_documented(): void
    {
        $finalDoc = base_path('docs/phase-54-report-saved-view-controls-finalization.md');
        $rolloutDoc = base_path('docs/phase-54-report-saved-view-controls-rollout.md');
        $extensionGuide = base_path('docs/report-saved-view-controls-extension-guide.md');

        $this->assertFileExists($finalDoc);
        $this->assertFileExists($rolloutDoc);
        $this->assertFileExists($extensionGuide);

        $finalContents = file_get_contents($finalDoc);
        $rolloutContents = file_get_contents($rolloutDoc);
        $extensionContents = file_get_contents($extensionGuide);

        $this->assertStringContainsString('Phase 54 is finalized.', $finalContents);
        $this->assertStringContainsString('Phase 54A', $finalContents);
        $this->assertStringContainsString('Phase 54B', $finalContents);
        $this->assertStringContainsString('Phase 54C', $finalContents);
        $this->assertStringContainsString('Report views under resources/views/reports must not inline saved view controls markup.', $finalContents);
        $this->assertStringContainsString('sales-invoice-aging-saved-view-controls-config.blade.php', $finalContents);
        $this->assertStringContainsString('ReportSavedViewControlsRolloutFinalizationTest', $finalContents);

        $this->assertStringContainsString('Phase 54 finalization', $rolloutContents);
        $this->assertStringContainsString('docs/phase-54-report-saved-view-controls-finalization.md', $rolloutContents);

        $this->assertStringContainsString('Phase 54 rollout finalization', $extensionContents);
        $this->assertStringContainsString('docs/phase-54-report-saved-view-controls-finalization.md', $extensionContents);
    }

    public function test_phase_54_rollout_contract_is_enforced_across_report_views_and_config_partials(): void
    {
        $reportViews = glob(resource_path('views/reports/*.blade.php'));
        $configPartials = glob(resource_path('views/reports/partials/*-saved-view-controls-config.blade.php'));

        $this->assertNotEmpty($reportViews);
        $this->assertNotEmpty($configPartials);

        foreach ($reportViews as $reportView) {
            $contents = file_get_contents($reportView);

            $this->assertStringNotContainsString(
                "@include('reports.partials.saved-view-controls'",
                $contents,
                "{$reportView} should not render saved-view-controls directly."
            );

            $this->assertStringNotContainsString(
                'SavedViewControlsConfig = [',
                $contents,
                "{$reportView} should not inline saved view controls config arrays."
            );
        }

        foreach ($configPartials as $configPartial) {
            $contents = file_get_contents($configPartial);

            $this->assertStringContainsString('SavedViewControlsConfig = [', $contents);
            $this->assertStringContainsString("@include('reports.partials.saved-view-controls'", $contents);

            foreach (["'savedViews'", "'section'", "'form'", "'hiddenFields'"] as $key) {
                $this->assertStringContainsString($key, $contents);
            }
        }
    }
}
