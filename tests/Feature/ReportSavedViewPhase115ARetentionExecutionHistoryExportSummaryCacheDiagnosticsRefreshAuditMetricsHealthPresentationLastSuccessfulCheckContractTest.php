<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase115ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationLastSuccessfulCheckContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-115a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-last-successful-check-contract.json';

    public function test_contract_documents_exist_and_baseline_is_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-115a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-last-successful-check-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 115A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            'bb2298be66d9816029aa58e56d3efac226b724fb',
            $document['baseline']['commit']
        );
        $this->assertSame(2256, $document['baseline']['tests']);
        $this->assertSame(22412, $document['baseline']['assertions']);
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

    public function test_element_success_definition_timestamp_and_updates_are_locked(): void
    {
        $contract = $this->document()['last_successful_check_contract'];

        $this->assertSame(
            'retention-audit-metrics-health-last-successful-check',
            $contract['element']['id']
        );
        $this->assertSame('time', $contract['element']['element']);
        $this->assertSame(
            'Last successful check:',
            $contract['element']['prefix']
        );
        $this->assertSame(
            'No successful check yet',
            $contract['element']['initial_text']
        );
        $this->assertNull($contract['element']['initial_datetime']);
        $this->assertSame('off', $contract['element']['aria_live']);

        $this->assertTrue(
            $contract['success_definition']['validated_healthy_response_updates']
        );

        foreach ([
            'validated_unhealthy_response_updates',
            'http_error_response_updates',
            'network_failure_updates',
            'json_parse_failure_updates',
            'payload_validation_failure_updates',
        ] as $key) {
            $this->assertFalse(
                $contract['success_definition'][$key],
                $key
            );
        }

        $this->assertSame(
            'client_completion_time',
            $contract['timestamp']['source']
        );
        $this->assertSame(
            'new Date()',
            $contract['timestamp']['clock']
        );
        $this->assertSame(
            'Date.toISOString()',
            $contract['timestamp']['datetime_attribute']
        );
        $this->assertSame(
            'existing Intl.DateTimeFormat',
            $contract['timestamp']['display_primary']
        );

        $this->assertFalse(
            $contract['update_rules']['request_start_clears_previous_value']
        );

        foreach ([
            'healthy_update_occurs_after_payload_validation',
            'healthy_update_occurs_after_fields_rendered',
            'healthy_update_occurs_before_request_finally',
            'unhealthy_preserves_previous_value',
            'failure_preserves_previous_value',
            'ignored_concurrent_request_preserves_previous_value',
            'updates_once_per_validated_healthy_request',
        ] as $key) {
            $this->assertTrue($contract['update_rules'][$key], $key);
        }
    }

    public function test_accessibility_privacy_compatibility_scope_and_workflow_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['last_successful_check_contract'];

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
            'Phase 115B',
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
