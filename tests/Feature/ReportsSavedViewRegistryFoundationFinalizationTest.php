<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewRegistry;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportsSavedViewRegistryFoundationFinalizationTest extends TestCase
{
    public function test_phase_55_registry_foundation_finalization_is_documented(): void
    {
        $finalDoc = base_path('docs/phase-55-report-saved-view-registry-foundation-finalization.md');
        $auditDoc = base_path('docs/phase-55-reports-saved-view-foundation-audit.md');
        $registryDoc = base_path('docs/phase-55-report-saved-view-registry.md');
        $extensionGuide = base_path('docs/report-saved-view-controls-extension-guide.md');

        foreach ([$finalDoc, $auditDoc, $registryDoc, $extensionGuide] as $document) {
            $this->assertFileExists($document);
        }

        $finalContents = file_get_contents($finalDoc);
        $auditContents = file_get_contents($auditDoc);
        $registryContents = file_get_contents($registryDoc);
        $guideContents = file_get_contents($extensionGuide);

        $this->assertStringContainsString('Phase 55 is finalized.', $finalContents);
        $this->assertStringContainsString('ReportSavedViewRegistry.php', $finalContents);
        $this->assertStringContainsString('sales-invoice-aging', $finalContents);
        $this->assertStringContainsString('customer_id', $finalContents);
        $this->assertStringContainsString('payment_status', $finalContents);
        $this->assertStringContainsString('aging_bucket', $finalContents);
        $this->assertStringContainsString('ReportsSavedViewRegistryFoundationFinalizationTest', $finalContents);

        $this->assertStringContainsString('Registry integration', $auditContents);
        $this->assertStringContainsString('phase-55-report-saved-view-registry-foundation-finalization.md', $auditContents);

        $this->assertStringContainsString('Phase 55C finalization', $registryContents);
        $this->assertStringContainsString('phase-55-report-saved-view-registry-foundation-finalization.md', $registryContents);

        $this->assertStringContainsString('Registry requirement', $guideContents);
        $this->assertStringContainsString('ReportSavedViewRegistry.php', $guideContents);
    }

    public function test_registry_entries_have_required_shape_and_existing_assets(): void
    {
        $reports = ReportSavedViewRegistry::reports();

        $this->assertNotEmpty($reports);

        $requiredKeys = [
            'key',
            'label',
            'view',
            'view_path',
            'index_route',
            'export_route',
            'saved_view_store_route',
            'config_partial',
            'config_partial_path',
            'hidden_fields',
            'test_ids',
        ];

        foreach ($reports as $key => $report) {
            foreach ($requiredKeys as $requiredKey) {
                $this->assertArrayHasKey($requiredKey, $report, "{$key} is missing {$requiredKey}.");
            }

            $this->assertSame($key, $report['key']);
            $this->assertFileExists(base_path($report['view_path']));
            $this->assertFileExists(base_path($report['config_partial_path']));
            $this->assertTrue(Route::has($report['index_route']));
            $this->assertTrue(Route::has($report['export_route']));
            $this->assertTrue(Route::has($report['saved_view_store_route']));

            $this->assertIsArray($report['hidden_fields']);

            if (in_array(($report['key'] ?? null), ['saved-view-candidates', 'sales-invoice-collections'], true)) {
                $this->assertSame([], $report['hidden_fields']);
            } else {
                $this->assertNotEmpty($report['hidden_fields']);
            }

            $this->assertIsArray($report['test_ids']);
            $this->assertNotEmpty($report['test_ids']);
        }
    }

    public function test_registry_hidden_fields_match_config_partials(): void
    {
        foreach (ReportSavedViewRegistry::reports() as $report) {
            $configContents = file_get_contents(base_path($report['config_partial_path']));

            foreach ($report['hidden_fields'] as $hiddenField) {
                $this->assertStringContainsString(
                    "'{$hiddenField}'",
                    $configContents,
                    "{$report['key']} config partial should contain hidden field {$hiddenField}."
                );
            }
        }
    }

    public function test_registry_test_ids_match_config_partials(): void
    {
        foreach (ReportSavedViewRegistry::reports() as $report) {
            $configContents = file_get_contents(base_path($report['config_partial_path']));

            foreach ($report['test_ids'] as $testId) {
                $this->assertStringContainsString(
                    $testId,
                    $configContents,
                    "{$report['key']} config partial should contain test id {$testId}."
                );
            }
        }
    }

    public function test_report_pages_remain_free_of_inline_saved_view_controls(): void
    {
        foreach (glob(resource_path('views/reports/*.blade.php')) as $reportView) {
            $contents = file_get_contents($reportView);

            $this->assertStringNotContainsString("@include('reports.partials.saved-view-controls'", $contents);
            $this->assertStringNotContainsString('SavedViewControlsConfig = [', $contents);
        }
    }
}
