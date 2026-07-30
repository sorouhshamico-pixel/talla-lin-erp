<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase129ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyStatusResetContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-129a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-status-reset-contract.json';

    public function test_contract_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-129a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-status-reset-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 129A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '351e1067fc8adb3a0391426f9dae0c6a24c08618',
            $document['baseline']['commit']
        );
        $this->assertSame(2473, $document['baseline']['tests']);
        $this->assertSame(25690, $document['baseline']['assertions']);
    }

    public function test_phase_is_documentation_and_tests_only(): void
    {
        $scope = $this->document()['scope'];

        foreach ($scope as $key => $value) {
            if ($key === 'documentation_and_tests_only') {
                $this->assertTrue($value, $key);
                continue;
            }

            $this->assertFalse($value, $key);
        }
    }

    public function test_status_labels_sources_and_state_management_are_locked(): void
    {
        $contract = $this->document()['copy_status_reset_contract'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary-copy-status',
            $contract['status_element']['id']
        );
        $this->assertSame(
            [
                'idle' => '',
                'success' => 'Copied',
                'failure' => 'Copy failed',
                'unavailable' => 'Summary unavailable',
            ],
            $contract['status_labels']
        );
        $this->assertSame(
            'manualRefreshOutcomeSummary.dataset.summaryState',
            $contract['reset_sources']['summary_state']
        );
        $this->assertSame(
            'manualRefreshOutcomeSummaryCopyAvailability.dataset.copyAvailability',
            $contract['reset_sources']['availability_state']
        );
        $this->assertSame(
            'resetManualRefreshOutcomeSummaryCopyStatus',
            $contract['state_management']['resetter']
        );
        $this->assertSame(
            'setManualRefreshOutcomeSummaryCopyStatus',
            $contract['state_management']['status_setter_reused']
        );
        $this->assertFalse(
            $contract['state_management']['persistent_storage_used']
        );
    }

    public function test_reset_rules_and_copy_behavior_are_locked(): void
    {
        $contract = $this->document()['copy_status_reset_contract'];

        foreach ([
            'reset_to_idle_when_summary_becomes_available',
            'reset_to_unavailable_when_summary_becomes_unavailable',
            'reset_to_unavailable_when_clipboard_unsupported',
            'reset_before_new_copy_attempt',
            'preserve_success_until_summary_or_availability_changes',
            'preserve_failure_until_summary_or_availability_changes',
            'reset_on_initialization',
        ] as $key) {
            $this->assertTrue($contract['reset_rules'][$key], $key);
        }

        foreach ([
            'reset_on_automatic_request_without_summary_change',
            'timer_added',
            'polling_added',
            'timeout_added',
        ] as $key) {
            $this->assertFalse($contract['reset_rules'][$key], $key);
        }

        $this->assertSame(
            "setManualRefreshOutcomeSummaryCopyStatus('Copied');",
            $contract['copy_behavior']['success_literal_preserved']
        );
        $this->assertTrue(
            $contract['copy_behavior']['promise_callbacks_preserved']
        );
        $this->assertFalse(
            $contract['copy_behavior']['fallback_added']
        );
        $this->assertFalse(
            $contract['copy_behavior']['try_catch_added_before_load_health']
        );
    }

    public function test_availability_legacy_compatibility_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['copy_status_reset_contract'];

        $this->assertSame(
            [
                'unavailable',
                'available',
                'unsupported',
            ],
            $contract['availability_feedback']['states_preserved']
        );
        $this->assertTrue(
            $contract['legacy_contract']['must_remain_unchanged']
        );
        $this->assertTrue(
            $contract['legacy_contract']['phase_114b_through_120b_try_catch_ordering_preserved']
        );
        $this->assertSame(
            'lastManualRefreshOutcomeAt.toLocaleString();',
            $contract['legacy_contract']['phase_123b_literal_fallback_preserved']
        );
        $this->assertTrue(
            $contract['legacy_contract']['phase_128b_availability_feedback_preserved']
        );

        foreach ($contract['compatibility'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        $this->assertSame(
            2,
            $contract['planned_implementation']['maximum_modified_files']
        );
        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse(
            $document['workflow']['post_commit_full_suite']
        );
        $this->assertSame(
            'Phase 129B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(base_path(self::JSON_PATH)),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
