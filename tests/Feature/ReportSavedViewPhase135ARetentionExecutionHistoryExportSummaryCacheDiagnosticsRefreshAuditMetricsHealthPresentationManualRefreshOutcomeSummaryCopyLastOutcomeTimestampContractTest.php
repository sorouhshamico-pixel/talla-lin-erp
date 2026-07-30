<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase135ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyLastOutcomeTimestampContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-135a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-last-outcome-timestamp-contract.json';

    public function test_contract_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-135a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-last-outcome-timestamp-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 135A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '5d5d6bc9e4e4eee1cbf6aca01d361c1ef6899901',
            $document['baseline']['commit']
        );
        $this->assertSame(2572, $document['baseline']['tests']);
        $this->assertSame(27144, $document['baseline']['assertions']);
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

    public function test_timestamp_element_state_and_recording_rules_are_locked(): void
    {
        $contract = $this->document()['copy_last_outcome_timestamp_contract'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary-copy-last-outcome-at',
            $contract['timestamp_element']['id']
        );
        $this->assertSame(
            'Last copy outcome at:',
            $contract['timestamp_element']['prefix']
        );
        $this->assertSame(
            'lastManualRefreshOutcomeSummaryCopyOutcomeAt',
            $contract['state_management']['variable']
        );
        $this->assertSame(
            'renderManualRefreshOutcomeSummaryCopyLastOutcomeAt',
            $contract['state_management']['renderer']
        );
        $this->assertNull(
            $contract['state_management']['initial_value']
        );
        $this->assertSame(
            'Date',
            $contract['state_management']['value_type']
        );

        foreach ([
            'record_on_resolved_clipboard_write',
            'record_on_rejected_clipboard_write',
        ] as $key) {
            $this->assertTrue($contract['recording_rules'][$key], $key);
        }

        foreach ([
            'record_on_unavailable_click',
            'record_on_unsupported_clipboard_click',
            'record_on_initialization',
            'record_on_manual_refresh_completion',
            'record_on_status_reset',
        ] as $key) {
            $this->assertFalse($contract['recording_rules'][$key], $key);
        }

        $this->assertSame(
            1,
            $contract['recording_rules']['success_assignment_count']
        );
        $this->assertSame(
            1,
            $contract['recording_rules']['failure_assignment_count']
        );
    }

    public function test_formatting_metrics_copy_render_and_legacy_rules_are_locked(): void
    {
        $contract = $this->document()['copy_last_outcome_timestamp_contract'];

        $this->assertSame(
            'Not available',
            $contract['formatting_rules']['null_label']
        );
        $this->assertSame(
            'toLocaleString',
            $contract['formatting_rules']['valid_date_format']
        );
        $this->assertSame(
            3,
            $contract['formatting_rules']['renderer_invocation_count']
        );

        foreach ([
            'attempt_counter_preserved',
            'success_counter_preserved',
            'failure_counter_preserved',
            'success_rate_preserved',
            'last_outcome_preserved',
        ] as $key) {
            $this->assertTrue($contract['metrics_preservation'][$key], $key);
        }

        foreach ([
            'timer_added',
            'polling_added',
            'timeout_added',
            'automatic_reset_added',
        ] as $key) {
            $this->assertFalse($contract['render_rules'][$key], $key);
        }

        $this->assertTrue(
            $contract['copy_behavior']['promise_callbacks_preserved']
        );
        $this->assertFalse(
            $contract['copy_behavior']['fallback_added']
        );
        $this->assertTrue(
            $contract['legacy_contract']['must_remain_unchanged']
        );
        $this->assertSame(
            'lastManualRefreshOutcomeAt.toLocaleString();',
            $contract['legacy_contract']['phase_123b_literal_fallback_preserved']
        );
        $this->assertTrue(
            $contract['legacy_contract']['phase_134b_last_outcome_preserved']
        );
    }

    public function test_compatibility_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['copy_last_outcome_timestamp_contract'];

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
            'Phase 135B',
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
