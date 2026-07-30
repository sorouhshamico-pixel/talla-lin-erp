<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase135CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyLastOutcomeTimestampFinalizationTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-135c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-last-outcome-timestamp-finalization.json';

    public function test_finalization_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-135c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-last-outcome-timestamp-finalization.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 135C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '59d1add8a9f4c5aaee370e64684c4b9299e99a0d',
            $document['baseline']['commit']
        );
        $this->assertSame(2583, $document['baseline']['tests']);
        $this->assertSame(27278, $document['baseline']['assertions']);
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

    public function test_timestamp_state_recording_and_formatting_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary-copy-last-outcome-at',
            $locked['timestamp_element']['id']
        );
        $this->assertSame(
            'lastManualRefreshOutcomeSummaryCopyOutcomeAt',
            $locked['state_management']['variable']
        );
        $this->assertSame(
            'renderManualRefreshOutcomeSummaryCopyLastOutcomeAt',
            $locked['state_management']['renderer']
        );
        $this->assertNull($locked['state_management']['initial_value']);
        $this->assertSame('Date', $locked['state_management']['runtime_type']);

        $this->assertSame(
            1,
            $locked['recording_rules']['resolved_write_assignment_count']
        );
        $this->assertSame(
            1,
            $locked['recording_rules']['rejected_write_assignment_count']
        );

        foreach ([
            'unavailable_click_recorded',
            'unsupported_clipboard_click_recorded',
            'initialization_recorded',
            'manual_refresh_completion_recorded',
            'status_reset_recorded',
        ] as $key) {
            $this->assertFalse($locked['recording_rules'][$key], $key);
        }

        $this->assertSame(
            'toLocaleString',
            $locked['formatting_rules']['valid_date_format']
        );
        $this->assertSame(
            3,
            $locked['formatting_rules']['renderer_invocation_count']
        );
    }

    public function test_source_order_metrics_copy_render_and_legacy_contract_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        foreach ($locked['source_order_lock'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ([
            'attempt_counter_preserved',
            'success_counter_preserved',
            'failure_counter_preserved',
            'success_rate_preserved',
            'last_outcome_preserved',
        ] as $key) {
            $this->assertTrue($locked['metrics_preservation'][$key], $key);
        }

        $this->assertSame(
            3,
            $locked['metrics_preservation']['success_rate_renderer_invocation_count']
        );
        $this->assertSame(
            3,
            $locked['metrics_preservation']['last_outcome_renderer_invocation_count']
        );

        foreach ([
            'timer_added',
            'polling_added',
            'timeout_added',
            'automatic_reset_added',
        ] as $key) {
            $this->assertFalse($locked['render_rules'][$key], $key);
        }

        $this->assertSame(
            'navigator.clipboard.writeText',
            $locked['copy_behavior']['clipboard_api']
        );
        $this->assertTrue(
            $locked['copy_behavior']['promise_callbacks_preserved']
        );
        $this->assertFalse(
            $locked['copy_behavior']['fallback_added']
        );
        $this->assertFalse(
            $locked['copy_behavior']['try_catch_added_before_load_health']
        );

        $this->assertTrue($locked['legacy_contract']['preserved']);
        $this->assertSame(
            'lastManualRefreshOutcomeAt.toLocaleString();',
            $locked['legacy_contract']['phase_123b_literal_fallback_preserved']
        );
        $this->assertTrue(
            $locked['legacy_contract']['phase_134b_last_outcome_preserved']
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
            $locked['implementation_scope']['phase_135b_test_added']
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
            'Phase 136A',
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
