<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase91ARetentionExecutionHistoryExportSummaryContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-91a-retention-execution-history-export-summary-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-91a-retention-execution-history-export-summary-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 91A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '02d8afc0d85e681720c09c6b9c0659a627ea9e6d',
            $document['baseline']['commit']
        );
        $this->assertSame(1862, $document['baseline']['tests']);
        $this->assertSame(16897, $document['baseline']['assertions']);
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

    public function test_filter_and_summary_fields_are_locked(): void
    {
        $contract = $this->document()['summary_contract'];

        $this->assertSame([
            'type',
            'status',
            'actor_user_id',
            'started_from',
            'started_to',
        ], $contract['filter_semantics']['filters']);

        $this->assertSame([
            'total_count',
            'succeeded_count',
            'failed_count',
            'conflicted_count',
            'manual_preview_count',
            'manual_execution_count',
            'scheduled_execution_count',
            'command_execution_count',
            'candidate_count_sum',
            'deleted_count_sum',
            'average_duration_ms',
            'oldest_started_at',
            'newest_started_at',
        ], $contract['summary_fields']);
    }

    public function test_aggregation_interface_and_performance_are_locked(): void
    {
        $contract = $this->document()['summary_contract'];

        foreach ($contract['aggregation_rules'] as $key => $value) {
            if (in_array($key, [
                'empty_result_average_duration_ms',
                'empty_result_oldest_started_at',
                'empty_result_newest_started_at',
            ], true)) {
                $this->assertNull($value, $key);
                continue;
            }

            if ($key === 'empty_result_total_count') {
                $this->assertSame(0, $value);
                continue;
            }

            $this->assertTrue($value, $key);
        }

        $this->assertFalse(
            $contract['interface']['new_route_required']
        );
        $this->assertFalse(
            $contract['interface']['new_controller_required']
        );
        $this->assertFalse(
            $contract['interface']['new_service_required']
        );
        $this->assertTrue(
            $contract['interface']['json_status_response_unchanged']
        );

        $this->assertTrue(
            $contract['performance']['summary_computed_server_side']
        );
        $this->assertTrue(
            $contract['performance']['summary_uses_aggregate_query']
        );
        $this->assertTrue(
            $contract['performance']['summary_does_not_load_execution_rows']
        );
    }

    public function test_privacy_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['summary_contract'];

        foreach ($contract['privacy'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($contract['compatibility'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

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
            'Phase 91B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-91a-retention-execution-history-export-summary-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
