<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase82ASharingActivityContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-82a-saved-view-sharing-activity-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-82a-saved-view-sharing-activity-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame(
            'Phase 82A',
            $document['phase']
        );
        $this->assertSame(
            'contract',
            $document['type']
        );
        $this->assertSame(
            'Phase 81C',
            $document['baseline']['phase']
        );
        $this->assertSame(
            'b53de24',
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
            'csv_format_changes_expected',
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

    public function test_storage_and_immutability_contract_is_locked(): void
    {
        $storage = $this->document()
            ['proposed_storage'];

        $this->assertSame(
            'report_saved_view_share_activities',
            $storage['table']
        );
        $this->assertSame(
            'App\\Models\\ReportSavedViewShareActivity',
            $storage['model']
        );
        $this->assertSame(
            'App\\Services\\ReportSavedViewShareActivityService',
            $storage['service']
        );
        $this->assertTrue(
            $storage['immutability']
        );
        $this->assertTrue(
            $storage['timestamps']['created_at']
        );
        $this->assertFalse(
            $storage['timestamps']['updated_at']
        );

        $columns = collect(
            $storage['columns']
        )->keyBy('name');

        foreach ([
            'report_saved_view_share_id',
            'report_saved_view_id',
            'actor_user_id',
            'owner_user_id',
            'recipient_user_id',
        ] as $column) {
            $this->assertSame(
                'set null',
                $columns[$column]['on_delete']
            );
        }

        foreach ([
            'action',
            'permission_before',
            'permission_after',
            'source_name_snapshot',
            'source_report_key_snapshot',
            'metadata',
            'created_at',
        ] as $column) {
            $this->assertTrue(
                $columns->has($column),
                $column
            );
        }
    }

    public function test_activity_actions_and_idempotency_are_locked(): void
    {
        $actions = $this->document()['actions'];

        $this->assertSame(
            [
                'shared',
                'permission_updated',
                'revoked',
                'applied',
                'copied',
                'source_archived',
                'source_restored',
                'source_deleted',
            ],
            $actions['allowed']
        );

        $this->assertSame(
            'No new activity.',
            $actions['idempotency']
                ['repeat_share_without_change']
        );
        $this->assertSame(
            'Record permission_updated.',
            $actions['idempotency']
                ['repeat_share_with_permission_change']
        );
        $this->assertSame(
            'No activity.',
            $actions['idempotency']
                ['failed_or_unauthorized_action']
        );
    }

    public function test_transaction_and_authorization_boundaries_are_locked(): void
    {
        $writes = $this->document()
            ['write_boundaries'];
        $reads = $this->document()
            ['read_boundaries'];

        $this->assertTrue(
            $writes['transactional']
        );
        $this->assertTrue(
            $writes['write_after_success_only']
        );
        $this->assertTrue(
            $writes['service_owned_writes']
        );
        $this->assertFalse(
            $writes['direct_model_writes']
        );
        $this->assertFalse(
            $writes['recipient_source_mutation']
        );

        $this->assertSame(
            'created_at descending, then id descending',
            $reads['ordering']
        );
        $this->assertSame(
            25,
            $reads['pagination']['default']
        );
        $this->assertSame(
            100,
            $reads['pagination']['maximum']
        );
        $this->assertContains(
            'No access.',
            $reads['foreign_user']
        );
    }

    public function test_retention_privacy_and_integration_contracts_are_locked(): void
    {
        $retention = $this->document()
            ['retention_and_privacy'];
        $integration = $this->document()
            ['integration_boundaries'];

        $this->assertTrue(
            $retention['activity_rows_survive_share_delete']
        );
        $this->assertTrue(
            $retention['activity_rows_survive_source_delete']
        );
        $this->assertTrue(
            $retention['snapshots_preserve_minimum_context']
        );
        $this->assertFalse(
            $retention['filters_payload_snapshot_allowed']
        );
        $this->assertFalse(
            $retention['sensitive_request_payload_allowed']
        );

        $this->assertSame(
            ['view', 'use'],
            $integration['sharing_permissions']
        );

        foreach ([
            'archive_semantics_unchanged',
            'copy_semantics_unchanged',
            'tags_semantics_unchanged',
            'csv_import_export_unchanged',
            'format_version_unchanged',
            'existing_saved_views_private_by_default',
        ] as $key) {
            $this->assertTrue(
                $integration[$key],
                $key
            );
        }

        $this->assertFalse(
            $integration['existing_shares_backfilled']
        );
    }

    public function test_next_phase_is_classified_as_large_and_split(): void
    {
        $plan = $this->document()
            ['implementation_plan'];

        $this->assertSame(
            'Phase 82B',
            $plan['next_phase']
        );
        $this->assertSame(
            'large',
            $plan['classification']
        );
        $this->assertTrue(
            $plan['split_required']
        );
        $this->assertCount(
            5,
            $plan['recommended_stages']
        );
    }

    public function test_main_only_workflow_and_prior_contracts_remain(): void
    {
        $workflow = $this->document()
            ['workflow'];
        $agents = file_get_contents(
            base_path('AGENTS.md')
        );

        $this->assertSame(
            'main',
            $workflow['branch']
        );
        $this->assertFalse(
            $workflow['phase_branch_allowed']
        );
        $this->assertFalse(
            $workflow['codex_worktree_allowed']
        );
        $this->assertSame(
            'origin/main',
            $workflow['push_target']
        );

        foreach ([
            '## Main-only workflow',
            'Phase 81B — Saved View Sharing',
            'Phase 81C — Finalize Saved View Sharing',
        ] as $marker) {
            $this->assertStringContainsString(
                $marker,
                $agents
            );
        }
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-82a-saved-view-sharing-activity-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
