<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase126ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-126a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-contract.json';

    public function test_contract_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-126a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 126A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '1112afaf81355872e41731f5374e168eec41eeba',
            $document['baseline']['commit']
        );
        $this->assertSame(2427, $document['baseline']['tests']);
        $this->assertSame(25034, $document['baseline']['assertions']);
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

    public function test_summary_element_states_and_format_are_locked(): void
    {
        $contract = $this->document()[
            'manual_refresh_outcome_summary_contract'
        ];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary',
            $contract['element']['id']
        );
        $this->assertSame('p', $contract['element']['element']);
        $this->assertSame(
            'data-summary-state',
            $contract['element']['data_attribute']
        );
        $this->assertSame(
            [
                'unavailable' => 'Not available',
                'healthy' => 'Healthy',
                'unhealthy' => 'Requires attention',
                'failed' => 'Failed',
            ],
            $contract['states']
        );
        $this->assertSame(
            ' · ',
            $contract['summary_format']['separator']
        );
        $this->assertSame(
            [
                'outcome label',
                'formatted timestamp',
                'formatted age',
                'freshness label',
            ],
            $contract['summary_format']['segments']
        );
        $this->assertFalse(
            $contract['summary_format']['duplicates_business_logic']
        );
    }

    public function test_state_management_update_rules_and_legacy_contract_are_locked(): void
    {
        $contract = $this->document()[
            'manual_refresh_outcome_summary_contract'
        ];

        $this->assertSame(
            [
                'lastManualRefreshOutcome',
                'lastManualRefreshOutcomeAt',
            ],
            $contract['state_management']['sources']
        );
        $this->assertSame(
            'formatManualRefreshOutcomeSummary',
            $contract['state_management']['formatter']
        );
        $this->assertSame(
            'renderManualRefreshOutcomeSummary',
            $contract['state_management']['renderer']
        );

        foreach ([
            'renders_after_outcome_and_timestamp_state_update',
            'validated_healthy_updates_summary',
            'validated_unhealthy_updates_summary',
            'manual_failure_updates_summary',
            'initial_automatic_request_does_not_update',
            'ignored_concurrent_manual_request_does_not_update',
            'manual_attempt_increment_does_not_update',
        ] as $key) {
            $this->assertTrue($contract['update_rules'][$key], $key);
        }

        foreach ([
            'timer_added',
            'polling_added',
            'periodic_recalculation_added',
        ] as $key) {
            $this->assertFalse($contract['update_rules'][$key], $key);
        }

        $this->assertTrue(
            $contract['legacy_contract']['must_remain_unchanged']
        );
        $this->assertTrue(
            $contract['legacy_contract']['phase_125b_last_outcome_freshness_preserved']
        );
    }

    public function test_compatibility_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document[
            'manual_refresh_outcome_summary_contract'
        ];

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
            'Phase 126B',
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
