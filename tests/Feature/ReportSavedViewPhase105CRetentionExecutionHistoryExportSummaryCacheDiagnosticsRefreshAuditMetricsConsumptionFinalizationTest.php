<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase105CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsConsumptionFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-105c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-consumption-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-105c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-consumption-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 105C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '1f0d0b779b7176aa6792bedfcbeaacfc573ef53a',
            $document['baseline']['commit']
        );
        $this->assertSame(2099, $document['baseline']['tests']);
        $this->assertSame(19875, $document['baseline']['assertions']);
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
            'logging_configuration_changes_expected',
        ] as $key) {
            $this->assertFalse($scope[$key], $key);
        }

        $this->assertTrue($scope['documentation_and_tests_only']);
    }

    public function test_listener_and_recording_contract_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'App\\Listeners\\'
            . 'RecordSavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetric',
            $locked['listener']['class']
        );
        $this->assertSame(
            'App\\Events\\'
            . 'SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded',
            $locked['listener']['event']
        );
        $this->assertSame(
            'event_discovery',
            $locked['listener']['registration']
        );
        $this->assertTrue($locked['listener']['synchronous']);
        $this->assertFalse($locked['listener']['queued']);
        $this->assertFalse($locked['listener']['after_commit']);
        $this->assertSame(1, $locked['listener']['listener_count']);

        $recording = $locked['recording'];
        $this->assertSame(
            'saved_view_retention_audit_metrics',
            $recording['channel_name']
        );
        $this->assertSame('daily', $recording['driver']);
        $this->assertSame('info', $recording['level']);
        $this->assertSame(14, $recording['retention_days']);
        $this->assertTrue($recording['replace_placeholders']);
        $this->assertSame(
            [
                'outcome',
                'audit_attempted',
                'audit_succeeded',
                'rate_limit_name',
                'route_name',
                'request_method',
            ],
            $recording['context_properties']
        );
        $this->assertSame(6, $recording['exact_context_property_count']);
        $this->assertSame(
            0,
            $recording['default_log_channel_writes_added']
        );
    }

    public function test_failure_privacy_and_performance_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        foreach ($locked['failure_behavior'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($locked['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        foreach ([
            'additional_database_queries',
            'additional_cache_reads',
            'additional_cache_writes',
            'additional_model_hydration',
            'additional_summary_queries',
        ] as $key) {
            $this->assertSame(0, $locked['performance'][$key], $key);
        }

        $this->assertSame(
            1,
            $locked['performance']['dedicated_metric_log_writes_per_event']
        );
    }

    public function test_scope_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

        foreach ([
            'listener_added',
            'logging_configuration_modified',
            'phase_105b_test_added',
        ] as $key) {
            $this->assertTrue(
                $locked['implementation_scope'][$key],
                $key
            );
        }

        foreach ([
            'middleware_modified',
            'event_modified',
            'phase_101b_test_modified',
            'phase_102b_test_modified',
            'phase_103b_test_modified',
            'phase_104b_test_modified',
            'bootstrap_changed',
            'route_changed',
            'controller_changed',
            'service_changed',
            'provider_changed',
            'view_changed',
            'layout_changed',
            'database_changed',
            'migration_changed',
            'model_changed',
        ] as $key) {
            $this->assertFalse(
                $locked['implementation_scope'][$key],
                $key
            );
        }

        foreach ($locked['compatibility'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse(
            $document['workflow']['post_commit_full_suite']
        );
        $this->assertSame(
            'Phase 106A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-105c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-consumption-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
