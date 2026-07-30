<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase134ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyLastOutcomeContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-134a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-last-outcome-contract.json';

    public function test_contract_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-134a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-last-outcome-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 134A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '1dc5133958d1bad1fea5c8362cd965876a3cb643',
            $document['baseline']['commit']
        );
        $this->assertSame(2556, $document['baseline']['tests']);
        $this->assertSame(26931, $document['baseline']['assertions']);
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

    public function test_outcome_element_state_and_formatting_rules_are_locked(): void
    {
        $contract = $this->document()['copy_last_outcome_contract'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary-copy-last-outcome',
            $contract['outcome_element']['id']
        );
        $this->assertSame(
            'Last copy outcome:',
            $contract['outcome_element']['prefix']
        );
        $this->assertSame(
            'lastManualRefreshOutcomeSummaryCopyOutcome',
            $contract['state_management']['variable']
        );
        $this->assertSame(
            'renderManualRefreshOutcomeSummaryCopyLastOutcome',
            $contract['state_management']['renderer']
        );
        $this->assertSame(
            ['unavailable', 'success', 'failure'],
            $contract['state_management']['allowed_values']
        );
        $this->assertSame(
            'Not available',
            $contract['formatting_rules']['unavailable_label']
        );
        $this->assertSame(
            'Success',
            $contract['formatting_rules']['success_label']
        );
        $this->assertSame(
            'Failure',
            $contract['formatting_rules']['failure_label']
        );
        $this->assertSame(
            3,
            $contract['formatting_rules']['renderer_invocation_count']
        );
    }

    public function test_metrics_copy_render_and_legacy_rules_are_locked(): void
    {
        $contract = $this->document()['copy_last_outcome_contract'];

        foreach ([
            'attempt_counter_preserved',
            'success_counter_preserved',
            'failure_counter_preserved',
            'success_rate_preserved',
        ] as $key) {
            $this->assertTrue(
                $contract['counting_and_rate_preservation'][$key],
                $key
            );
        }

        $this->assertSame(
            'successes + failures',
            $contract['counting_and_rate_preservation']['success_rate_denominator_preserved']
        );
        $this->assertSame(
            1,
            $contract['counting_and_rate_preservation']['success_rate_precision_preserved']
        );

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
            $contract['legacy_contract']['phase_133b_success_rate_preserved']
        );
    }

    public function test_compatibility_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['copy_last_outcome_contract'];

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
            'Phase 134B',
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
