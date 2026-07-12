<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use Tests\TestCase;

class ReportSavedViewControlsConfigRolloutTest extends TestCase
{
    public function test_report_views_do_not_directly_render_saved_view_controls(): void
    {
        $reportViews = collect(glob(resource_path('views/reports/*.blade.php')));

        $this->assertNotEmpty($reportViews);

        foreach ($reportViews as $reportView) {
            $contents = file_get_contents($reportView);

            $this->assertStringNotContainsString(
                "@include('reports.partials.saved-view-controls'",
                $contents,
                "{$reportView} should load a report-specific saved view controls config partial instead of rendering saved-view-controls directly."
            );

            $this->assertStringNotContainsString(
                'SavedViewControlsConfig = [',
                $contents,
                "{$reportView} should not inline saved view controls config arrays."
            );
        }
    }

    public function test_report_specific_saved_view_controls_config_partials_render_shared_controls(): void
    {
        $configPartials = collect(glob(resource_path('views/reports/partials/*-saved-view-controls-config.blade.php')));

        $this->assertNotEmpty($configPartials);

        foreach ($configPartials as $configPartial) {
            $contents = file_get_contents($configPartial);

            $this->assertStringContainsString(
                'SavedViewControlsConfig = [',
                $contents,
                "{$configPartial} should define a saved view controls config array."
            );

            $this->assertStringContainsString(
                "@include('reports.partials.saved-view-controls'",
                $contents,
                "{$configPartial} should render the shared saved-view-controls partial in the same scope as the config array."
            );

            $this->assertStringContainsString("'savedViews'", $contents);
            $this->assertStringContainsString("'section'", $contents);
            $this->assertStringContainsString("'form'", $contents);
            $this->assertStringContainsString("'hiddenFields'", $contents);
        }
    }

    public function test_report_views_using_saved_view_controls_load_existing_config_partials(): void
    {
        $reportViews = collect(glob(resource_path('views/reports/*.blade.php')));

        foreach ($reportViews as $reportView) {
            $contents = file_get_contents($reportView);

            preg_match_all(
                "/@include\\('reports\\.partials\\.([a-z0-9\\-]+-saved-view-controls-config)'\\)/",
                $contents,
                $matches
            );

            foreach ($matches[1] as $partialName) {
                $partialPath = resource_path("views/reports/partials/{$partialName}.blade.php");

                $this->assertFileExists($partialPath, "{$reportView} references a missing saved view controls config partial.");

                $this->assertTrue(
                    Str::endsWith($partialName, '-saved-view-controls-config'),
                    "{$partialName} should use the report saved view controls config naming convention."
                );
            }
        }
    }

    public function test_phase_54_rollout_is_documented(): void
    {
        $doc = base_path('docs/phase-54-report-saved-view-controls-rollout.md');

        $this->assertFileExists($doc);

        $contents = file_get_contents($doc);

        $this->assertStringContainsString('Phase 54A', $contents);
        $this->assertStringContainsString('Report Saved View Controls Config Rollout', $contents);
        $this->assertStringContainsString('Report views under resources/views/reports should not contain direct saved-view-controls includes.', $contents);
        $this->assertStringContainsString('ReportSavedViewControlsConfigRolloutTest', $contents);
    }
}
