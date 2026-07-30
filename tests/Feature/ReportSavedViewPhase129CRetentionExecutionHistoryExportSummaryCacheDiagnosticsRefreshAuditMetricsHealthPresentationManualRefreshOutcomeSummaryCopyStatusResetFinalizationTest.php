<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase129CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyStatusResetFinalizationTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-129c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-status-reset-finalization.json';

    public function test_finalization_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-129c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-status-reset-finalization.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 129C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '0a3829a2905f8515897fd2bf625cda70aa9531e3',
            $document['baseline']['commit']
        );
        $this->assertSame(2484, $document['baseline']['tests']);
        $this->assertSame(25823, $document['baseline']['assertions']);
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

    public function test_status_sources_state_management_and_reset_rules_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary-copy-status',
            $locked['status_element']['id']
        );
        $this->assertSame(
            [
                'idle' => '',
                'success' => 'Copied',
                'failure' => 'Copy failed',
                'unavailable' => 'Summary unavailable',
            ],
            $locked['status_labels']
        );
        $this->assertSame(
            'manualRefreshOutcomeSummaryCopyAvailability.dataset.copyAvailability',
            $locked['reset_sources']['availability_state']
        );
        $this->assertSame(
            'resetManualRefreshOutcomeSummaryCopyStatus',
            $locked['state_management']['resetter']
        );
        $this->assertSame(
            'setManualRefreshOutcomeSummaryCopyStatus',
            $locked['state_management']['status_setter']
        );
        $this->assertFalse(
            $locked['state_management']['persistent_storage_used']
        );

        foreach ([
            'reset_to_idle_when_available',
            'reset_to_unavailable_when_summary_unavailable',
            'reset_to_unavailable_when_clipboard_unsupported',
            'reset_before_new_copy_attempt',
            'called_from_availability_renderer',
            'called_from_copy_handler',
            'preserve_success_until_summary_or_availability_changes',
            'preserve_failure_until_summary_or_availability_changes',
            'reset_on_initialization',
        ] as $key) {
            $this->assertTrue($locked['reset_rules'][$key], $key);
        }

        $this->assertSame(2, $locked['reset_rules']['call_count']);

        foreach ([
            'reset_on_automatic_request_without_summary_change',
            'timer_added',
            'polling_added',
            'timeout_added',
        ] as $key) {
            $this->assertFalse($locked['reset_rules'][$key], $key);
        }
    }

    public function test_copy_availability_and_legacy_contract_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

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
        $this->assertTrue(
            $locked['availability_feedback']['button_disabled_state_preserved']
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
            $locked['legacy_contract']['phase_128b_availability_feedback_preserved']
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
            $locked['implementation_scope']['phase_129b_test_added']
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
            'Phase 130A',
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
