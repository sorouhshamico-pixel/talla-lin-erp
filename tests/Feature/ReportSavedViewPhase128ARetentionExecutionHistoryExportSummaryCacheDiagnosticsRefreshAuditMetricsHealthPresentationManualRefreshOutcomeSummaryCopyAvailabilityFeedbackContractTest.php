<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase128ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyAvailabilityFeedbackContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-128a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-availability-feedback-contract.json';

    public function test_contract_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-128a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-availability-feedback-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 128A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '18103b49541e998af6df5133f9b0ae15ea94e532',
            $document['baseline']['commit']
        );
        $this->assertSame(2457, $document['baseline']['tests']);
        $this->assertSame(25458, $document['baseline']['assertions']);
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

    public function test_feedback_element_states_and_sources_are_locked(): void
    {
        $contract = $this->document()[
            'copy_availability_feedback_contract'
        ];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary-copy-availability',
            $contract['feedback_element']['id']
        );
        $this->assertSame(
            'Copy unavailable until a manual refresh completes.',
            $contract['feedback_element']['initial_text']
        );
        $this->assertSame(
            'unavailable',
            $contract['feedback_element']['initial_state']
        );

        $this->assertSame(
            [
                'unavailable',
                'available',
                'unsupported',
            ],
            array_keys($contract['states'])
        );

        $this->assertSame(
            'manualRefreshOutcomeSummary.dataset.summaryState',
            $contract['sources']['summary_state']
        );
        $this->assertSame(
            'manualRefreshOutcomeSummaryValue.textContent',
            $contract['sources']['summary_text']
        );
        $this->assertSame(
            'window.isSecureContext',
            $contract['sources']['secure_context']
        );
        $this->assertSame(
            'navigator.clipboard.writeText',
            $contract['sources']['write_text_function']
        );
    }

    public function test_state_management_render_rules_and_copy_behavior_are_locked(): void
    {
        $contract = $this->document()[
            'copy_availability_feedback_contract'
        ];

        $this->assertSame(
            'formatManualRefreshOutcomeSummaryCopyAvailability',
            $contract['state_management']['formatter']
        );
        $this->assertSame(
            'renderManualRefreshOutcomeSummaryCopyAvailabilityFeedback',
            $contract['state_management']['renderer']
        );
        $this->assertTrue(
            $contract['state_management']['existing_availability_renderer_reused']
        );
        $this->assertFalse(
            $contract['state_management']['persistent_storage_used']
        );

        foreach ([
            'unavailable_when_summary_unavailable',
            'unsupported_when_clipboard_missing',
            'unsupported_when_context_not_secure',
            'available_when_summary_present_and_clipboard_supported',
            'button_disabled_matches_feedback_state',
            'rendered_on_initialization',
            'rendered_when_summary_changes',
        ] as $key) {
            $this->assertTrue($contract['render_rules'][$key], $key);
        }

        foreach ([
            'rendered_after_copy_attempt',
            'timer_added',
            'polling_added',
            'periodic_recalculation_added',
        ] as $key) {
            $this->assertFalse($contract['render_rules'][$key], $key);
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
    }

    public function test_legacy_compatibility_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document[
            'copy_availability_feedback_contract'
        ];

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
            $contract['legacy_contract']['phase_126b_summary_format_preserved']
        );
        $this->assertTrue(
            $contract['legacy_contract']['phase_127b_copy_handler_preserved']
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
            'Phase 128B',
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
