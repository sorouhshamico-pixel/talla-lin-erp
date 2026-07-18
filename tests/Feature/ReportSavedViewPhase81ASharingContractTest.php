<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase81ASharingContractTest
    extends TestCase
{
    public function test_phase_81a_selects_sharing_contract(): void
    {
        $document = $this->contract();

        $this->assertSame(
            'Phase 81A',
            $document['phase']
        );
        $this->assertSame(
            'contract',
            $document['type']
        );
        $this->assertSame(
            'saved_view_sharing',
            $document['selection_decision']
                ['selected_capability']
        );
        $this->assertSame(
            'Phase 80C',
            $document['baseline']['phase']
        );
        $this->assertSame(
            '7a75448',
            $document['baseline']['commit']
        );
        $this->assertFalse(
            $document['scope']
                ['runtime_changes_expected']
        );
        $this->assertFalse(
            $document['scope']
                ['database_changes_expected']
        );
        $this->assertSame(
            'Phase 81B',
            $document['scope']
                ['implementation_phase']
        );
        $this->assertSame(
            'Phase 81C',
            $document['scope']
                ['finalization_phase']
        );
    }

    public function test_contract_locks_schema_and_permissions(): void
    {
        $document = $this->contract();

        $table = $document[
            'future_database_contract'
        ]['table'];

        $this->assertSame(
            'report_saved_view_shares',
            $table['name']
        );
        $this->assertSame(
            [
                'report_saved_view_id',
                'recipient_user_id',
            ],
            $table['unique']
        );
        $this->assertSame(
            ['view', 'use'],
            $document[
                'future_database_contract'
            ]['permission_values']
        );

        $this->assertContains(
            'recipient cannot mutate source',
            $document[
                'required_phase_81b_tests'
            ]
        );
    }

    public function test_contract_locks_recipient_boundaries(): void
    {
        $document = $this->contract();

        $ownership = $document[
            'future_ownership_contract'
        ];

        $this->assertSame(
            'rejected',
            $ownership['self_sharing']
        );
        $this->assertContains(
            'edit source saved view',
            $ownership[
                'recipient_forbidden_actions'
            ]
        );
        $this->assertContains(
            'share source with another user',
            $ownership[
                'recipient_forbidden_actions'
            ]
        );

        $copy = $document[
            'future_service_contract'
        ]['methods']['copyToRecipient']['rules'];

        $this->assertContains(
            'creates independent active non-default saved view',
            $copy
        );
        $this->assertContains(
            'does not copy owner tags',
            $copy
        );
        $this->assertContains(
            'does not copy further shares',
            $copy
        );
    }

    public function test_contract_locks_archive_tags_and_csv(): void
    {
        $document = $this->contract();

        $archive = $document[
            'archive_interaction_contract'
        ];

        $this->assertTrue(
            $archive[
                'share_records_preserved_when_archived'
            ]
        );
        $this->assertTrue(
            $archive[
                'archived_source_hidden_from_recipient'
            ]
        );
        $this->assertTrue(
            $archive[
                'restore_reactivates_existing_shares'
            ]
        );

        $tags = $document[
            'tag_interaction_contract'
        ];

        $this->assertTrue(
            $tags['owner_tags_remain_private']
        );
        $this->assertTrue(
            $tags['copy_does_not_copy_owner_tags']
        );

        $csv = $document[
            'csv_and_import_contract'
        ];

        $this->assertFalse(
            $csv['schema_change']
        );
        $this->assertFalse(
            $csv['format_version_change']
        );
        $this->assertFalse(
            $csv['shares_exported']
        );
        $this->assertFalse(
            $csv['writer_changes_expected']
        );
        $this->assertFalse(
            $csv['parser_changes_expected']
        );
    }

    public function test_large_phase_split_policy_is_recorded(): void
    {
        $document = $this->contract();
        $plan = $document[
            'phase_81b_execution_plan'
        ];

        $this->assertTrue(
            $plan['large_phase']
        );
        $this->assertTrue(
            $plan['split_required']
        );
        $this->assertCount(
            5,
            $plan['stages']
        );

        $this->assertTrue(
            $document[
                'next_recommendation'
            ]['split_large_phase']
        );
    }

    public function test_main_only_and_historical_markers_remain(): void
    {
        $agents = file_get_contents(
            base_path('AGENTS.md')
        );

        foreach ([
            '## Main-only workflow',
            'Do not create or push a phase branch.',
            'Do not create a Codex worktree.',
            '### 9. Commit directly on main',
            '### 10. Push only main',
            'Phase 80A — Prepare Saved View Tags Contract',
            'Phase 80B — Implement Saved View Tags',
            'Phase 80C — Finalize Saved View Tags',
        ] as $marker) {
            $this->assertStringContainsString(
                $marker,
                $agents
            );
        }
    }

    private function contract(): array
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-81a-saved-view-sharing-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-81a-saved-view-sharing-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
