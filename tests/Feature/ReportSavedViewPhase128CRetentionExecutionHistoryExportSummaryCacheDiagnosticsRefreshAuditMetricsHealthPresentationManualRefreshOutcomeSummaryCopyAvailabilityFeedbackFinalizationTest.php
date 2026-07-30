<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase128CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyAvailabilityFeedbackFinalizationTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-128c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-availability-feedback-finalization.json';

    public function test_finalization_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-128c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-availability-feedback-finalization.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 128C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '7b225cde6e29dcefab6bac654b39bde2b4c4b151',
            $document['baseline']['commit']
        );
        $this->assertSame(2468, $document['baseline']['tests']);
        $this->assertSame(25595, $document['baseline']['assertions']);
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

    public function test_feedback_element_states_sources_and_management_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary-copy-availability',
            $locked['feedback_element']['id']
        );
        $this->assertSame(
            'Copy unavailable until a manual refresh completes.',
            $locked['feedback_element']['initial_text']
        );
        $this->assertSame(
            ['unavailable', 'available', 'unsupported'],
            array_keys($locked['states'])
        );
        $this->assertSame(
            'navigator.clipboard.writeText',
            $locked['sources']['write_text_function']
        );
        $this->assertSame(
            'formatManualRefreshOutcomeSummaryCopyAvailability',
            $locked['state_management']['formatter']
        );
        $this->assertSame(
            'renderManualRefreshOutcomeSummaryCopyAvailabilityFeedback',
            $locked['state_management']['feedback_renderer']
        );
        $this->assertSame(
            'renderManualRefreshOutcomeSummaryCopyAvailability',
            $locked['state_management']['existing_renderer']
        );
        $this->assertTrue(
            $locked['state_management']['existing_renderer_reused']
        );
        $this->assertFalse(
            $locked['state_management']['persistent_storage_used']
        );
    }

    public function test_render_rules_and_copy_behavior_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        foreach ([
            'unavailable_when_summary_unavailable',
            'unsupported_when_context_not_secure',
            'unsupported_when_clipboard_missing',
            'available_when_summary_present_and_clipboard_supported',
            'button_disabled_matches_state',
            'rendered_on_initialization',
            'rendered_when_summary_changes',
        ] as $key) {
            $this->assertTrue($locked['render_rules'][$key], $key);
        }

        foreach ([
            'rendered_after_copy_attempt',
            'timer_added',
            'polling_added',
            'periodic_recalculation_added',
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
    }

    public function test_legacy_compatibility_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

        $this->assertTrue($locked['legacy_contract']['preserved']);
        $this->assertTrue(
            $locked['legacy_contract']['phase_114b_through_120b_try_catch_ordering_preserved']
        );
        $this->assertSame(
            'lastManualRefreshOutcomeAt.toLocaleString();',
            $locked['legacy_contract']['phase_123b_literal_fallback_preserved']
        );
        $this->assertTrue(
            $locked['legacy_contract']['phase_126b_summary_format_preserved']
        );
        $this->assertTrue(
            $locked['legacy_contract']['phase_127b_copy_handler_preserved']
        );

        foreach ($locked['compatibility'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        $this->assertTrue(
            $locked['implementation_scope']['partial_modified']
        );
        $this->assertTrue(
            $locked['implementation_scope']['phase_128b_test_added']
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
            'Phase 129A',
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
