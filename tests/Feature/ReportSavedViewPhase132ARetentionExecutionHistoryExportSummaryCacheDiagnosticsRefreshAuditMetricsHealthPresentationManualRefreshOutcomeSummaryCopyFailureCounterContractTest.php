<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase132ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyFailureCounterContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-132a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-failure-counter-contract.json';

    public function test_contract_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-132a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-failure-counter-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 132A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '31ce8dad4c9254d9327033960f9300a152f8c6d1',
            $document['baseline']['commit']
        );
        $this->assertSame(2523, $document['baseline']['tests']);
        $this->assertSame(26438, $document['baseline']['assertions']);
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

    public function test_counter_element_and_state_management_are_locked(): void
    {
        $contract = $this->document()['copy_failure_counter_contract'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary-copy-failures',
            $contract['counter_element']['id']
        );
        $this->assertSame(
            'Copy failures:',
            $contract['counter_element']['prefix']
        );
        $this->assertSame(
            'manualRefreshOutcomeSummaryCopyFailureCount',
            $contract['state_management']['variable']
        );
        $this->assertSame(
            'renderManualRefreshOutcomeSummaryCopyFailures',
            $contract['state_management']['renderer']
        );
        $this->assertSame(
            'recordManualRefreshOutcomeSummaryCopyFailure',
            $contract['state_management']['recorder']
        );
        $this->assertSame(0, $contract['state_management']['initial_value']);
        $this->assertSame(999, $contract['state_management']['maximum_value']);
        $this->assertFalse(
            $contract['state_management']['persistent_storage_used']
        );
    }

    public function test_counting_render_attempt_success_and_copy_rules_are_locked(): void
    {
        $contract = $this->document()['copy_failure_counter_contract'];

        foreach ([
            'count_rejected_clipboard_write',
            'increment_in_promise_failure_callback',
            'increment_before_failure_status',
            'increment_once_per_rejected_write',
        ] as $key) {
            $this->assertTrue($contract['counting_rules'][$key], $key);
        }

        foreach ([
            'count_resolved_clipboard_write',
            'count_eligible_attempt_before_resolution',
            'count_unavailable_summary_click',
            'count_unsupported_clipboard_click',
            'count_initialization',
            'count_manual_refresh_completion',
            'count_automatic_request',
            'count_status_reset',
        ] as $key) {
            $this->assertFalse($contract['counting_rules'][$key], $key);
        }

        foreach ([
            'integer_only',
            'non_negative_only',
            'clamped_to_maximum',
            'rendered_on_initialization',
        ] as $key) {
            $this->assertTrue($contract['render_rules'][$key], $key);
        }

        foreach ([
            'timer_added',
            'polling_added',
            'timeout_added',
        ] as $key) {
            $this->assertFalse($contract['render_rules'][$key], $key);
        }

        $this->assertTrue(
            $contract['attempt_counter']['increment_before_clipboard_write_preserved']
        );
        $this->assertTrue(
            $contract['success_counter']['increment_in_promise_success_callback_preserved']
        );
        $this->assertTrue(
            $contract['copy_behavior']['promise_callbacks_preserved']
        );
        $this->assertFalse(
            $contract['copy_behavior']['fallback_added']
        );
    }

    public function test_legacy_compatibility_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['copy_failure_counter_contract'];

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
            $contract['legacy_contract']['phase_131b_success_counter_preserved']
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
            'Phase 132B',
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
