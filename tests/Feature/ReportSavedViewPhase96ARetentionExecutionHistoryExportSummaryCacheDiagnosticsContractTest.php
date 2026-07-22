<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase96ARetentionExecutionHistoryExportSummaryCacheDiagnosticsContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-96a-retention-execution-history-export-summary-cache-diagnostics-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-96a-retention-execution-history-export-summary-cache-diagnostics-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 96A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            'a395b4ece09c3bc89a2adedb053bf398c56a1677',
            $document['baseline']['commit']
        );
        $this->assertSame(1942, $document['baseline']['tests']);
        $this->assertSame(17747, $document['baseline']['assertions']);
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
        ] as $key) {
            $this->assertFalse($scope[$key], $key);
        }

        $this->assertTrue($scope['documentation_and_tests_only']);
    }

    public function test_return_shape_behavior_and_privacy_are_locked(): void
    {
        $contract = $this->document()['diagnostics_contract'];

        $this->assertSame(
            'summaryCacheDiagnostics',
            $contract['planned_method']
        );

        $this->assertSame([
            'cache_key_prefix',
            'summary_ttl_seconds',
            'generation_key_prefix',
            'generation_ttl_seconds',
            'generation_present',
            'generation_source',
            'cache_store',
            'cache_read_available',
            'observability_enabled',
        ], array_keys($contract['return_shape']));

        foreach ($contract['behavior'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($contract['privacy'] as $key => $value) {
            $this->assertTrue($value, $key);
        }
    }

    public function test_access_performance_and_scope_are_locked(): void
    {
        $contract = $this->document()['diagnostics_contract'];

        $this->assertTrue($contract['access']['public_service_method']);

        foreach ([
            'controller_endpoint_required',
            'route_required',
            'view_required',
            'authorization_change_required',
        ] as $key) {
            $this->assertFalse($contract['access'][$key], $key);
        }

        $this->assertSame(
            1,
            $contract['performance']['maximum_cache_reads']
        );
        $this->assertSame(
            0,
            $contract['performance']['maximum_database_queries']
        );
        $this->assertSame(
            0,
            $contract['performance']['maximum_model_hydration']
        );
        $this->assertTrue(
            $contract['performance']['constant_result_size']
        );

        foreach ([
            'history_service_changes_expected',
            'controller_changes_expected',
            'view_changes_expected',
            'route_changes_expected',
            'database_changes_expected',
            'migration_changes_expected',
        ] as $key) {
            $this->assertFalse(
                $contract['planned_implementation'][$key],
                $key
            );
        }
    }

    public function test_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['diagnostics_contract'];

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
            'Phase 96B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-96a-retention-execution-history-export-summary-cache-diagnostics-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
