<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase68ABulkSelectionContractTest extends TestCase
{
    public function test_phase_68a_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-68a-saved-view-management-bulk-selection-contract.json'));
        $this->assertFileExists(base_path('docs/phase-68a-saved-view-management-bulk-selection-contract.md'));
    }

    public function test_phase_68a_contract_matches_baseline_and_scope(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-68a-saved-view-management-bulk-selection-contract.json')),
            true
        );

        $this->assertSame('Phase 68A', $contract['phase']);
        $this->assertSame('Phase 67E clean', $contract['baseline']['phase']);
        $this->assertSame('c54365e', $contract['baseline']['commit']);
        $this->assertSame('1288 passed / 11360 assertions', $contract['baseline']['previous_tests']);
        $this->assertSame('audit_contract', $contract['scope']['type']);
        $this->assertFalse($contract['scope']['implementation_changes_expected']);

        foreach ([
            'app/Http/Controllers/ReportSavedViewController.php',
            'app/Services/ReportSavedViewService.php',
            'routes/web.php',
            'resources/views/reports/saved-views/index.blade.php',
            'resources/views/reports/saved-views/edit.blade.php',
            'app/Models/ReportSavedView.php',
            'app/Support/Reports/ReportSavedViewRegistry.php',
        ] as $excludedFile) {
            $this->assertContains($excludedFile, $contract['scope']['excluded_files']);
        }
    }

    public function test_phase_68a_contract_documented_pre_implementation_bulk_selection_gap(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-68a-saved-view-management-bulk-selection-contract.json')),
            true
        );

        foreach ([
            'single_row_actions_exist',
            'delete_all_action_exists',
            'bulk_selection_checkboxes_absent',
            'select_all_checkbox_absent',
            'bulk_delete_form_absent',
            'bulk_destroy_route_absent',
            'bulk_destroy_controller_method_absent',
        ] as $key) {
            $this->assertTrue($contract['current_state'][$key], $key);
        }

        $this->assertSame('Implement Saved View Management Bulk Selection', $contract['phase_68b_recommendation']['title']);
        $this->assertSame('medium', $contract['phase_68b_recommendation']['risk']);
        $this->assertNotEmpty($contract['phase_68b_recommendation']['implementation_targets']);
        $this->assertNotEmpty($contract['guardrails']);
    }

    public function test_phase_68a_contract_remains_historical_after_phase_68b_implementation(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-68a-saved-view-management-bulk-selection-contract.json')),
            true
        );

        $this->assertSame('audit_contract', $contract['scope']['type']);
        $this->assertFalse($contract['scope']['implementation_changes_expected']);

        foreach ([
            'bulk_selection_checkboxes_absent',
            'select_all_checkbox_absent',
            'bulk_delete_form_absent',
            'bulk_destroy_route_absent',
            'bulk_destroy_controller_method_absent',
        ] as $preImplementationGap) {
            $this->assertTrue($contract['current_state'][$preImplementationGap], $preImplementationGap);
        }
    }

    public function test_phase_68a_markdown_mentions_selection_scope_and_next_phase(): void
    {
        $markdown = file_get_contents(base_path('docs/phase-68a-saved-view-management-bulk-selection-contract.md'));

        foreach ([
            'Phase 68A',
            'Phase 68B',
            'select-all',
            'per-row checkboxes',
            'selected saved view IDs only',
            'selection-scoped',
            'Risk level: medium',
        ] as $expectedText) {
            $this->assertStringContainsString($expectedText, $markdown);
        }
    }
}
