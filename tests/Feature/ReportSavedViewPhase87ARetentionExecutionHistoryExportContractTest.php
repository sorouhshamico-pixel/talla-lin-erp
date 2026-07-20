<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase87ARetentionExecutionHistoryExportContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-87a-retention-execution-history-export-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-87a-retention-execution-history-export-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame(
            'Phase 87A',
            $document['phase']
        );
        $this->assertSame(
            'contract',
            $document['type']
        );
        $this->assertSame(
            'edcbcab',
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

    public function test_formats_routes_filters_and_ordering_are_locked(): void
    {
        $contract = $this->document()['export_contract'];

        $this->assertSame(
            [
                'csv',
                'json',
            ],
            $contract['formats']
        );
        $this->assertSame(
            'csv',
            $contract['default_format']
        );
        $this->assertSame(
            'reports.saved-view-share-activity-retention.history.export.csv',
            $contract['route_names']['csv']
        );
        $this->assertSame(
            'reports.saved-view-share-activity-retention.history.export.json',
            $contract['route_names']['json']
        );
        $this->assertContains(
            'actor_user_id',
            $contract['filters']
        );
        $this->assertSame(
            [
                'created_at desc',
                'id desc',
            ],
            $contract['ordering']
        );
    }

    public function test_columns_privacy_and_limits_are_locked(): void
    {
        $contract = $this->document()['export_contract'];

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
            'started_at',
            'finished_at',
            'created_at',
        ] as $column) {
            $this->assertContains(
                $column,
                $contract['columns']
            );
        }

        $this->assertContains(
            'context',
            $contract['excluded_fields']
        );
        $this->assertTrue(
            $contract['privacy']['no_context_export']
        );
        $this->assertTrue(
            $contract['privacy']['no_credentials_or_secrets']
        );
        $this->assertSame(
            100000,
            $contract['limits']['csv_maximum_rows']
        );
        $this->assertSame(
            10000,
            $contract['limits']['json_maximum_rows']
        );
    }

    public function test_csv_json_and_audit_contract_are_locked(): void
    {
        $contract = $this->document()['export_contract'];

        $this->assertTrue(
            $contract['csv']['utf8_bom']
        );
        $this->assertTrue(
            $contract['csv']['streaming_required']
        );
        $this->assertSame(
            'CRLF',
            $contract['csv']['line_ending']
        );

        $this->assertSame(
            [
                'exported_at',
                'filters',
                'count',
                'items',
            ],
            $contract['json']['top_level_keys']
        );
        $this->assertSame(
            10000,
            $contract['json']['maximum_rows']
        );

        $this->assertTrue(
            $contract['audit']['export_request_logged']
        );
        $this->assertFalse(
            $contract['audit']['creates_execution_history_row']
        );
        $this->assertFalse(
            $contract['audit']['creates_sharing_activity_row']
        );
    }

    public function test_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['export_contract'];

        foreach (
            $contract['compatibility']
            as $key => $value
        ) {
            $this->assertTrue(
                $value,
                $key
            );
        }

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
            'Phase 87B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-87a-retention-execution-history-export-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
