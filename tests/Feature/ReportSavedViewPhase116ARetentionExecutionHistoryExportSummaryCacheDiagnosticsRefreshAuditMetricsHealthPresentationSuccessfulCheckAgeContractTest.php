<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase116ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationSuccessfulCheckAgeContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-116a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-successful-check-age-contract.json';

    public function test_contract_documents_exist_and_baseline_is_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-116a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-successful-check-age-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 116A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '46a7bcb3a2e0acd18232d895863013803879db67',
            $document['baseline']['commit']
        );
        $this->assertSame(2273, $document['baseline']['tests']);
        $this->assertSame(22701, $document['baseline']['assertions']);
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

    public function test_element_state_source_formatting_and_updates_are_locked(): void
    {
        $contract = $this->document()['successful_check_age_contract'];

        $this->assertSame(
            'retention-audit-metrics-health-successful-check-age',
            $contract['element']['id']
        );
        $this->assertSame('span', $contract['element']['element']);
        $this->assertSame(
            'Successful check age:',
            $contract['element']['prefix']
        );
        $this->assertSame(
            'Not available',
            $contract['element']['initial_text']
        );
        $this->assertSame('off', $contract['element']['aria_live']);

        $this->assertSame(
            'lastSuccessfulCheckAt',
            $contract['state']['variable']
        );
        $this->assertNull($contract['state']['initial_value']);
        $this->assertSame('Date', $contract['state']['type_after_success']);
        $this->assertTrue($contract['state']['client_memory_only']);

        foreach ([
            'local_storage_used',
            'session_storage_used',
            'indexed_db_used',
            'cookie_used',
            'database_used',
            'cache_used',
        ] as $key) {
            $this->assertFalse($contract['state'][$key], $key);
        }

        $this->assertSame(
            'same client completion Date used by last successful check',
            $contract['source']['timestamp_source']
        );

        $this->assertSame(
            'Less than 1 minute',
            $contract['formatting']['under_one_minute_text']
        );
        $this->assertSame(59, $contract['formatting']['minutes_range_maximum']);
        $this->assertSame(1440, $contract['formatting']['days_range_minimum_minutes']);
        $this->assertSame('floor', $contract['formatting']['rounding']);
        $this->assertSame(999, $contract['formatting']['maximum_display_value']);
        $this->assertFalse(
            $contract['formatting']['relative_time_format_used']
        );

        $this->assertFalse(
            $contract['update_rules']['request_start_clears_previous_value']
        );
        $this->assertFalse(
            $contract['update_rules']['validated_unhealthy_response_updates_state']
        );
        $this->assertFalse(
            $contract['update_rules']['ignored_concurrent_request_updates_state']
        );
        $this->assertFalse(
            $contract['update_rules']['background_timer_updates_display']
        );

        foreach ([
            'validated_healthy_response_updates_state',
            'validated_healthy_response_updates_display',
            'updates_once_per_validated_healthy_request',
            'manual_refresh_recalculates_after_success_only',
        ] as $key) {
            $this->assertTrue($contract['update_rules'][$key], $key);
        }
    }

    public function test_accessibility_privacy_compatibility_scope_and_workflow_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['successful_check_age_contract'];

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
            'Phase 116B',
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
