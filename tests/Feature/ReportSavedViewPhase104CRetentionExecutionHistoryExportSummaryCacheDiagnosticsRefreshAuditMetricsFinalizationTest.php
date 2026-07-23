<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase104CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-104c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-104c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 104C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            'f8e3301502b1f14e84f92afa89183ff65bfa3da7',
            $document['baseline']['commit']
        );
        $this->assertSame(2083, $document['baseline']['tests']);
        $this->assertSame(19623, $document['baseline']['assertions']);
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
        ] as $key) {
            $this->assertFalse($scope[$key], $key);
        }

        $this->assertTrue($scope['documentation_and_tests_only']);
    }

    public function test_transport_payload_rules_and_dimensions_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'laravel_domain_event',
            $locked['transport']['mechanism']
        );
        $this->assertSame(
            'App\\Events\\'
            . 'SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded',
            $locked['transport']['event_class']
        );
        $this->assertSame(
            1,
            $locked['transport']['dispatch_count_per_request']
        );

        foreach ([
            'listeners_required',
            'queue_required',
            'database_persistence',
            'cache_persistence',
        ] as $key) {
            $this->assertFalse($locked['transport'][$key], $key);
        }

        $this->assertSame(
            [
                'outcome',
                'auditAttempted',
                'auditSucceeded',
                'rateLimitName',
                'routeName',
                'requestMethod',
            ],
            $locked['event_payload']['properties']
        );
        $this->assertTrue(
            $locked['event_payload']['readonly_properties']
        );

        foreach ($locked['metric_rules'] as $key => $value) {
            if ($key === 'allowed_unsampled_audit_attempted'
                || $key === 'allowed_unsampled_audit_succeeded') {
                $this->assertFalse($value, $key);

                continue;
            }

            $this->assertTrue($value, $key);
        }

        $this->assertSame(
            'saved-view-retention-summary-cache-diagnostics-refresh',
            $locked['locked_dimensions']['rate_limit_name']
        );
        $this->assertSame(
            'reports.saved-view-share-activity-retention.'
            . 'summary-cache-diagnostics',
            $locked['locked_dimensions']['route_name']
        );
        $this->assertSame(
            'GET',
            $locked['locked_dimensions']['request_method']
        );
    }

    public function test_compatibility_migration_privacy_and_performance_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $migration = $locked['compatibility_migration'];
        $this->assertTrue($migration['phase_103b_test_modified']);
        $this->assertSame('source_guard_only', $migration['update_type']);

        foreach ([
            'sampling_expectations_changed',
            'log_expectations_changed',
            'fixture_expectations_changed',
            'phase_101b_test_modified',
            'phase_102b_test_modified',
            'historical_runtime_contract_relaxed',
            'production_test_environment_exception_added',
        ] as $key) {
            $this->assertFalse($migration[$key], $key);
        }

        foreach ($locked['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        foreach ([
            'additional_log_info_calls',
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
            $locked['performance']['domain_events_dispatched_per_request']
        );
    }

    public function test_scope_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

        foreach ([
            'middleware_modified',
            'event_added',
            'phase_103b_test_modified',
            'phase_104b_test_added',
        ] as $key) {
            $this->assertTrue(
                $locked['implementation_scope'][$key],
                $key
            );
        }

        foreach ([
            'phase_101b_test_modified',
            'phase_102b_test_modified',
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
            'Phase 105A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-104c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
