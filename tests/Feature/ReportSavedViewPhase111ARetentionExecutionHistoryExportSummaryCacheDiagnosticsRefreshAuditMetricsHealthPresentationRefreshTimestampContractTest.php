<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase111ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationRefreshTimestampContractTest extends TestCase
{
    private const JSON_PATH =
        'docs/phase-111a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-refresh-timestamp-contract.json';

    public function test_contract_files_and_baseline(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/phase-111a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-refresh-timestamp-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 111A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            'cccdfe0d83ff442def6478eaf89e15717d22055e',
            $document['baseline']['commit']
        );
        $this->assertSame(2186, $document['baseline']['tests']);
        $this->assertSame(21315, $document['baseline']['assertions']);
    }

    public function test_scope_is_documentation_and_tests_only(): void
    {
        $scope = $this->document()['scope'];

        foreach ($scope as $key => $value) {
            $this->assertSame(
                $key === 'documentation_and_tests_only',
                $value,
                $key
            );
        }
    }

    public function test_timestamp_semantics_are_locked(): void
    {
        $contract = $this->document()['refresh_timestamp_contract'];

        $this->assertSame(
            'retention-audit-metrics-health-updated-at',
            $contract['timestamp_element']['id']
        );
        $this->assertSame('time', $contract['timestamp_element']['element']);
        $this->assertSame(
            'Not updated yet',
            $contract['timestamp_element']['initial_text']
        );
        $this->assertNull(
            $contract['timestamp_element']['initial_datetime_attribute']
        );
        $this->assertSame('off', $contract['timestamp_element']['aria_live']);

        $this->assertSame('client', $contract['source']['clock']);
        $this->assertSame('new Date()', $contract['source']['constructor']);
        $this->assertSame(
            'browser_local_timezone',
            $contract['source']['timezone']
        );

        $this->assertSame('Last checked:', $contract['display']['prefix']);
        $this->assertSame(
            'Intl.DateTimeFormat',
            $contract['display']['formatter']
        );
        $this->assertSame(
            'Date.prototype.toISOString',
            $contract['machine_readable']['generator']
        );
    }

    public function test_update_accessibility_privacy_and_compatibility_are_locked(): void
    {
        $contract = $this->document()['refresh_timestamp_contract'];

        $this->assertFalse(
            $contract['update_rules']['request_start_updates_timestamp']
        );
        $this->assertFalse(
            $contract['update_rules']['ignored_concurrent_request_updates_timestamp']
        );

        foreach ([
            'healthy_completion_updates_timestamp',
            'unhealthy_completion_updates_timestamp',
            'request_failure_updates_timestamp',
            'parse_failure_updates_timestamp',
            'validation_failure_updates_timestamp',
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
        $this->assertSame(
            'once before commit',
            $this->document()['workflow']['full_suite_runs']
        );
        $this->assertSame(
            'Phase 111B',
            $this->document()['next_recommendation']['phase']
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
