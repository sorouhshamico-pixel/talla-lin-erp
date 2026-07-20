<?php

namespace Tests\Feature;

use App\Models\ReportSavedViewShareActivityRetentionExecution;
use App\Models\User;
use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ReportSavedViewPhase91BRetentionExecutionHistoryExportSummaryImplementationTest
    extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::define(
            'manage_saved_view_share_activity_retention',
            fn (User $user): bool => true
        );
    }

    public function test_service_returns_locked_filtered_summary(): void
    {
        $actor = User::factory()->create();

        $this->execution([
            'type' => ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_EXECUTION,
            'status' => ReportSavedViewShareActivityRetentionExecution::STATUS_SUCCEEDED,
            'actor_user_id' => $actor->id,
            'candidate_count' => 5,
            'deleted_count' => 4,
            'duration_ms' => 10,
            'started_at' => '2026-07-10 10:00:00',
        ]);

        $this->execution([
            'type' => ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_EXECUTION,
            'status' => ReportSavedViewShareActivityRetentionExecution::STATUS_FAILED,
            'actor_user_id' => $actor->id,
            'candidate_count' => null,
            'deleted_count' => null,
            'duration_ms' => 21,
            'started_at' => '2026-07-11 11:00:00',
        ]);

        $this->execution([
            'type' => ReportSavedViewShareActivityRetentionExecution::TYPE_SCHEDULED_EXECUTION,
            'status' => ReportSavedViewShareActivityRetentionExecution::STATUS_CONFLICTED,
            'actor_user_id' => null,
            'candidate_count' => 9,
            'deleted_count' => 0,
            'duration_ms' => null,
            'started_at' => '2026-07-12 12:00:00',
        ]);

        $service = app(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        );

        $summary = $service->summary([
            'actor_user_id' => $actor->id,
            'started_from' => '2026-07-01',
            'started_to' => '2026-07-31 23:59:59',
        ]);

        $this->assertSame(2, $summary['total_count']);
        $this->assertSame(1, $summary['succeeded_count']);
        $this->assertSame(1, $summary['failed_count']);
        $this->assertSame(0, $summary['conflicted_count']);
        $this->assertSame(0, $summary['manual_preview_count']);
        $this->assertSame(2, $summary['manual_execution_count']);
        $this->assertSame(0, $summary['scheduled_execution_count']);
        $this->assertSame(0, $summary['command_execution_count']);
        $this->assertSame(5, $summary['candidate_count_sum']);
        $this->assertSame(4, $summary['deleted_count_sum']);
        $this->assertSame(16, $summary['average_duration_ms']);
        $this->assertSame(
            '2026-07-10T10:00:00.000000Z',
            $summary['oldest_started_at']
        );
        $this->assertSame(
            '2026-07-11T11:00:00.000000Z',
            $summary['newest_started_at']
        );
    }

    public function test_service_returns_locked_empty_summary(): void
    {
        $summary = app(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        )->summary([]);

        $this->assertSame(0, $summary['total_count']);
        $this->assertSame(0, $summary['candidate_count_sum']);
        $this->assertSame(0, $summary['deleted_count_sum']);
        $this->assertNull($summary['average_duration_ms']);
        $this->assertNull($summary['oldest_started_at']);
        $this->assertNull($summary['newest_started_at']);
    }

    public function test_html_page_renders_filtered_summary_and_context(): void
    {
        $user = User::factory()->create();

        $this->execution([
            'type' => ReportSavedViewShareActivityRetentionExecution::TYPE_COMMAND_EXECUTION,
            'status' => ReportSavedViewShareActivityRetentionExecution::STATUS_FAILED,
            'actor_user_id' => $user->id,
            'candidate_count' => 7,
            'deleted_count' => 2,
            'duration_ms' => 30,
            'started_at' => '2026-07-20 09:00:00',
        ]);

        $this
            ->actingAs($user)
            ->get(route(
                'reports.saved-view-share-activity-retention.index',
                [
                    'status' => 'failed',
                    'actor_user_id' => $user->id,
                ]
            ))
            ->assertOk()
            ->assertSee('Current export summary')
            ->assertSee('Total executions')
            ->assertSee('Candidate count total')
            ->assertSee('Deleted count total')
            ->assertSee('Average duration (ms)')
            ->assertSee('status=failed')
            ->assertSee('actor=' . $user->id);
    }

    public function test_html_page_renders_empty_state(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route(
                'reports.saved-view-share-activity-retention.index',
                ['status' => 'conflicted']
            ))
            ->assertOk()
            ->assertSee(
                'No execution history matches the current filters.'
            );
    }

    public function test_existing_json_status_response_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson(route(
                'reports.saved-view-share-activity-retention.index',
                ['status' => 'invalid-status']
            ));

        $response
            ->assertOk()
            ->assertJsonStructure([
                'retention_enabled',
                'retention_days',
                'chunk_size',
                'schedule',
                'candidate_count',
                'oldest_activity_at',
                'newest_activity_at',
                'last_manual_preview',
                'last_manual_execution',
            ])
            ->assertJsonMissing([
                'total_count' => 0,
            ]);
    }

    private function execution(
        array $overrides
    ): ReportSavedViewShareActivityRetentionExecution {
        return ReportSavedViewShareActivityRetentionExecution::query()
            ->create(array_merge([
                'type' =>
                    ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_PREVIEW,
                'status' =>
                    ReportSavedViewShareActivityRetentionExecution::STATUS_SUCCEEDED,
                'actor_user_id' => null,
                'requested_days' => 30,
                'requested_chunk_size' => 500,
                'candidate_count' => 0,
                'deleted_count' => 0,
                'cutoff_at' => '2026-06-20 00:00:00',
                'duration_ms' => null,
                'failure_class' => null,
                'failure_message' => null,
                'context' => null,
                'started_at' => '2026-07-20 08:00:00',
                'finished_at' => '2026-07-20 08:00:01',
                'created_at' => '2026-07-20 08:00:01',
            ], $overrides));
    }
}
