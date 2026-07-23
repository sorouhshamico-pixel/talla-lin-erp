<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase106ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-106a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-106a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 106A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '35232de783979a5f0b51020e7ae21d492d4ce0d1',
            $document['baseline']['commit']
        );
        $this->assertSame(2104, $document['baseline']['tests']);
        $this->assertSame(19982, $document['baseline']['assertions']);
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

    public function test_health_checks_and_failure_behavior_are_locked(): void
    {
        $contract = $this->document()['metrics_health_contract'];

        $this->assertSame(
            'App\\Support\\'
            . 'SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealth',
            $contract['health_service']['class']
        );
        $this->assertSame('status', $contract['health_service']['method']);
        $this->assertSame('array', $contract['health_service']['return_type']);
        $this->assertTrue(
            $contract['health_service']['side_effect_free']
        );
        $this->assertFalse($contract['health_service']['throws']);

        $this->assertSame(
            [
                'listener_discovered',
                'listener_count',
                'channel_configured',
                'channel_driver',
                'channel_level',
                'channel_retention_days',
                'channel_path_matches',
                'healthy',
            ],
            array_keys($contract['checks'])
        );

        foreach ($contract['failure_behavior'] as $key => $value) {
            if ($key === 'exception_details_exposed'
                || $key === 'throws_to_caller') {
                $this->assertFalse($value, $key);

                continue;
            }

            $this->assertTrue($value, $key);
        }
    }

    public function test_privacy_compatibility_and_performance_are_locked(): void
    {
        $contract = $this->document()['metrics_health_contract'];

        foreach ($contract['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        foreach ($contract['compatibility'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        foreach ($contract['performance'] as $key => $value) {
            $this->assertSame(0, $value, $key);
        }
    }

    public function test_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $implementation =
            $document['metrics_health_contract']['planned_implementation'];

        $this->assertSame(
            'app/Support/'
            . 'SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealth.php',
            $implementation['new_health_class']
        );
        $this->assertSame(2, $implementation['maximum_modified_files']);

        foreach ([
            'modified_listener',
            'modified_event',
            'modified_middleware',
            'modified_logging_configuration',
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
            'Phase 106B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-106a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
