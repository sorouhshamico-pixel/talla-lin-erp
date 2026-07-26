<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase120ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshFailureCounterContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-120a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-failure-counter-contract.json';

    public function test_contract_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-120a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-failure-counter-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 120A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            'a38eb02c18a1e53ba992b2d0c8f49edd42f8ebe0',
            $document['baseline']['commit']
        );
        $this->assertSame(2339, $document['baseline']['tests']);
        $this->assertSame(23754, $document['baseline']['assertions']);
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

    public function test_element_state_and_failure_classification_are_locked(): void
    {
        $contract = $this->document()['manual_refresh_failure_counter_contract'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-failures',
            $contract['element']['id']
        );
        $this->assertSame(
            'Manual refresh failures:',
            $contract['element']['prefix']
        );
        $this->assertSame('0', $contract['element']['initial_text']);

        $this->assertSame(
            'manualRefreshFailures',
            $contract['state']['variable']
        );
        $this->assertSame(0, $contract['state']['initial_value']);
        $this->assertSame(999, $contract['state']['maximum']);
        $this->assertTrue($contract['state']['client_memory_only']);
        $this->assertFalse($contract['state']['persistent_storage_used']);

        foreach ([
            'http_error_counts',
            'network_failure_counts',
            'json_parse_failure_counts',
            'payload_validation_failure_counts',
        ] as $key) {
            $this->assertTrue($contract['classification'][$key], $key);
        }

        foreach ([
            'validated_healthy_counts',
            'validated_unhealthy_counts',
            'initial_automatic_request_counts',
            'ignored_concurrent_manual_request_counts',
        ] as $key) {
            $this->assertFalse($contract['classification'][$key], $key);
        }
    }

    public function test_update_rules_legacy_contract_and_compatibility_are_locked(): void
    {
        $contract = $this->document()['manual_refresh_failure_counter_contract'];

        foreach ([
            'manual_request_flag_preserved_for_failure_classification',
            'increment_occurs_in_catch_for_manual_requests',
            'increment_once_per_failed_manual_request',
            'no_increment_in_finally',
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
        $contract = $document['manual_refresh_failure_counter_contract'];

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
            'Phase 120B',
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
