<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewPhase65FSalesInvoiceCollectionsContractTest extends TestCase
{
    public function test_phase_65f_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-65f-sales-invoice-collections-saved-view-controls-contract.json'));
        $this->assertFileExists(base_path('docs/phase-65f-sales-invoice-collections-saved-view-controls-contract.md'));
    }

    public function test_phase_65f_contract_matches_phase_65e_lock(): void
    {
        $lock = json_decode(
            file_get_contents(base_path('docs/phase-65e-next-saved-view-rollout-target.json')),
            true
        );

        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65f-sales-invoice-collections-saved-view-controls-contract.json')),
            true
        );

        $this->assertSame('Phase 65F', $contract['phase']);
        $this->assertSame('Phase 65E clean', $contract['baseline']['phase']);
        $this->assertSame('dd92db4', $contract['baseline']['commit']);
        $this->assertSame('1155 passed / 10046 assertions', $contract['baseline']['previous_tests']);

        $this->assertSame('sales-invoice-collections', $lock['selected_target']['key']);
        $this->assertSame($lock['selected_target']['key'], $contract['target']['key']);
        $this->assertSame($lock['selected_target']['view_path'], $contract['target']['view_path']);
        $this->assertSame($lock['selected_target']['priority_score'], $contract['target']['priority_score']);
        $this->assertFalse($contract['target']['registered_at_lock_time']);
        $this->assertFalse($contract['target']['print_only_candidate']);
    }

    public function test_phase_65f_contract_captures_current_target_surface(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65f-sales-invoice-collections-saved-view-controls-contract.json')),
            true
        );

        $this->assertFileExists(base_path($contract['target']['view_path']));
        $this->assertFileExists(base_path($contract['target']['controller_path']));

        $view = file_get_contents(base_path($contract['target']['view_path']));
        $controller = file_get_contents(base_path($contract['target']['controller_path']));

        $this->assertStringContainsString('<!DOCTYPE html>', $view);
        $this->assertStringContainsString('data-testid="sales-invoice-collection-report-page"', $view);
        $this->assertStringContainsString('data-testid="sales-invoice-collection-summary-card"', $view);
        $this->assertStringContainsString('data-testid="sales-invoice-collection-invoices-card"', $view);

        $this->assertFalse($contract['current_state_evidence']['contains_interactive_form']);
        $this->assertFalse($contract['current_state_evidence']['contains_get_form']);
        $this->assertFalse($contract['current_state_evidence']['contains_saved_view_controls_include_or_inline_config']);
        $this->assertTrue($contract['current_state_evidence']['controller_filters_remaining_amount_positive']);
        $this->assertTrue($contract['current_state_evidence']['controller_limits_to_50_rows']);

        $this->assertStringContainsString("->where('remaining_amount', '>', 0)", $controller);
        $this->assertStringContainsString('->limit(50)', $controller);
    }

    public function test_phase_65f_contract_defines_empty_filter_rollout_routes_and_registry_contract(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65f-sales-invoice-collections-saved-view-controls-contract.json')),
            true
        );

        $this->assertSame('eligible_for_empty_filter_saved_view_controls_rollout', $contract['eligibility_decision']['status']);
        $this->assertSame('empty_filter_report_saved_views', $contract['eligibility_decision']['rollout_mode']);

        $this->assertTrue(Route::has($contract['route_contract']['existing_index_route']));
        $this->assertTrue(Route::has($contract['route_contract']['json_export_route_to_add']));
        $this->assertTrue(Route::has($contract['route_contract']['saved_view_store_route_to_add']));

        $this->assertSame('reports.sales-invoice-collections.index', $contract['registry_contract']['index_route']);
        $this->assertSame('reports.sales-invoice-collections.json', $contract['registry_contract']['export_route']);
        $this->assertSame('reports.sales-invoice-collections.saved-views.store', $contract['registry_contract']['saved_view_store_route']);
        $this->assertSame([], $contract['registry_contract']['hidden_fields']);
        $this->assertSame([], $contract['view_contract']['hidden_fields']);
    }

    public function test_phase_65f_contract_defines_expected_config_partial_and_test_ids(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65f-sales-invoice-collections-saved-view-controls-contract.json')),
            true
        );

        $this->assertSame(
            'reports.partials.sales-invoice-collections-saved-view-controls-config',
            $contract['view_contract']['config_partial']
        );

        $this->assertSame(
            'resources/views/reports/partials/sales-invoice-collections-saved-view-controls-config.blade.php',
            $contract['view_contract']['config_partial_path']
        );

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
        ] as $key) {
            $this->assertArrayHasKey($key, $contract['view_contract']['test_ids']);
            $this->assertStringStartsWith('sales-invoice-collections-', $contract['view_contract']['test_ids'][$key]);
        }
    }

    public function test_phase_65f_contract_is_documented(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-65f-sales-invoice-collections-saved-view-controls-contract.json')),
            true
        );

        $doc = file_get_contents(base_path('docs/phase-65f-sales-invoice-collections-saved-view-controls-contract.md'));

        $this->assertStringContainsString('Phase 65F', $doc);
        $this->assertStringContainsString('dd92db4', $doc);
        $this->assertStringContainsString('1155 passed / 10046 assertions', $doc);
        $this->assertStringContainsString('sales-invoice-collections', $doc);
        $this->assertStringContainsString($contract['view_contract']['config_partial_path'], $doc);
        $this->assertStringContainsString('Phase 65G', $doc);
    }
}
