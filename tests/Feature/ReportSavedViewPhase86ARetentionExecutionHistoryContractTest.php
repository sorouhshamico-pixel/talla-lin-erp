<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase86ARetentionExecutionHistoryContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-86a-saved-view-sharing-activity-retention-execution-history-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-86a-saved-view-sharing-activity-retention-execution-history-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame(
            'Phase 86A',
            $document['phase']
        );
        $this->assertSame(
            'contract',
            $document['type']
        );
        $this->assertSame(
            '9a3e59e',
            $document['baseline']['commit']
        );
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
            $this->assertFalse(
                $scope[$key],
                $key
            );
        }

        $this->assertTrue(
            $scope['documentation_and_tests_only']
        );
    }

    public function test_table_model_types_and_statuses_are_locked(): void
    {
        $contract = $this->document()
            ['execution_history_contract'];

        $this->assertSame(
            'report_saved_view_share_activity_retention_executions',
            $contract['table']
        );
        $this->assertSame(
            'App\\Models\\ReportSavedViewShareActivityRetentionExecution',
            $contract['model']
        );
        $this->assertSame(
            [
                'manual_preview',
                'manual_execution',
                'scheduled_execution',
                'command_execution',
            ],
            $contract['record_types']
        );
        $this->assertSame(
            [
                'succeeded',
                'failed',
                'conflicted',
            ],
            $contract['statuses']
        );
    }

    public function test_columns_constraints_and_immutability_are_locked(): void
    {
        $contract = $this->document()
            ['execution_history_contract'];

        foreach ([
            'id',
            'type',
            'status',
            'actor_user_id',
            'requested_days',
            'requested_chunk_size',
            'candidate_count',
            'deleted_count',
            'cutoff_at',
            'duration_ms',
            'failure_class',
            'failure_message',
            'context',
            'started_at',
            'finished_at',
            'created_at',
            'updated_at',
        ] as $column) {
            $this->assertArrayHasKey(
                $column,
                $contract['columns']
            );
        }

        $this->assertSame(
            30,
            $contract['constraints']
                ['requested_days_minimum']
        );
        $this->assertSame(
            3650,
            $contract['constraints']
                ['requested_days_maximum']
        );
        $this->assertSame(
            2000,
            $contract['constraints']
                ['failure_message_maximum_characters']
        );

        $this->assertTrue(
            $contract['immutability']['rows_append_only']
        );
        $this->assertTrue(
            $contract['immutability']
                ['updates_after_completion_forbidden']
        );
        $this->assertTrue(
            $contract['immutability']
                ['deletes_through_normal_model_operations_forbidden']
        );
    }

    public function test_recording_failure_handling_and_read_interface_are_locked(): void
    {
        $contract = $this->document()
            ['execution_history_contract'];

        foreach (
            $contract['recording']
            as $key => $value
        ) {
            if ($key === 'activity_rows_created') {
                $this->assertFalse($value);
                continue;
            }

            $this->assertTrue(
                $value,
                $key
            );
        }

        $this->assertTrue(
            $contract['failure_handling']
                ['history_write_failure_must_not_hide_primary_failure']
        );
        $this->assertTrue(
            $contract['failure_handling']
                ['history_write_failure_logged']
        );
        $this->assertTrue(
            $contract['failure_handling']
                ['primary_operation_result_remains_authoritative']
        );

        $this->assertSame(
            'manage_saved_view_share_activity_retention',
            $contract['read_interface']
                ['permission_required']
        );
        $this->assertSame(
            25,
            $contract['read_interface']
                ['default_page_size']
        );
        $this->assertSame(
            100,
            $contract['read_interface']
                ['maximum_page_size']
        );
    }

    public function test_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['execution_history_contract'];

        foreach (
            $contract['compatibility']
            as $key => $value
        ) {
            $this->assertTrue(
                $value,
                $key
            );
        }

        $this->assertFalse(
            $contract['export']['included_in_phase_86']
        );
        $this->assertTrue(
            $contract['export']['future_phase_possible']
        );

        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse(
            $document['workflow']['post_commit_full_suite']
        );
        $this->assertTrue(
            $document['workflow']
                ['successful_phase_pushed_immediately']
        );
        $this->assertSame(
            'Phase 86B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-86a-saved-view-sharing-activity-retention-execution-history-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
