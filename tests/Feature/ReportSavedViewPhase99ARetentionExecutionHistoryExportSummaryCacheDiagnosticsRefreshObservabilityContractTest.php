<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase99ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshObservabilityContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-99a-retention-execution-history-export-summary-cache-diagnostics-refresh-observability-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-99a-retention-execution-history-export-summary-cache-diagnostics-refresh-observability-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 99A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            'adbc975e94a6d6fe5e501245caf3236441f47c26',
            $document['baseline']['commit']
        );
        $this->assertSame(1989, $document['baseline']['tests']);
        $this->assertSame(18302, $document['baseline']['assertions']);
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
        ] as $key) {
            $this->assertFalse($scope[$key], $key);
        }

        $this->assertTrue($scope['documentation_and_tests_only']);
    }

    public function test_events_levels_and_context_are_locked(): void
    {
        $contract = $this->document()['observability_contract'];

        $this->assertSame(
            'saved_view_retention.summary_cache_diagnostics.refresh_succeeded',
            $contract['planned_events']['success']
        );
        $this->assertSame(
            'saved_view_retention.summary_cache_diagnostics.refresh_failed',
            $contract['planned_events']['failure']
        );
        $this->assertSame(
            'debug',
            $contract['planned_levels']['success']
        );
        $this->assertSame(
            'warning',
            $contract['planned_levels']['failure']
        );

        foreach ($contract['success_context'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($contract['failure_context'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($contract['forbidden_context'] as $key => $value) {
            $this->assertTrue($value, $key);
        }
    }

    public function test_failure_behavior_and_performance_are_locked(): void
    {
        $contract = $this->document()['observability_contract'];

        $this->assertTrue(
            $contract['failure_behavior']['service_exception_propagates']
        );
        $this->assertTrue(
            $contract['failure_behavior']
                ['existing_http_error_behavior_preserved']
        );
        $this->assertTrue(
            $contract['failure_behavior']['logging_failure_is_swallowed']
        );
        $this->assertFalse(
            $contract['failure_behavior']
                ['logging_failure_changes_response']
        );

        foreach ([
            'additional_cache_reads',
            'additional_database_queries',
            'additional_model_hydration',
            'summary_queries',
        ] as $key) {
            $this->assertSame(
                0,
                $contract['performance'][$key],
                $key
            );
        }

        $this->assertSame(
            1,
            $contract['performance']
                ['diagnostics_service_calls_per_request']
        );
    }

    public function test_scope_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['observability_contract'];

        foreach ([
            'service_changes_expected',
            'route_changes_expected',
            'view_changes_expected',
            'layout_changes_expected',
            'database_changes_expected',
            'migration_changes_expected',
            'model_changes_expected',
        ] as $key) {
            $this->assertFalse(
                $contract['planned_implementation'][$key],
                $key
            );
        }

        foreach ($contract['compatibility'] as $key => $value) {
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
            'Phase 99B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-99a-retention-execution-history-export-summary-cache-diagnostics-refresh-observability-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
