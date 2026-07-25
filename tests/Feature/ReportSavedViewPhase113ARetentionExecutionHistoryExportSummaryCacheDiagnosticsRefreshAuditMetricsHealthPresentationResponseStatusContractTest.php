<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase113ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationResponseStatusContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-113a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-response-status-contract.json';

    public function test_contract_documents_exist_and_baseline_is_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-113a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-response-status-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 113A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '9cec7ec9468dd0b1b75ee286c1361d1c51124ea2',
            $document['baseline']['commit']
        );
        $this->assertSame(2220, $document['baseline']['tests']);
        $this->assertSame(21830, $document['baseline']['assertions']);
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

    public function test_status_element_source_and_display_are_locked(): void
    {
        $contract = $this->document()['response_status_contract'];

        $this->assertSame(
            'retention-audit-metrics-health-response-status',
            $contract['status_element']['id']
        );
        $this->assertSame(
            'span',
            $contract['status_element']['element']
        );
        $this->assertSame(
            'Last response:',
            $contract['status_element']['prefix']
        );
        $this->assertSame(
            'Not received yet',
            $contract['status_element']['initial_text']
        );
        $this->assertSame(
            'off',
            $contract['status_element']['aria_live']
        );

        $this->assertSame(
            'Response.status',
            $contract['source']['status_code']
        );
        $this->assertSame(
            'Response.statusText',
            $contract['source']['status_text']
        );
        $this->assertSame(
            'Response.ok',
            $contract['source']['response_ok']
        );

        $this->assertSame(
            'Network error',
            $contract['display']['network_failure']['text']
        );
        $this->assertSame(
            'Not received yet',
            $contract['display']['invalid_status']['text']
        );
        $this->assertSame(
            100,
            $contract['display']['status_code_minimum']
        );
        $this->assertSame(
            599,
            $contract['display']['status_code_maximum']
        );
        $this->assertTrue($contract['display']['trim_status_text']);
    }

    public function test_update_accessibility_privacy_and_compatibility_are_locked(): void
    {
        $contract = $this->document()['response_status_contract'];

        $this->assertFalse(
            $contract['update_rules']['request_start_clears_previous_status']
        );
        $this->assertFalse(
            $contract['update_rules']['ignored_concurrent_request_updates_status']
        );

        foreach ([
            'response_received_updates_before_json_parse',
            'http_success_updates_status',
            'http_error_updates_status',
            'network_failure_updates_status',
            'json_parse_failure_preserves_received_http_status',
            'payload_validation_failure_preserves_received_http_status',
            'updates_once_per_executed_request',
        ] as $key) {
            $this->assertTrue($contract['update_rules'][$key], $key);
        }

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
    }

    public function test_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();

        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse(
            $document['workflow']['post_commit_full_suite']
        );
        $this->assertSame(
            'Phase 113B',
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
