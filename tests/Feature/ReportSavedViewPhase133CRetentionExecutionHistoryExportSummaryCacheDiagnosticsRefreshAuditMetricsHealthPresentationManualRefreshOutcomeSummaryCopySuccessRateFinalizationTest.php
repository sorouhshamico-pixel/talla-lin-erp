<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase133CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopySuccessRateFinalizationTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-133c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-success-rate-finalization.json';

    public function test_finalization_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-133c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-success-rate-finalization.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 133C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            'eae46af1b0eddf56063083ed91981facfe8b7cdf',
            $document['baseline']['commit']
        );
        $this->assertSame(2551, $document['baseline']['tests']);
        $this->assertSame(26844, $document['baseline']['assertions']);
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
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary-copy-success-rate',
            $locked['rate_element']['id']
        );
        $this->assertSame(
            'renderManualRefreshOutcomeSummaryCopySuccessRate',
            $locked['state_management']['renderer']
        );
        $this->assertSame(
            'successes + failures',
            $locked['calculation_rules']['completed_writes_formula']
        );
        $this->assertSame(
            '(successes / completed writes) * 100',
            $locked['calculation_rules']['success_rate_formula']
        );
        $this->assertSame(
            'Not available',
            $locked['calculation_rules']['initial_value_when_no_completed_writes']
        );
        $this->assertSame(
            1,
            $locked['calculation_rules']['percentage_precision']
        );
        $this->assertTrue(
            $locked['calculation_rules']['attempt_counter_excluded_from_denominator']
        );
        $this->assertSame(
            3,
            $locked['calculation_rules']['renderer_invocation_count']
        );
    }

    public function test_render_counters_copy_and_legacy_contract_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame('0.0%', $locked['render_rules']['format']);
        $this->assertTrue(
            $locked['render_rules']['finite_number_required']
        );
        $this->assertTrue(
            $locked['render_rules']['clamped_to_range']
        );

        foreach ([
            'timer_added',
            'polling_added',
            'timeout_added',
        ] as $key) {
            $this->assertFalse($locked['render_rules'][$key], $key);
        }

        foreach ([
            'attempt_counter',
            'success_counter',
            'failure_counter',
        ] as $key) {
            $this->assertTrue($locked[$key]['preserved'], $key);
            $this->assertSame(999, $locked[$key]['maximum_value']);
        }

        $this->assertSame(
            'navigator.clipboard.writeText',
            $locked['copy_behavior']['clipboard_api']
        );
        $this->assertTrue(
            $locked['copy_behavior']['promise_callbacks_preserved']
        );
        $this->assertSame(
            "setManualRefreshOutcomeSummaryCopyStatus('Copied');",
            $locked['copy_behavior']['success_literal']
        );
        $this->assertFalse(
            $locked['copy_behavior']['fallback_added']
        );
        $this->assertFalse(
            $locked['copy_behavior']['try_catch_added_before_load_health']
        );

        $this->assertTrue($locked['legacy_contract']['preserved']);
        $this->assertTrue(
            $locked['legacy_contract']['phase_114b_through_120b_try_catch_ordering_preserved']
        );
        $this->assertSame(
            'lastManualRefreshOutcomeAt.toLocaleString();',
            $locked['legacy_contract']['phase_123b_literal_fallback_preserved']
        );
        $this->assertTrue(
            $locked['legacy_contract']['phase_132b_failure_counter_preserved']
        );
    }

    public function test_compatibility_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

        foreach ($locked['compatibility'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        $this->assertTrue(
            $locked['implementation_scope']['partial_modified']
        );
        $this->assertTrue(
            $locked['implementation_scope']['phase_133b_test_added']
        );
        $this->assertSame(
            2,
            $locked['implementation_scope']['maximum_modified_files']
        );

        foreach ([
            'parent_view_modified',
            'controller_modified',
            'route_modified',
            'health_class_modified',
            'database_modified',
            'migration_modified',
            'model_modified',
        ] as $key) {
            $this->assertFalse(
                $locked['implementation_scope'][$key],
                $key
            );
        }

        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse(
            $document['workflow']['post_commit_full_suite']
        );
        $this->assertSame(
            'Phase 134A',
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
