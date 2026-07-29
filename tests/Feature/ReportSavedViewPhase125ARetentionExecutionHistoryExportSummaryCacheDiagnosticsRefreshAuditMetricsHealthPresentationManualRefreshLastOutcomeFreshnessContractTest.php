<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase125ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshLastOutcomeFreshnessContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-125a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-last-outcome-freshness-contract.json';

    public function test_contract_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));

        $document = $this->document();

        $this->assertSame('Phase 125A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '3f7fcd3fbbbe5919edc91fc1ed26d6c26108b5d5',
            $document['baseline']['commit']
        );
        $this->assertSame(2413, $document['baseline']['tests']);
        $this->assertSame(24827, $document['baseline']['assertions']);
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

    public function test_freshness_contract_is_locked(): void
    {
        $contract = $this->document()[
            'manual_refresh_last_outcome_freshness_contract'
        ];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-last-outcome-freshness',
            $contract['element']['id']
        );
        $this->assertSame(
            [
                'unavailable' => 'Unavailable',
                'fresh' => 'Fresh',
                'stale' => 'Stale',
            ],
            $contract['states']
        );
        $this->assertSame(
            'lastManualRefreshOutcomeAt',
            $contract['state_management']['source_state']
        );
        $this->assertSame(
            'formatLastManualRefreshOutcomeFreshness',
            $contract['state_management']['formatter']
        );
        $this->assertSame(
            'renderLastManualRefreshOutcomeFreshness',
            $contract['state_management']['renderer']
        );
        $this->assertSame(
            14,
            $contract['thresholds']['fresh_maximum_age_minutes']
        );
        $this->assertSame(
            15,
            $contract['thresholds']['stale_minimum_age_minutes']
        );
    }

    public function test_update_rules_compatibility_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document[
            'manual_refresh_last_outcome_freshness_contract'
        ];

        foreach ([
            'renders_when_last_outcome_timestamp_updates',
            'uses_same_completed_at_value',
            'validated_healthy_updates_freshness',
            'validated_unhealthy_updates_freshness',
            'manual_failure_updates_freshness',
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

        foreach ($contract['compatibility'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        $this->assertSame(
            2,
            $contract['planned_implementation']['maximum_modified_files']
        );
        $this->assertSame(
            'Phase 125B',
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
