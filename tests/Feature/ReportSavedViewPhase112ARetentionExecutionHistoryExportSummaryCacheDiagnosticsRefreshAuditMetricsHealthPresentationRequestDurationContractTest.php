<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase112ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationRequestDurationContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-112a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-request-duration-contract.json';

    public function test_contract_documents_exist_and_baseline_is_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-112a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-request-duration-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 112A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '8078605483d11de44c02aafe300588fa13e79e5e',
            $document['baseline']['commit']
        );
        $this->assertSame(2202, $document['baseline']['tests']);
        $this->assertSame(21564, $document['baseline']['assertions']);
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

    public function test_duration_element_measurement_and_formatting_are_locked(): void
    {
        $contract = $this->document()['request_duration_contract'];

        $this->assertSame(
            'retention-audit-metrics-health-request-duration',
            $contract['duration_element']['id']
        );
        $this->assertSame(
            'span',
            $contract['duration_element']['element']
        );
        $this->assertSame(
            'Not measured yet',
            $contract['duration_element']['initial_text']
        );
        $this->assertSame(
            'Last request duration:',
            $contract['duration_element']['prefix']
        );

        $this->assertSame(
            'performance.now',
            $contract['measurement']['clock']
        );
        $this->assertSame(
            'immediately_before_fetch',
            $contract['measurement']['start_point']
        );
        $this->assertSame(
            'request_finally_block',
            $contract['measurement']['end_point']
        );
        $this->assertSame(
            'milliseconds',
            $contract['measurement']['unit']
        );
        $this->assertSame(0, $contract['measurement']['minimum']);
        $this->assertTrue(
            $contract['measurement']['negative_value_forbidden']
        );

        $this->assertSame(
            'Intl.NumberFormat',
            $contract['formatting']['formatter']
        );
        $this->assertSame(
            'Number.prototype.toFixed',
            $contract['formatting']['fallback']
        );
        $this->assertSame(
            'Not measured yet',
            $contract['formatting']['invalid_value_text']
        );
    }

    public function test_update_accessibility_privacy_and_compatibility_are_locked(): void
    {
        $contract = $this->document()['request_duration_contract'];

        $this->assertFalse(
            $contract['update_rules']['request_start_clears_previous_duration']
        );
        $this->assertFalse(
            $contract['update_rules']['ignored_concurrent_request_updates_duration']
        );

        foreach ([
            'validated_healthy_completion_updates_duration',
            'validated_unhealthy_completion_updates_duration',
            'request_failure_updates_duration',
            'parse_failure_updates_duration',
            'validation_failure_updates_duration',
            'updates_once_per_completed_request',
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
            'Phase 112B',
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
