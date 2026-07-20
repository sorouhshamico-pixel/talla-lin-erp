<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase85ARetentionAdministrationContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-85a-saved-view-sharing-activity-retention-administration-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-85a-saved-view-sharing-activity-retention-administration-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame(
            'Phase 85A',
            $document['phase']
        );
        $this->assertSame(
            'contract',
            $document['type']
        );
        $this->assertSame(
            '2df38da',
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
            'policy_changes_expected',
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

    public function test_authorization_and_status_visibility_are_locked(): void
    {
        $contract = $this->document()
            ['administration_contract'];

        $this->assertTrue(
            $contract['authorization']
                ['authentication_required']
        );
        $this->assertSame(
            'manage_saved_view_share_activity_retention',
            $contract['authorization']
                ['permission_required']
        );
        $this->assertTrue(
            $contract['authorization']
                ['cross_user_operational_access_requires_permission']
        );

        $this->assertContains(
            'candidate_count',
            $contract['read_interface']['shows']
        );
        $this->assertContains(
            'last_manual_execution',
            $contract['read_interface']['shows']
        );
        $this->assertContains(
            'full_activity_metadata',
            $contract['read_interface']['does_not_show']
        );
        $this->assertContains(
            'environment_secrets',
            $contract['read_interface']['does_not_show']
        );
    }

    public function test_preview_execution_and_confirmation_are_locked(): void
    {
        $contract = $this->document()
            ['administration_contract'];

        $this->assertTrue(
            $contract['manual_preview']['supported']
        );
        $this->assertFalse(
            $contract['manual_preview']['deletes_rows']
        );
        $this->assertSame(
            30,
            $contract['manual_preview']['minimum_days']
        );
        $this->assertSame(
            3650,
            $contract['manual_preview']['maximum_days']
        );

        $this->assertTrue(
            $contract['manual_execution']['supported']
        );
        $this->assertTrue(
            $contract['manual_execution']['confirmation_required']
        );
        $this->assertSame(
            'PRUNE',
            $contract['manual_execution']['confirmation_token']
        );
        $this->assertSame(
            1,
            $contract['manual_execution']['minimum_chunk_size']
        );
        $this->assertSame(
            10000,
            $contract['manual_execution']['maximum_chunk_size']
        );
    }

    public function test_configuration_audit_and_concurrency_are_locked(): void
    {
        $contract = $this->document()
            ['administration_contract'];

        $this->assertTrue(
            $contract['configuration_visibility']['read_only']
        );
        $this->assertTrue(
            $contract['configuration_visibility']
                ['web_interface_must_not_write_env']
        );
        $this->assertTrue(
            $contract['configuration_visibility']
                ['web_interface_must_not_write_config_files']
        );

        $this->assertTrue(
            $contract['audit']['manual_preview_logged']
        );
        $this->assertTrue(
            $contract['audit']['manual_execution_logged']
        );
        $this->assertFalse(
            $contract['audit']['manual_operation_creates_activity_row']
        );

        $this->assertTrue(
            $contract['concurrency']
                ['single_manual_execution_lock_required']
        );
        $this->assertSame(
            'saved-view-share-activity-retention-prune',
            $contract['concurrency']['lock_name']
        );
        $this->assertTrue(
            $contract['concurrency']
                ['manual_and_scheduled_overlap_forbidden']
        );
    }

    public function test_responses_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['administration_contract'];

        $this->assertSame(
            200,
            $contract['responses']['success']
        );
        $this->assertSame(
            403,
            $contract['responses']['forbidden']
        );
        $this->assertSame(
            422,
            $contract['responses']['validation_error']
        );
        $this->assertSame(
            409,
            $contract['responses']['lock_conflict']
        );

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
            $document['workflow']['successful_phase_pushed_immediately']
        );
        $this->assertSame(
            'Phase 85B',
            $document['next_recommendation']['phase']
        );
        $this->assertSame(
            'single validated stage',
            $document['next_recommendation']['execution']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-85a-saved-view-sharing-activity-retention-administration-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
