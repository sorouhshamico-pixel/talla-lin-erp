<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase122ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshLastOutcomeContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-122a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-last-outcome-contract.json';

    public function test_contract_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-122a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-last-outcome-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 122A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '687d344421bcc62a049548500f092bd033ed8389',
            $document['baseline']['commit']
        );
        $this->assertSame(2368, $document['baseline']['tests']);
        $this->assertSame(24174, $document['baseline']['assertions']);
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

    public function test_element_states_and_state_management_are_locked(): void
    {
        $contract = $this->document()['manual_refresh_last_outcome_contract'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-last-outcome',
            $contract['element']['id']
        );
        $this->assertSame(
            'Last manual refresh outcome:',
            $contract['element']['prefix']
        );
        $this->assertSame(
            'Not available',
            $contract['element']['initial_text']
        );
        $this->assertSame(
            'data-outcome-state',
            $contract['element']['data_attribute']
        );
        $this->assertSame(
            'unavailable',
            $contract['element']['initial_state']
        );

        $this->assertSame(
            ['unavailable', 'healthy', 'unhealthy', 'failed'],
            array_keys($contract['states'])
        );
        $this->assertSame(
            'lastManualRefreshOutcome',
            $contract['state_management']['state_variable']
        );
        $this->assertSame(
            'renderLastManualRefreshOutcome',
            $contract['state_management']['renderer']
        );
        $this->assertSame(
            'setLastManualRefreshOutcome',
            $contract['state_management']['setter']
        );
        $this->assertTrue(
            $contract['state_management']['client_memory_only']
        );
        $this->assertFalse(
            $contract['state_management']['persistent_storage_used']
        );
    }

    public function test_update_rules_and_legacy_contract_are_locked(): void
    {
        $contract = $this->document()['manual_refresh_last_outcome_contract'];

        foreach ($contract['update_rules'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertTrue(
            $contract['legacy_contract']['must_remain_unchanged']
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
        $contract = $document['manual_refresh_last_outcome_contract'];

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
            'Phase 122B',
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
