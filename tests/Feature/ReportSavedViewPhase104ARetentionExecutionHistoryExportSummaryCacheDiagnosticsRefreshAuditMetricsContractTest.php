<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase104ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-104a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-104a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 104A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '69573469febb096e9e14a579823437c7459f08ec',
            $document['baseline']['commit']
        );
        $this->assertSame(2070, $document['baseline']['tests']);
        $this->assertSame(19456, $document['baseline']['assertions']);
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

    public function test_transport_dimensions_and_rules_are_locked(): void
    {
        $contract = $this->document()['audit_metrics_contract'];

        $this->assertSame(
            'laravel_domain_event',
            $contract['transport']['mechanism']
        );
        $this->assertSame(
            'App\\Events\\'
            . 'SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded',
            $contract['transport']['event_class']
        );
        $this->assertSame(
            1,
            $contract['transport']['dispatch_count_per_request']
        );
        $this->assertFalse($contract['transport']['listeners_required']);
        $this->assertFalse($contract['transport']['queue_required']);
        $this->assertFalse($contract['transport']['database_persistence']);
        $this->assertFalse($contract['transport']['cache_persistence']);

        $this->assertSame(
            [
                'allowed_sampled',
                'allowed_unsampled',
                'limited',
            ],
            $contract['metric_dimensions']['outcome_values']
        );

        $this->assertTrue(
            $contract['metric_rules']['allowed_sampled']['audit_attempted']
        );
        $this->assertFalse(
            $contract['metric_rules']['allowed_unsampled']['audit_attempted']
        );
        $this->assertFalse(
            $contract['metric_rules']['allowed_unsampled']['audit_succeeded']
        );
        $this->assertTrue(
            $contract['metric_rules']['limited']['audit_attempted']
        );
        $this->assertTrue(
            $contract['metric_rules']['audit_failure_preserves_response']
        );
        $this->assertTrue(
            $contract['metric_rules']['event_dispatch_failure_preserves_response']
        );
    }

    public function test_privacy_compatibility_and_performance_are_locked(): void
    {
        $contract = $this->document()['audit_metrics_contract'];

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
            $contract['performance']['domain_events_dispatched_per_request']
        );
    }

    public function test_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $implementation =
            $document['audit_metrics_contract']['planned_implementation'];

        $this->assertSame(
            'app/Http/Middleware/'
            . 'AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh.php',
            $implementation['modified_middleware']
        );
        $this->assertSame(
            'app/Events/'
            . 'SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded.php',
            $implementation['new_event']
        );
        $this->assertSame(3, $implementation['maximum_modified_files']);

        foreach ([
            'modified_phase_101b_test',
            'modified_phase_102b_test',
            'modified_phase_103b_test',
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
            'Phase 104B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-104a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
