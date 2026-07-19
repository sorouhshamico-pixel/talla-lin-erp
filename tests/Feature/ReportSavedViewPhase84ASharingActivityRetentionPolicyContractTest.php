<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase84ASharingActivityRetentionPolicyContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-84a-saved-view-sharing-activity-retention-policy-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-84a-saved-view-sharing-activity-retention-policy-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame(
            'Phase 84A',
            $document['phase']
        );
        $this->assertSame(
            'contract',
            $document['type']
        );
        $this->assertSame(
            'b7903cd',
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
            'command_changes_expected',
            'scheduler_changes_expected',
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

    public function test_default_policy_is_retain_forever(): void
    {
        $policy = $this->document()
            ['retention_contract']['default_policy'];

        $this->assertSame(
            'retain_forever',
            $policy['mode']
        );
        $this->assertFalse(
            $policy['automatic_pruning_enabled']
        );
        $this->assertNull(
            $policy['retention_days']
        );
    }

    public function test_optional_pruning_safety_is_locked(): void
    {
        $pruning = $this->document()
            ['retention_contract']['optional_pruning'];

        $this->assertTrue(
            $pruning['must_be_explicitly_configured']
        );
        $this->assertSame(
            30,
            $pruning['minimum_retention_days']
        );
        $this->assertSame(
            3650,
            $pruning['maximum_retention_days']
        );
        $this->assertTrue(
            $pruning['dry_run_supported']
        );
        $this->assertTrue(
            $pruning['chunked_deletion_required']
        );
        $this->assertTrue(
            $pruning['single_mass_delete_forbidden']
        );
    }

    public function test_scope_observability_and_immutability_are_locked(): void
    {
        $contract = $this->document()
            ['retention_contract'];

        $this->assertTrue(
            $contract['scope_and_safety']
                ['global_table_policy_only']
        );
        $this->assertTrue(
            $contract['scope_and_safety']
                ['user_supplied_owner_scope_forbidden']
        );
        $this->assertTrue(
            $contract['scope_and_safety']
                ['user_supplied_recipient_scope_forbidden']
        );
        $this->assertTrue(
            $contract['scope_and_safety']
                ['activity_action_filter_forbidden']
        );

        $this->assertTrue(
            $contract['audit_and_observability']
                ['dry_run_reports_candidate_count']
        );
        $this->assertFalse(
            $contract['audit_and_observability']
                ['pruning_creates_activity_rows']
        );

        $this->assertTrue(
            $contract['immutability_boundary']
                ['normal_model_delete_forbidden']
        );
        $this->assertTrue(
            $contract['immutability_boundary']
                ['retention_service_may_use_query_builder_delete']
        );
        $this->assertTrue(
            $contract['immutability_boundary']
                ['retention_delete_is_policy_exception']
        );
    }

    public function test_configuration_and_planned_interface_are_locked(): void
    {
        $contract = $this->document()
            ['retention_contract'];

        $this->assertFalse(
            $contract['configuration']['default_enabled']
        );
        $this->assertNull(
            $contract['configuration']['default_days']
        );
        $this->assertSame(
            500,
            $contract['configuration']['default_chunk_size']
        );
        $this->assertSame(
            'daily',
            $contract['configuration']['default_schedule']
        );

        $this->assertSame(
            'reports:prune-saved-view-share-activities',
            $contract['planned_interface']['command']
        );
        $this->assertContains(
            '--dry-run',
            $contract['planned_interface']['command_options']
        );
    }

    public function test_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();

        foreach (
            $document['retention_contract']['compatibility']
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
            'Phase 84B',
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
                    . 'phase-84a-saved-view-sharing-activity-retention-policy-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
