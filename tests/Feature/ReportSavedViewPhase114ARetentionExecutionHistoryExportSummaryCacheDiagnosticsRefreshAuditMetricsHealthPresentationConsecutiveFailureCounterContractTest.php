<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase114ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationConsecutiveFailureCounterContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-114a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-consecutive-failure-counter-contract.json';

    public function test_contract_documents_exist_and_baseline_is_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-114a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-consecutive-failure-counter-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 114A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '37d842d4d503739301ef7d1af3e9bb4220214fc3',
            $document['baseline']['commit']
        );
        $this->assertSame(2238, $document['baseline']['tests']);
        $this->assertSame(22108, $document['baseline']['assertions']);
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

    public function test_counter_element_state_classification_and_updates_are_locked(): void
    {
        $contract = $this->document()['consecutive_failure_counter_contract'];

        $this->assertSame(
            'retention-audit-metrics-health-consecutive-failures',
            $contract['counter_element']['id']
        );
        $this->assertSame(
            'Consecutive failures:',
            $contract['counter_element']['prefix']
        );
        $this->assertSame(
            '0',
            $contract['counter_element']['initial_text']
        );
        $this->assertSame('off', $contract['counter_element']['aria_live']);

        $this->assertSame(
            'consecutiveFailures',
            $contract['state']['variable']
        );
        $this->assertSame(0, $contract['state']['initial_value']);
        $this->assertSame(999, $contract['state']['maximum']);
        $this->assertTrue($contract['state']['integer_required']);
        $this->assertTrue($contract['state']['client_memory_only']);

        $this->assertSame(
            'success',
            $contract['classification']['validated_healthy_response']
        );
        $this->assertSame(
            'success',
            $contract['classification']['validated_unhealthy_response']
        );

        foreach ([
            'http_error_response',
            'network_failure',
            'json_parse_failure',
            'payload_validation_failure',
        ] as $key) {
            $this->assertSame(
                'failure',
                $contract['classification'][$key],
                $key
            );
        }

        $this->assertFalse(
            $contract['update_rules']['request_start_changes_counter']
        );
        $this->assertFalse(
            $contract['update_rules']['ignored_concurrent_request_changes_counter']
        );

        foreach ([
            'success_resets_to_zero',
            'failure_increments_by_one',
            'counter_clamped_to_maximum',
            'updates_once_per_executed_request',
            'http_error_counted_once',
            'network_failure_counted_once',
            'parse_failure_counted_once',
            'validation_failure_counted_once',
        ] as $key) {
            $this->assertTrue($contract['update_rules'][$key], $key);
        }
    }

    public function test_accessibility_privacy_compatibility_scope_and_workflow_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['consecutive_failure_counter_contract'];

        foreach ($contract['accessibility'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($contract['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

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
            'Phase 114B',
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
