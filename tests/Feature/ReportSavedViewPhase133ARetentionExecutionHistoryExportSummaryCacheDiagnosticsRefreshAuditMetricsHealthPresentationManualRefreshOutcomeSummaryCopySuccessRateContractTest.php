<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase133ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopySuccessRateContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-133a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-success-rate-contract.json';

    public function test_contract_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-133a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-success-rate-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 133A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '613c4f83254fd1a98b8d6e6fb0a2c3f7b1d7694d',
            $document['baseline']['commit']
        );
        $this->assertSame(2540, $document['baseline']['tests']);
        $this->assertSame(26714, $document['baseline']['assertions']);
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

    public function test_rate_element_state_and_calculation_rules_are_locked(): void
    {
        $contract = $this->document()['copy_success_rate_contract'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary-copy-success-rate',
            $contract['rate_element']['id']
        );
        $this->assertSame(
            'Copy success rate:',
            $contract['rate_element']['prefix']
        );
        $this->assertSame(
            'renderManualRefreshOutcomeSummaryCopySuccessRate',
            $contract['state_management']['renderer']
        );
        $this->assertSame(
            'successes + failures',
            $contract['calculation_rules']['completed_writes_formula']
        );
        $this->assertSame(
            '(successes / completed writes) * 100',
            $contract['calculation_rules']['success_rate_formula']
        );
        $this->assertSame(
            'Not available',
            $contract['calculation_rules']['initial_value_when_no_completed_writes']
        );
        $this->assertSame(
            1,
            $contract['calculation_rules']['percentage_precision']
        );
        $this->assertTrue(
            $contract['calculation_rules']['attempt_counter_excluded_from_denominator']
        );
    }

    public function test_render_counter_and_copy_rules_are_locked(): void
    {
        $contract = $this->document()['copy_success_rate_contract'];

        foreach ([
            'render_after_success_record',
            'render_after_failure_record',
            'render_on_initialization',
        ] as $key) {
            $this->assertTrue($contract['calculation_rules'][$key], $key);
        }

        foreach ([
            'timer_added',
            'polling_added',
            'timeout_added',
        ] as $key) {
            $this->assertFalse($contract['render_rules'][$key], $key);
        }

        $this->assertSame(
            999,
            $contract['attempt_counter']['maximum_value_preserved']
        );
        $this->assertSame(
            999,
            $contract['success_counter']['maximum_value_preserved']
        );
        $this->assertSame(
            999,
            $contract['failure_counter']['maximum_value_preserved']
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
        $contract = $document['copy_success_rate_contract'];

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
            $contract['legacy_contract']['phase_132b_failure_counter_preserved']
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
            'Phase 133B',
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
