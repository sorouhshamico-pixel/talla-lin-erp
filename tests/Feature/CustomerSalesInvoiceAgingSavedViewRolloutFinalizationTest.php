<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewCandidateScanner;
use App\Support\Reports\ReportSavedViewRegistry;
use App\Support\Reports\ReportSavedViewRegistryDiagnosticReport;
use App\Support\Reports\ReportSavedViewRegistryValidator;
use App\Support\Reports\ReportSavedViewRolloutTarget;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CustomerSalesInvoiceAgingSavedViewRolloutFinalizationTest extends TestCase
{
    public function test_phase_63_final_target_is_registered_and_asset_backed(): void
    {
        $report = ReportSavedViewRegistry::find('customer-sales-invoice-aging');

        $this->assertIsArray($report);
        $this->assertSame('customer-sales-invoice-aging', $report['key']);
        $this->assertSame('resources/views/reports/customer-sales-invoice-aging.blade.php', $report['view_path']);
        $this->assertSame(
            'resources/views/reports/partials/customer-sales-invoice-aging-saved-view-controls-config.blade.php',
            $report['config_partial_path']
        );

        $this->assertFileExists(base_path($report['view_path']));
        $this->assertFileExists(base_path($report['config_partial_path']));
        $this->assertTrue(Route::has($report['index_route']));
        $this->assertTrue(Route::has($report['saved_view_store_route']));
    }

    public function test_customer_sales_invoice_aging_view_and_config_partial_contract_is_finalized(): void
    {
        $view = file_get_contents(base_path('resources/views/reports/customer-sales-invoice-aging.blade.php'));
        $partial = file_get_contents(base_path('resources/views/reports/partials/customer-sales-invoice-aging-saved-view-controls-config.blade.php'));

        $this->assertStringContainsString(
            "@include('reports.partials.customer-sales-invoice-aging-saved-view-controls-config')",
            $view
        );

        $this->assertStringContainsString('$customerSalesInvoiceAgingSavedViewControlsConfig', $partial);
        $this->assertStringContainsString("reports.partials.saved-view-controls", $partial);
        $this->assertStringContainsString("'customer_id' => " . '$' . "customerFilter", $partial);
        $this->assertStringContainsString("'aging_bucket' => " . '$' . "agingBucketFilter", $partial);
    }

    public function test_registry_diagnostics_and_candidate_scanner_are_healthy_after_rollout(): void
    {
        $this->assertSame([], ReportSavedViewRegistryValidator::errorsFor('customer-sales-invoice-aging'));
        $this->assertTrue(ReportSavedViewRegistryValidator::summary()['valid']);

        $validReportKeys = ReportSavedViewRegistryDiagnosticReport::validReportKeys();

        $this->assertContains('sales-invoice-aging', $validReportKeys);
        $this->assertContains('customer-sales-invoice-aging', $validReportKeys);

        $candidate = collect(ReportSavedViewCandidateScanner::candidates())
            ->firstWhere('key', 'customer-sales-invoice-aging');

        $this->assertNotNull($candidate);
        $this->assertTrue($candidate['registered']);
    }

    public function test_rollout_target_support_surface_points_to_finalized_target(): void
    {
        $this->assertSame('customer-sales-invoice-aging', ReportSavedViewRolloutTarget::key());
        $this->assertSame(
            'resources/views/reports/customer-sales-invoice-aging.blade.php',
            ReportSavedViewRolloutTarget::viewPath()
        );
        $this->assertTrue(ReportSavedViewRolloutTarget::viewExists());
        $this->assertSame(
            'resources/views/reports/partials/customer-sales-invoice-aging-saved-view-controls-config.blade.php',
            ReportSavedViewRolloutTarget::recommendedConfigPartialPath()
        );
    }

    public function test_phase_63e_finalization_is_documented(): void
    {
        $finalDoc = base_path('docs/phase-63-customer-sales-invoice-aging-saved-view-rollout-finalization.md');
        $rolloutDoc = base_path('docs/phase-63-customer-sales-invoice-aging-saved-view-rollout.md');
        $targetDoc = base_path('docs/phase-63-report-saved-view-rollout-target.md');

        $this->assertFileExists($finalDoc);
        $this->assertFileExists($rolloutDoc);
        $this->assertFileExists($targetDoc);

        $finalContents = file_get_contents($finalDoc);
        $rolloutContents = file_get_contents($rolloutDoc);
        $targetContents = file_get_contents($targetDoc);

        $this->assertStringContainsString('Phase 63 is finalized.', $finalContents);
        $this->assertStringContainsString('CustomerSalesInvoiceAgingSavedViewRolloutFinalizationTest', $finalContents);
        $this->assertStringContainsString('customer-sales-invoice-aging', $finalContents);
        $this->assertStringContainsString('Phase 63E finalization', $rolloutContents);
        $this->assertStringContainsString('Phase 63E final acceptance', $targetContents);
    }
}
