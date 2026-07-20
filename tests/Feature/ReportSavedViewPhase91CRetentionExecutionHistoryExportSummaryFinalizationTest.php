<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewPhase91CRetentionExecutionHistoryExportSummaryFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-91c-retention-execution-history-export-summary-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-91c-retention-execution-history-export-summary-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 91C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '1b3c615e2e0a60753d72e2be0029e6e49efc2eef',
            $document['baseline']['commit']
        );
        $this->assertSame(1872, $document['baseline']['tests']);
        $this->assertSame(16999, $document['baseline']['assertions']);
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

    public function test_summary_route_filters_and_fields_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $route = Route::getRoutes()->getByName(
            $locked['administration_route']
        );

        $this->assertNotNull($route);
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains(
            'can:manage_saved_view_share_activity_retention',
            $route->gatherMiddleware()
        );

        $this->assertSame([
            'type',
            'status',
            'actor_user_id',
            'started_from',
            'started_to',
        ], $locked['filters']);

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
        ], $locked['summary_fields']);
    }

    public function test_aggregation_empty_state_behavior_and_scope_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        foreach ($locked['aggregation'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertSame(0, $locked['empty_state']['total_count']);
        $this->assertSame(
            0,
            $locked['empty_state']['candidate_count_sum']
        );
        $this->assertSame(
            0,
            $locked['empty_state']['deleted_count_sum']
        );
        $this->assertNull(
            $locked['empty_state']['average_duration_ms']
        );
        $this->assertNull(
            $locked['empty_state']['oldest_started_at']
        );
        $this->assertNull(
            $locked['empty_state']['newest_started_at']
        );

        foreach ($locked['behavior'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertTrue(
            $locked['implementation_scope']
                ['existing_export_service_modified']
        );
        $this->assertTrue(
            $locked['implementation_scope']
                ['existing_administration_controller_modified']
        );
        $this->assertTrue(
            $locked['implementation_scope']
                ['existing_administration_view_modified']
        );
        $this->assertTrue(
            $locked['implementation_scope']['phase_91b_test_added']
        );

        foreach ([
            'new_service_added',
            'new_controller_added',
            'route_changed',
            'database_changed',
            'migration_changed',
            'model_changed',
        ] as $key) {
            $this->assertFalse(
                $locked['implementation_scope'][$key],
                $key
            );
        }
    }

    public function test_privacy_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

        foreach ($locked['privacy'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($locked['compatibility'] as $key => $value) {
            $this->assertTrue($value, $key);
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
            'Phase 92A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-91c-retention-execution-history-export-summary-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
