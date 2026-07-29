<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase127ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-127a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-contract.json';

    public function test_contract_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-127a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 127A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '648c625ca97045e08ee78713374b796260cf6043',
            $document['baseline']['commit']
        );
        $this->assertSame(2441, $document['baseline']['tests']);
        $this->assertSame(25234, $document['baseline']['assertions']);
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

    public function test_button_status_copy_source_and_labels_are_locked(): void
    {
        $contract = $this->document()[
            'manual_refresh_outcome_summary_copy_contract'
        ];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary-copy',
            $contract['button']['id']
        );
        $this->assertTrue($contract['button']['disabled_initially']);
        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary-copy-status',
            $contract['status']['id']
        );
        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary',
            $contract['copy_source']['element_id']
        );
        $this->assertFalse(
            $contract['copy_source']['copy_prefix_included']
        );
        $this->assertSame(
            [
                'idle' => 'Copy summary',
                'success' => 'Copied',
                'failure' => 'Copy failed',
                'unavailable' => 'Summary unavailable',
            ],
            $contract['labels']
        );
    }

    public function test_clipboard_availability_and_interaction_rules_are_locked(): void
    {
        $contract = $this->document()[
            'manual_refresh_outcome_summary_copy_contract'
        ];

        $this->assertSame(
            'navigator.clipboard.writeText',
            $contract['clipboard']['primary_api']
        );
        $this->assertTrue(
            $contract['clipboard']['secure_context_required']
        );
        $this->assertFalse(
            $contract['clipboard']['fallback_added']
        );
        $this->assertFalse(
            $contract['clipboard']['exec_command_used']
        );

        foreach ($contract['availability_rules'] as $key => $value) {
            if (in_array($key, [
                'summary_state_source',
                'summary_text_source',
            ], true)) {
                continue;
            }

            $this->assertTrue($value, $key);
        }

        foreach ([
            'copy_only_on_explicit_click',
            'no_copy_on_initial_load',
            'no_copy_on_manual_refresh_completion',
            'no_copy_on_automatic_request',
            'success_status_after_resolved_write',
            'failure_status_after_rejected_write',
        ] as $key) {
            $this->assertTrue(
                $contract['interaction_rules'][$key],
                $key
            );
        }

        foreach ([
            'button_label_restored_after_status',
            'timer_added',
            'polling_added',
        ] as $key) {
            $this->assertFalse(
                $contract['interaction_rules'][$key],
                $key
            );
        }
    }

    public function test_legacy_compatibility_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document[
            'manual_refresh_outcome_summary_copy_contract'
        ];

        $this->assertTrue(
            $contract['legacy_contract']['must_remain_unchanged']
        );
        $this->assertSame(
            'lastManualRefreshOutcomeAt.toLocaleString();',
            $contract['legacy_contract']['phase_123b_literal_fallback_preserved']
        );
        $this->assertTrue(
            $contract['legacy_contract']['phase_126b_summary_format_preserved']
        );
        $this->assertTrue(
            $contract['legacy_contract']['phase_126b_summary_renderer_preserved']
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
            'Phase 127B',
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
