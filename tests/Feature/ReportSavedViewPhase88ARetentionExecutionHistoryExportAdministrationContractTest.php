<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase88ARetentionExecutionHistoryExportAdministrationContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-88a-retention-execution-history-export-administration-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-88a-retention-execution-history-export-administration-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 88A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '811fdf60db903ba783bf1869b9770ca606748b65',
            $document['baseline']['commit']
        );
        $this->assertSame(1818, $document['baseline']['tests']);
        $this->assertSame(16416, $document['baseline']['assertions']);
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

    public function test_existing_interfaces_and_authorization_are_locked(): void
    {
        $contract = $this->document()['administration_contract'];
        $reuse = $contract['existing_interface_reuse'];

        $this->assertSame(
            'App\\Http\\Controllers\\ReportSavedViewShareActivityRetentionAdminController',
            $reuse['controller']
        );
        $this->assertSame(
            'reports.saved-view-share-activity-retention.index',
            $reuse['status_route']
        );
        $this->assertSame(
            'reports.saved-view-share-activity-retention.history.export.csv',
            $reuse['csv_route']
        );
        $this->assertSame(
            'reports.saved-view-share-activity-retention.history.export.json',
            $reuse['json_route']
        );
        $this->assertFalse($reuse['new_controller_required']);
        $this->assertFalse($reuse['new_service_required']);
        $this->assertFalse($reuse['new_route_required']);

        $this->assertTrue(
            $contract['authorization']['authentication_required']
        );
        $this->assertSame(
            'manage_saved_view_share_activity_retention',
            $contract['authorization']['permission_required']
        );
    }

    public function test_controls_presentation_privacy_and_behavior_are_locked(): void
    {
        $contract = $this->document()['administration_contract'];

        $this->assertSame(
            ['csv', 'json'],
            $contract['controls']['formats']
        );
        $this->assertSame(
            [
                'type',
                'status',
                'actor_user_id',
                'started_from',
                'started_to',
            ],
            $contract['controls']['filters']
        );
        $this->assertTrue(
            $contract['controls']['filter_values_forwarded_as_query_string']
        );
        $this->assertTrue(
            $contract['controls']['server_side_validation_remains_authoritative']
        );
        $this->assertSame(
            100000,
            $contract['presentation']['shows_csv_maximum_rows']
        );
        $this->assertSame(
            10000,
            $contract['presentation']['shows_json_maximum_rows']
        );
        $this->assertTrue(
            $contract['privacy']['no_client_side_export_data_materialization']
        );
        $this->assertTrue(
            $contract['behavior']['export_downloads_are_get_requests']
        );
        $this->assertTrue(
            $contract['behavior']['export_request_does_not_mutate_history']
        );
    }

    public function test_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['administration_contract'];

        foreach ($contract['compatibility'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertFalse(
            $contract['planned_implementation']
                ['controller_changes_expected']
        );
        $this->assertFalse(
            $contract['planned_implementation']
                ['service_changes_expected']
        );
        $this->assertFalse(
            $contract['planned_implementation']
                ['route_changes_expected']
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
            'Phase 88B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-88a-retention-execution-history-export-administration-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
