<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase123ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshLastOutcomeTimestampContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-123a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-last-outcome-timestamp-contract.json';

    public function test_contract_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-123a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-last-outcome-timestamp-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 123A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '93d508529304b4b46e7e6be99af17ade12f09963',
            $document['baseline']['commit']
        );
        $this->assertSame(2384, $document['baseline']['tests']);
        $this->assertSame(24394, $document['baseline']['assertions']);
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
            'manual_refresh_last_outcome_timestamp_contract'
        ];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-last-outcome-at',
            $contract['element']['id']
        );
        $this->assertSame('time', $contract['element']['element']);
        $this->assertSame(
            'Last manual refresh outcome at:',
            $contract['element']['prefix']
        );
        $this->assertSame(
            'Not available',
            $contract['element']['initial_text']
        );
        $this->assertFalse(
            $contract['element']['datetime_initially_present']
        );

        $this->assertSame(
            'lastManualRefreshOutcomeAt',
            $contract['state_management']['state_variable']
        );
        $this->assertNull(
            $contract['state_management']['initial_value']
        );
        $this->assertSame(
            'renderLastManualRefreshOutcomeTimestamp',
            $contract['state_management']['renderer']
        );
        $this->assertSame(
            'setLastManualRefreshOutcomeTimestamp',
            $contract['state_management']['setter']
        );
        $this->assertSame(
            'manualRefreshOutcomeTimestampFormatter',
            $contract['state_management']['formatter']
        );

        $this->assertSame(
            'client clock',
            $contract['formatting']['source']
        );
        $this->assertSame('medium', $contract['formatting']['date_style']);
        $this->assertSame('medium', $contract['formatting']['time_style']);
        $this->assertSame(
            'Date.toLocaleString()',
            $contract['formatting']['fallback']
        );
        $this->assertSame(
            'ISO 8601',
            $contract['formatting']['valid_datetime_attribute']
        );
    }

    public function test_update_rules_and_legacy_contract_are_locked(): void
    {
        $contract = $this->document()[
            'manual_refresh_last_outcome_timestamp_contract'
        ];

        foreach ($contract['update_rules'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertTrue(
            $contract['legacy_contract']['must_remain_unchanged']
        );
        $this->assertTrue(
            $contract['legacy_contract']['phase_110b_visual_order_preserved']
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
            'manual_refresh_last_outcome_timestamp_contract'
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
            'Phase 123B',
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
