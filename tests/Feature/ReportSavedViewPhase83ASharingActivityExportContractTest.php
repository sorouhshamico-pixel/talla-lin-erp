<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase83ASharingActivityExportContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-83a-saved-view-sharing-activity-export-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-83a-saved-view-sharing-activity-export-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame(
            'Phase 83A',
            $document['phase']
        );
        $this->assertSame(
            'contract',
            $document['type']
        );
        $this->assertSame(
            'f1fb3ac',
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
            'route_changes_expected',
            'controller_changes_expected',
            'service_changes_expected',
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

    public function test_owner_and_recipient_scopes_are_locked(): void
    {
        $audiences = $this->document()
            ['export_contract']['audiences'];

        $this->assertSame(
            'owner_user_id',
            $audiences['owner']['scope_column']
        );
        $this->assertTrue(
            $audiences['owner']['authenticated_user_only']
        );
        $this->assertFalse(
            $audiences['owner']['may_export_foreign_owner_activity']
        );

        $this->assertSame(
            'recipient_user_id',
            $audiences['recipient']['scope_column']
        );
        $this->assertTrue(
            $audiences['recipient']['authenticated_user_only']
        );
        $this->assertFalse(
            $audiences['recipient']['may_export_other_recipient_activity']
        );
    }

    public function test_csv_format_and_columns_are_locked(): void
    {
        $csv = $this->document()
            ['export_contract']['csv'];

        $this->assertSame(
            'UTF-8 with BOM',
            $csv['encoding']
        );
        $this->assertSame(
            ',',
            $csv['delimiter']
        );
        $this->assertSame(
            'LF',
            $csv['line_ending']
        );

        $this->assertSame(
            [
                'activity_id',
                'created_at',
                'action',
                'source_saved_view_id',
                'source_name',
                'source_report_key',
                'actor_user_id',
                'actor_name',
                'owner_user_id',
                'owner_name',
                'recipient_user_id',
                'recipient_name',
                'permission_before',
                'permission_after',
                'copied_saved_view_id',
            ],
            $csv['columns']
        );
    }

    public function test_metadata_retention_and_performance_are_locked(): void
    {
        $contract = $this->document()
            ['export_contract'];

        $this->assertFalse(
            $contract['csv']['metadata_policy']
                ['full_metadata_column_exported']
        );
        $this->assertTrue(
            $contract['csv']['metadata_policy']
                ['copied_saved_view_id_extracted']
        );
        $this->assertFalse(
            $contract['csv']['metadata_policy']
                ['filters_payload_exported']
        );

        $this->assertTrue(
            $contract['performance']
                ['streamed_response_required']
        );
        $this->assertTrue(
            $contract['performance']
                ['unbounded_collection_loading_forbidden']
        );
        $this->assertTrue(
            $contract['performance']
                ['chunked_or_cursor_iteration_required']
        );
    }

    public function test_compatibility_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $compatibility = $document
            ['export_contract']['compatibility'];

        foreach ([
            'activity_table_unchanged',
            'activity_actions_unchanged',
            'sharing_permissions_unchanged',
            'saved_view_csv_format_unchanged',
            'saved_view_format_version_unchanged',
        ] as $key) {
            $this->assertTrue(
                $compatibility[$key],
                $key
            );
        }

        $this->assertSame(
            'Phase 83B',
            $document['next_recommendation']['phase']
        );
        $this->assertSame(
            'single validated stage',
            $document['next_recommendation']['execution']
        );
    }

    public function test_workflow_policy_is_locked(): void
    {
        $workflow = $this->document()['workflow'];

        $this->assertSame(
            'main',
            $workflow['branch']
        );
        $this->assertSame(
            'origin/main',
            $workflow['push_target']
        );
        $this->assertSame(
            'once before commit',
            $workflow['full_suite_runs']
        );
        $this->assertFalse(
            $workflow['post_commit_full_suite']
        );
        $this->assertTrue(
            $workflow['successful_phase_pushed_immediately']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-83a-saved-view-sharing-activity-export-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
