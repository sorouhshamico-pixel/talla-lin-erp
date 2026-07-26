<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase118ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshAttemptCounterContractTest extends TestCase
{
    private const JSON_PATH = 'docs/phase-118a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-attempt-counter-contract.json';

    public function test_contract_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path('docs/phase-118a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-attempt-counter-contract.md'));

        $document = $this->document();

        $this->assertSame('Phase 118A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame('73746dc3580ceba54b855d2fde800e009c516e73', $document['baseline']['commit']);
    }

    public function test_scope_is_documentation_and_tests_only(): void
    {
        $scope = $this->document()['scope'];

        $this->assertTrue($scope['documentation_and_tests_only']);

        foreach ($scope as $key => $value) {
            if ($key !== 'documentation_and_tests_only') {
                $this->assertFalse($value, $key);
            }
        }
    }

    public function test_counter_and_counting_rules_are_locked(): void
    {
        $contract = $this->document()['manual_refresh_attempt_counter_contract'];

        $this->assertSame('retention-audit-metrics-health-manual-refresh-attempts', $contract['element']['id']);
        $this->assertSame('manualRefreshAttempts', $contract['state']['variable']);
        $this->assertSame(0, $contract['state']['initial_value']);
        $this->assertSame(999, $contract['state']['maximum']);
        $this->assertFalse($contract['counting_rules']['initial_automatic_request_counts']);
        $this->assertFalse($contract['counting_rules']['ignored_concurrent_manual_refresh_counts']);

        foreach ([
            'accepted_manual_refresh_counts',
            'successful_manual_refresh_counts',
            'unhealthy_manual_refresh_counts',
            'http_error_manual_refresh_counts',
            'network_failure_manual_refresh_counts',
            'json_parse_failure_manual_refresh_counts',
            'payload_validation_failure_manual_refresh_counts',
            'updates_once_per_accepted_manual_attempt',
        ] as $key) {
            $this->assertTrue($contract['counting_rules'][$key], $key);
        }
    }

    public function test_trigger_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['manual_refresh_attempt_counter_contract'];

        $this->assertTrue($contract['trigger_contract']['request_function_accepts_manual_flag']);
        $this->assertFalse($contract['trigger_contract']['manual_flag_default']);
        $this->assertTrue($contract['trigger_contract']['button_listener_passes_manual_true']);
        $this->assertTrue($contract['trigger_contract']['initial_request_passes_manual_false']);

        foreach ($contract['compatibility'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        $this->assertSame(2, $contract['planned_implementation']['maximum_modified_files']);
        $this->assertSame('once before commit', $document['workflow']['full_suite_runs']);
        $this->assertFalse($document['workflow']['post_commit_full_suite']);
        $this->assertSame('Phase 118B', $document['next_recommendation']['phase']);
    }

    private function document(): array
    {
        $document = json_decode(file_get_contents(base_path(self::JSON_PATH)), true);

        $this->assertIsArray($document);

        return $document;
    }
}
