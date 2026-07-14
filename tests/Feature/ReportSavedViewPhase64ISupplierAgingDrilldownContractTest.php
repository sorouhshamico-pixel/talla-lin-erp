<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewPhase64ISupplierAgingDrilldownContractTest extends TestCase
{
    public function test_phase_64i_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-64i-supplier-aging-drilldown-saved-view-controls-contract.json'));
        $this->assertFileExists(base_path('docs/phase-64i-supplier-aging-drilldown-saved-view-controls-contract.md'));
    }

    public function test_phase_64i_contract_matches_phase_64h_locked_target(): void
    {
        $lock = json_decode(
            file_get_contents(base_path('docs/phase-64h-next-saved-view-rollout-target.json')),
            true
        );

        $contract = json_decode(
            file_get_contents(base_path('docs/phase-64i-supplier-aging-drilldown-saved-view-controls-contract.json')),
            true
        );

        $this->assertSame('Phase 64I', $contract['phase']);
        $this->assertSame('supplier-purchase-invoice-aging-drilldown', $contract['target']['key']);
        $this->assertSame($lock['selected_target']['key'], $contract['target']['key']);
        $this->assertSame($lock['selected_target']['view_path'], $contract['target']['view_path']);
        $this->assertSame($lock['selected_target']['priority_score'], $contract['target']['priority_score']);
        $this->assertFalse($contract['target']['registered_at_lock_time']);
    }

    public function test_phase_64i_contract_target_view_exists_and_current_inline_shape_is_known(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-64i-supplier-aging-drilldown-saved-view-controls-contract.json')),
            true
        );

        $targetViewPath = str_replace('\\', '/', $contract['target']['view_path']);

        $this->assertFileExists(base_path($targetViewPath));

        $contents = file_get_contents(base_path($targetViewPath));

        $this->assertStringContainsString("@include('reports.partials.saved-view-section'", $contents);
        $this->assertStringContainsString("route('reports.supplier-purchase-invoice-aging.drilldown.saved-views.store')", $contents);
        $this->assertStringNotContainsString("@include('reports.partials.supplier-purchase-invoice-aging-drilldown-saved-view-controls-config')", $contents);
    }

    public function test_phase_64i_contract_uses_saved_view_controls_conventions(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-64i-supplier-aging-drilldown-saved-view-controls-contract.json')),
            true
        );

        $registryKey = $contract['contract']['registry_key'];

        $this->assertSame('supplier-purchase-invoice-aging-drilldown', $registryKey);

        $this->assertSame(
            'resources/views/reports/partials/' . $registryKey . '-saved-view-controls-config.blade.php',
            str_replace('\\', '/', $contract['contract']['config_partial_path'])
        );

        $this->assertSame(
            'reports.partials.' . $registryKey . '-saved-view-controls-config',
            $contract['contract']['config_partial']
        );

        $this->assertSame('reports.partials.saved-view-controls', $contract['contract']['shared_controls_partial']);
        $this->assertStringContainsString($contract['contract']['config_partial'], $contract['contract']['view_include']);
    }

    public function test_phase_64i_contract_documents_routes_hidden_fields_and_test_ids(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-64i-supplier-aging-drilldown-saved-view-controls-contract.json')),
            true
        );

        foreach ([
            'reports.supplier-purchase-invoice-aging.drilldown',
            'reports.supplier-purchase-invoice-aging.drilldown.export',
            'reports.supplier-purchase-invoice-aging.drilldown.saved-views.store',
        ] as $routeName) {
            $this->assertTrue(Route::has($routeName), $routeName);
        }

        $this->assertSame([
            'supplier_id',
            'branch_id',
            'as_of_date',
            'aging_bucket',
        ], $contract['contract']['candidate_hidden_fields']);

        foreach ([
            'section_card',
            'empty',
            'form_card',
            'form',
            'name_input',
            'default_checkbox',
            'save_button',
            'list',
            'item',
            'open_link',
            'active_badge',
            'default_badge',
        ] as $testIdKey) {
            $this->assertArrayHasKey($testIdKey, $contract['contract']['test_ids']);
            $this->assertNotEmpty($contract['contract']['test_ids'][$testIdKey]);
        }

        $this->assertTrue($contract['evidence']['contains_get_form']);
        $this->assertTrue($contract['evidence']['contains_filter_terms']);
        $this->assertTrue($contract['evidence']['contains_direct_saved_view_section_include']);
        $this->assertTrue($contract['evidence']['contains_inline_saved_view_form']);
        $this->assertFalse($contract['evidence']['contains_report_specific_config_partial']);
    }

    public function test_phase_64i_contract_is_documented(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-64i-supplier-aging-drilldown-saved-view-controls-contract.json')),
            true
        );

        $doc = file_get_contents(base_path('docs/phase-64i-supplier-aging-drilldown-saved-view-controls-contract.md'));

        $this->assertStringContainsString('Phase 64I', $doc);
        $this->assertStringContainsString('supplier-purchase-invoice-aging-drilldown', $doc);
        $this->assertStringContainsString($contract['target']['view_path'], $doc);
        $this->assertStringContainsString($contract['contract']['config_partial_path'], $doc);
        $this->assertStringContainsString('ReportSavedViewPhase64ISupplierAgingDrilldownContractTest', $doc);
    }
}
