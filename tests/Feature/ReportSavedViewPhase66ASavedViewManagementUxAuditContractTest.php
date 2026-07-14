<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewRegistry;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewPhase66ASavedViewManagementUxAuditContractTest extends TestCase
{
    public function test_phase_66a_audit_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-66a-saved-view-management-ux-audit-contract.json'));
        $this->assertFileExists(base_path('docs/phase-66a-saved-view-management-ux-audit-contract.md'));
    }

    public function test_phase_66a_audit_contract_matches_baseline_and_scope(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-66a-saved-view-management-ux-audit-contract.json')),
            true
        );

        $this->assertSame('Phase 66A', $contract['phase']);
        $this->assertSame('Phase 65Q clean', $contract['baseline']['phase']);
        $this->assertSame('308ab02', $contract['baseline']['commit']);
        $this->assertSame('1216 passed / 10744 assertions', $contract['baseline']['previous_tests']);
        $this->assertSame('audit_contract_only', $contract['scope']['type']);
        $this->assertFalse($contract['scope']['implementation_changes_allowed']);
    }

    public function test_saved_view_management_routes_and_controller_actions_are_present(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-66a-saved-view-management-ux-audit-contract.json')),
            true
        );

        foreach ([
            'reports.saved-views.index',
            'reports.saved-views.edit',
            'reports.saved-views.update',
            'reports.saved-views.duplicate',
            'reports.saved-views.apply',
            'reports.saved-views.destroy-all',
            'reports.saved-views.make-default',
            'reports.saved-views.destroy',
        ] as $routeName) {
            $this->assertTrue(Route::has($routeName), $routeName);
            $this->assertTrue($contract['current_capabilities']['routes'][$routeName], $routeName);
        }

        foreach ([
            'index',
            'edit',
            'update',
            'duplicate',
            'apply',
            'make_default',
            'destroy',
            'destroy_all',
            'ownership_guard',
        ] as $capability) {
            $this->assertTrue($contract['current_capabilities']['controller_actions'][$capability], $capability);
        }
    }

    public function test_saved_view_management_views_expose_expected_ux_controls(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-66a-saved-view-management-ux-audit-contract.json')),
            true
        );

        foreach ([
            'page_test_id',
            'table_test_id',
            'count_test_id',
            'empty_state_test_id',
            'clear_all_button',
            'open_link',
            'edit_link',
            'apply_link',
            'duplicate_button',
            'make_default_button',
            'delete_button',
            'default_badge',
        ] as $capability) {
            $this->assertTrue($contract['current_capabilities']['index_view'][$capability], $capability);
        }

        foreach ([
            'page_test_id',
            'form_test_id',
            'name_input',
            'default_checkbox',
            'filter_inputs',
            'submit_button',
        ] as $capability) {
            $this->assertTrue($contract['current_capabilities']['edit_view'][$capability], $capability);
        }
    }

    public function test_registry_alignment_gap_is_documented(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-66a-saved-view-management-ux-audit-contract.json')),
            true
        );

        $this->assertSame(ReportSavedViewRegistry::count(), $contract['registry_alignment']['registry_report_count']);
        $this->assertSame(13, $contract['registry_alignment']['registry_report_count']);
        $this->assertTrue($contract['registry_alignment']['uses_static_labels_instead_of_registry']);
        $this->assertTrue($contract['registry_alignment']['uses_static_routes_instead_of_registry']);

        $this->assertNotEmpty($contract['registry_alignment']['missing_label_keys_from_controller_static_map']);
        $this->assertNotEmpty($contract['registry_alignment']['missing_route_keys_from_controller_static_map']);

        $this->assertContains('financial-dashboard', $contract['registry_alignment']['missing_label_keys_from_controller_static_map']);
        $this->assertContains('financial-dashboard', $contract['registry_alignment']['missing_route_keys_from_controller_static_map']);

        $findings = collect($contract['audit_findings'])->keyBy('key');

        $this->assertSame('high', $findings['registry_alignment_gap']['severity']);
        $this->assertSame('medium', $findings['index_action_density']['severity']);
        $this->assertSame('medium', $findings['edit_filter_mutation_risk']['severity']);
        $this->assertSame('positive', $findings['ownership_guard_present']['severity']);
    }

    public function test_phase_66a_markdown_is_documented(): void
    {
        $doc = file_get_contents(base_path('docs/phase-66a-saved-view-management-ux-audit-contract.md'));

        $this->assertStringContainsString('Phase 66A', $doc);
        $this->assertStringContainsString('308ab02', $doc);
        $this->assertStringContainsString('1216 passed / 10744 assertions', $doc);
        $this->assertStringContainsString('registry_alignment_gap', $doc);
        $this->assertStringContainsString('Phase 66B recommendations', $doc);
    }
}
