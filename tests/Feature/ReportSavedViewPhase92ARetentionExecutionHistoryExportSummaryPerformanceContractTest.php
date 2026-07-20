<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase92ARetentionExecutionHistoryExportSummaryPerformanceContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-92a-retention-execution-history-export-summary-performance-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-92a-retention-execution-history-export-summary-performance-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 92A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            'fec63654486ed17d5163aa42ab7d9bd02c2bd9ec',
            $document['baseline']['commit']
        );
        $this->assertSame(1877, $document['baseline']['tests']);
        $this->assertSame(17079, $document['baseline']['assertions']);
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

    public function test_query_budget_and_aggregation_are_locked(): void
    {
        $contract = $this->document()['performance_contract'];

        $this->assertSame(
            1,
            $contract['query_budget']['maximum_summary_queries']
        );
        $this->assertSame(
            0,
            $contract['query_budget']['execution_rows_loaded']
        );
        $this->assertSame(
            0,
            $contract['query_budget']['n_plus_one_queries']
        );

        foreach ($contract['aggregation'] as $key => $value) {
            $this->assertTrue($value, $key);
        }
    }

    public function test_filtering_database_controller_and_limits_are_locked(): void
    {
        $contract = $this->document()['performance_contract'];

        foreach ($contract['filtering'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertFalse(
            $contract['database']['new_table_required']
        );
        $this->assertFalse(
            $contract['database']['new_column_required']
        );
        $this->assertFalse(
            $contract['database']['new_migration_required']
        );
        $this->assertFalse(
            $contract['database']['index_changes_required']
        );
        $this->assertTrue(
            $contract['database']['existing_indexes_must_be_reused']
        );

        foreach (
            $contract['controller_and_view']
            as $key => $value
        ) {
            $this->assertTrue($value, $key);
        }

        $this->assertSame(
            30,
            $contract['limits']['summary_timeout_seconds']
        );
        $this->assertTrue(
            $contract['limits']['summary_result_size_is_constant']
        );
    }

    public function test_observability_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['performance_contract'];

        foreach ($contract['observability'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($contract['compatibility'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertTrue(
            $contract['planned_implementation']
                ['modified_service_allowed']
        );
        $this->assertFalse(
            $contract['planned_implementation']
                ['modified_controller_allowed']
        );
        $this->assertFalse(
            $contract['planned_implementation']
                ['modified_view_allowed']
        );
        $this->assertFalse(
            $contract['planned_implementation']
                ['route_changes_expected']
        );
        $this->assertFalse(
            $contract['planned_implementation']
                ['database_changes_expected']
        );
        $this->assertFalse(
            $contract['planned_implementation']
                ['migration_changes_expected']
        );

        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse(
            $document['workflow']['post_commit_full_suite']
        );
        $this->assertSame(
            'Phase 92B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-92a-retention-execution-history-export-summary-performance-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
