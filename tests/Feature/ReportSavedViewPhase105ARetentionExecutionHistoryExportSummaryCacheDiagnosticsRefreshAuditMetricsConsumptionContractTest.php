<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase105ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsConsumptionContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-105a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-consumption-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-105a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-consumption-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 105A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '075489627b35c98c90576740195863e193f7cbc2',
            $document['baseline']['commit']
        );
        $this->assertSame(2088, $document['baseline']['tests']);
        $this->assertSame(19736, $document['baseline']['assertions']);
    }

    public function test_phase_is_documentation_and_tests_only(): void
    {
        $scope = $this->document()['scope'];

        foreach ([
            'runtime_changes_expected',
            'database_changes_expected',
            'migration_changes_expected',
            'model_changes_expected',
            'service_changes_expected',
            'controller_changes_expected',
            'route_changes_expected',
            'view_changes_expected',
            'layout_changes_expected',
            'provider_changes_expected',
            'bootstrap_changes_expected',
            'middleware_changes_expected',
            'event_changes_expected',
            'listener_changes_expected',
        ] as $key) {
            $this->assertFalse($scope[$key], $key);
        }

        $this->assertTrue($scope['documentation_and_tests_only']);
    }

    public function test_listener_recording_and_failure_behavior_are_locked(): void
    {
        $contract = $this->document()['metrics_consumption_contract'];

        $this->assertSame(
            'App\\Listeners\\'
            . 'RecordSavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetric',
            $contract['listener']['class']
        );
        $this->assertSame(
            'App\\Events\\'
            . 'SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded',
            $contract['listener']['event']
        );
        $this->assertSame(
            'event_discovery',
            $contract['listener']['registration']
        );
        $this->assertTrue($contract['listener']['synchronous']);
        $this->assertFalse($contract['listener']['queued']);
        $this->assertFalse($contract['listener']['after_commit']);
        $this->assertSame(1, $contract['listener']['listener_count']);

        $this->assertSame(
            'dedicated_log_channel',
            $contract['recording']['mechanism']
        );
        $this->assertSame(
            'saved_view_retention_audit_metrics',
            $contract['recording']['channel_name']
        );
        $this->assertSame('info', $contract['recording']['log_level']);
        $this->assertSame(
            [
                'outcome',
                'audit_attempted',
                'audit_succeeded',
                'rate_limit_name',
                'route_name',
                'request_method',
            ],
            $contract['recording']['context_properties']
        );
        $this->assertSame(
            6,
            $contract['recording']['exact_context_property_count']
        );
        $this->assertFalse(
            $contract['recording']['additional_default_log_call']
        );

        foreach ($contract['failure_behavior'] as $key => $value) {
            $this->assertTrue($value, $key);
        }
    }

    public function test_privacy_compatibility_and_performance_are_locked(): void
    {
        $contract = $this->document()['metrics_consumption_contract'];

        foreach ($contract['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        foreach ($contract['compatibility'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        foreach ([
            'additional_database_queries',
            'additional_cache_reads',
            'additional_cache_writes',
            'additional_model_hydration',
            'additional_summary_queries',
        ] as $key) {
            $this->assertSame(
                0,
                $contract['performance'][$key],
                $key
            );
        }

        $this->assertSame(
            1,
            $contract['performance']['dedicated_metric_log_writes_per_event']
        );
    }

    public function test_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $implementation =
            $document['metrics_consumption_contract']['planned_implementation'];

        $this->assertSame(
            'app/Listeners/'
            . 'RecordSavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetric.php',
            $implementation['new_listener']
        );
        $this->assertSame(
            'config/logging.php',
            $implementation['logging_configuration']
        );
        $this->assertSame(3, $implementation['maximum_modified_files']);

        foreach ([
            'modified_middleware',
            'modified_event',
            'modified_bootstrap',
            'modified_route',
            'modified_controller',
            'modified_service',
            'modified_provider',
            'modified_view',
            'modified_layout',
            'database_changes_expected',
            'migration_changes_expected',
            'model_changes_expected',
        ] as $key) {
            $this->assertFalse($implementation[$key], $key);
        }

        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse(
            $document['workflow']['post_commit_full_suite']
        );
        $this->assertSame(
            'Phase 105B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-105a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-consumption-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
