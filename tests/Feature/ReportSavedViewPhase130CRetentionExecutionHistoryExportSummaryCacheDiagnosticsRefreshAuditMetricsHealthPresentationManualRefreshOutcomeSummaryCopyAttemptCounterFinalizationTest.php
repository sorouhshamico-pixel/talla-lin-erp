<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase130CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyAttemptCounterFinalizationTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-130c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-attempt-counter-finalization.json';

    public function test_finalization_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-130c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-attempt-counter-finalization.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 130C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '974a5545d839b59684170f6668d692236a401587',
            $document['baseline']['commit']
        );
        $this->assertSame(2501, $document['baseline']['tests']);
        $this->assertSame(26070, $document['baseline']['assertions']);
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

    public function test_counter_state_and_counting_rules_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary-copy-attempts',
            $locked['counter_element']['id']
        );
        $this->assertSame(
            'manualRefreshOutcomeSummaryCopyAttemptCount',
            $locked['state_management']['variable']
        );
        $this->assertSame(
            'renderManualRefreshOutcomeSummaryCopyAttempts',
            $locked['state_management']['renderer']
        );
        $this->assertSame(
            'recordManualRefreshOutcomeSummaryCopyAttempt',
            $locked['state_management']['recorder']
        );
        $this->assertSame(0, $locked['state_management']['initial_value']);
        $this->assertSame(999, $locked['state_management']['maximum_value']);
        $this->assertFalse(
            $locked['state_management']['persistent_storage_used']
        );

        foreach ([
            'eligible_explicit_click_counted',
            'resolved_copy_counted',
            'rejected_copy_counted',
            'increment_before_clipboard_write',
        ] as $key) {
            $this->assertTrue($locked['counting_rules'][$key], $key);
        }

        foreach ([
            'unavailable_summary_click_counted',
            'unsupported_clipboard_click_counted',
            'initialization_counted',
            'manual_refresh_completion_counted',
            'automatic_request_counted',
            'status_reset_counted',
        ] as $key) {
            $this->assertFalse($locked['counting_rules'][$key], $key);
        }

        $this->assertSame(
            1,
            $locked['counting_rules']['recorder_invocation_count']
        );
    }

    public function test_render_copy_availability_and_legacy_contract_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        foreach ([
            'integer_only',
            'non_negative_only',
            'clamped_to_maximum',
            'rendered_on_initialization',
        ] as $key) {
            $this->assertTrue($locked['render_rules'][$key], $key);
        }

        foreach ([
            'timer_added',
            'polling_added',
            'timeout_added',
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

        $this->assertSame(
            ['unavailable', 'available', 'unsupported'],
            $locked['availability_feedback']['states']
        );
        $this->assertTrue(
            $locked['availability_feedback']['messages_preserved']
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
            $locked['legacy_contract']['phase_129b_status_reset_preserved']
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
            $locked['implementation_scope']['phase_130b_test_added']
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
            'Phase 131A',
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
