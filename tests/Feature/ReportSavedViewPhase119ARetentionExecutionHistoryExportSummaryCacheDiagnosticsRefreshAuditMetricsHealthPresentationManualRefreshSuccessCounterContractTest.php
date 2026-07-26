<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase119ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshSuccessCounterContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-119a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-success-counter-contract.json';

    public function test_contract_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-119a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-success-counter-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 119A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '930dcf10a0de1545b69cda7d24b60d088137fbfb',
            $document['baseline']['commit']
        );
        $this->assertSame(2323, $document['baseline']['tests']);
        $this->assertSame(23535, $document['baseline']['assertions']);
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

    public function test_element_state_and_success_classification_are_locked(): void
    {
        $contract = $this->document()['manual_refresh_success_counter_contract'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-successes',
            $contract['element']['id']
        );
        $this->assertSame(
            'Manual refresh successes:',
            $contract['element']['prefix']
        );
        $this->assertSame('0', $contract['element']['initial_text']);

        $this->assertSame(
            'manualRefreshSuccesses',
            $contract['state']['variable']
        );
        $this->assertSame(0, $contract['state']['initial_value']);
        $this->assertSame(999, $contract['state']['maximum']);
        $this->assertTrue($contract['state']['client_memory_only']);
        $this->assertFalse($contract['state']['persistent_storage_used']);

        $this->assertTrue(
            $contract['classification']['validated_healthy_counts']
        );
        $this->assertTrue(
            $contract['classification']['validated_unhealthy_counts']
        );

        foreach ([
            'http_error_counts',
            'network_failure_counts',
            'json_parse_failure_counts',
            'payload_validation_failure_counts',
            'initial_automatic_request_counts',
            'ignored_concurrent_manual_request_counts',
        ] as $key) {
            $this->assertFalse($contract['classification'][$key], $key);
        }
    }

    public function test_update_rules_legacy_contract_and_compatibility_are_locked(): void
    {
        $contract = $this->document()['manual_refresh_success_counter_contract'];

        foreach ([
            'manual_request_flag_preserved_for_result_classification',
            'increment_occurs_after_payload_validation',
            'increment_occurs_before requestSucceeded assignment',
            'increment_once_per_validated_manual_request',
        ] as $key) {
            $this->assertTrue($contract['update_rules'][$key], $key);
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

        foreach ($contract['compatibility'] as $key => $value) {
            $this->assertFalse($value, $key);
        }
    }

    public function test_planned_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['manual_refresh_success_counter_contract'];

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
            'Phase 119B',
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
