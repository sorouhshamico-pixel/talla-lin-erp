<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase124ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshLastOutcomeAgeContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-124a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-last-outcome-age-contract.json';

    public function test_contract_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-124a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-last-outcome-age-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 124A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '5718588bbb2e9b11d5045a6c3fb8a388e45cd561',
            $document['baseline']['commit']
        );
        $this->assertSame(2398, $document['baseline']['tests']);
        $this->assertSame(24601, $document['baseline']['assertions']);
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

    public function test_element_state_and_formatting_contract_are_locked(): void
    {
        $contract = $this->document()[
            'manual_refresh_last_outcome_age_contract'
        ];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-last-outcome-age',
            $contract['element']['id']
        );
        $this->assertSame('span', $contract['element']['element']);
        $this->assertSame(
            'Last manual refresh outcome age:',
            $contract['element']['prefix']
        );
        $this->assertSame(
            'Not available',
            $contract['element']['initial_text']
        );

        $this->assertSame(
            'lastManualRefreshOutcomeAt',
            $contract['state_management']['source_state']
        );
        $this->assertSame(
            'formatLastManualRefreshOutcomeAge',
            $contract['state_management']['formatter']
        );
        $this->assertSame(
            'renderLastManualRefreshOutcomeAge',
            $contract['state_management']['renderer']
        );
        $this->assertTrue(
            $contract['state_management']['client_memory_only']
        );
        $this->assertFalse(
            $contract['state_management']['persistent_storage_used']
        );

        $this->assertSame(
            'Not available',
            $contract['formatting']['invalid_or_missing']
        );
        $this->assertSame(
            'Less than 1 minute',
            $contract['formatting']['less_than_one_minute']
        );
        $this->assertSame(999, $contract['formatting']['minutes_maximum']);
        $this->assertSame(999, $contract['formatting']['hours_maximum']);
        $this->assertSame(999, $contract['formatting']['days_maximum']);
        $this->assertTrue(
            $contract['formatting']['negative_age_clamped_to_zero']
        );
        $this->assertSame(60, $contract['formatting']['minute_threshold']);
        $this->assertSame(
            1440,
            $contract['formatting']['day_threshold_minutes']
        );
    }

    public function test_update_rules_and_legacy_contract_are_locked(): void
    {
        $contract = $this->document()[
            'manual_refresh_last_outcome_age_contract'
        ];

        foreach ([
            'renders_when_last_outcome_timestamp_updates',
            'uses_same_completed_at_value',
            'validated_healthy_updates_age',
            'validated_unhealthy_updates_age',
            'manual_failure_updates_age',
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
            $contract['legacy_contract']['phase_110b_visual_order_preserved']
        );
        $this->assertTrue(
            $contract['legacy_contract']['phase_111b_refresh_timestamp_preserved']
        );
        $this->assertTrue(
            $contract['legacy_contract']['phase_123b_last_outcome_timestamp_preserved']
        );
        $this->assertSame(
            'const loadHealth = async () => {',
            $contract['legacy_contract']['load_health_signature']
        );
        $this->assertSame(
            "refresh.addEventListener('click', loadHealth);",
            $contract['legacy_contract']['refresh_listener']
        );
        $this->assertSame(
            'loadHealth();',
            $contract['legacy_contract']['initial_load']
        );
    }

    public function test_compatibility_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document[
            'manual_refresh_last_outcome_age_contract'
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
            'Phase 124B',
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
