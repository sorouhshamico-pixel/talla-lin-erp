<?php

namespace Tests\Feature;

use App\Models\ReportSavedViewShareActivityRetentionExecution;
use App\Models\User;
use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Mockery;
use Tests\TestCase;

class ReportSavedViewPhase92BRetentionExecutionHistoryExportSummaryPerformanceImplementationTest
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

    public function test_summary_uses_exactly_one_aggregate_query(): void
    {
        foreach (range(1, 25) as $index) {
            $this->execution([
                'status' => $index % 2 === 0
                    ? ReportSavedViewShareActivityRetentionExecution::STATUS_FAILED
                    : ReportSavedViewShareActivityRetentionExecution::STATUS_SUCCEEDED,
                'candidate_count' => $index,
                'deleted_count' => $index - 1,
                'duration_ms' => $index * 10,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $summary = app(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        )->summary([]);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_MAXIMUM_QUERIES,
            $queries
        );
        $this->assertSame(25, $summary['total_count']);

        $sql = strtolower($queries[0]['query']);

        foreach ([
            'count(',
            'sum(',
            'avg(',
            'min(',
            'max(',
        ] as $aggregate) {
            $this->assertStringContainsString($aggregate, $sql);
        }

        $this->assertStringContainsString(' limit 1', $sql);
        $this->assertStringNotContainsString(' offset ', $sql);
    }

    public function test_summary_query_count_is_constant_for_empty_and_filtered_results(): void
    {
        $actor = User::factory()->create();

        foreach (range(1, 10) as $index) {
            $this->execution([
                'actor_user_id' => $index <= 5
                    ? $actor->id
                    : null,
                'type' => $index % 2 === 0
                    ? ReportSavedViewShareActivityRetentionExecution::TYPE_COMMAND_EXECUTION
                    : ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_EXECUTION,
            ]);
        }

        $service = app(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        );

        foreach ([
            ['actor_user_id' => $actor->id],
            ['status' => 'conflicted'],
            [
                'type' => 'command_execution',
                'started_from' => '2026-07-01',
                'started_to' => '2026-07-31 23:59:59',
            ],
        ] as $filters) {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $service->summary($filters);

            $queries = DB::getQueryLog();
            DB::disableQueryLog();

            $this->assertCount(
                ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_MAXIMUM_QUERIES,
                $queries
            );
        }
    }

    public function test_summary_does_not_hydrate_execution_models(): void
    {
        foreach (range(1, 5) as $index) {
            $this->execution([
                'candidate_count' => $index,
            ]);
        }

        $retrieved = 0;

        ReportSavedViewShareActivityRetentionExecution::retrieved(
            function () use (&$retrieved): void {
                $retrieved++;
            }
        );

        app(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        )->summary([]);

        $this->assertSame(0, $retrieved);
    }

    public function test_json_status_request_does_not_compute_export_summary(): void
    {
        $user = User::factory()->create();

        $export = Mockery::mock(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        );

        $export->shouldNotReceive('validatedFilters');
        $export->shouldNotReceive('summary');

        $this->app->instance(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class,
            $export
        );

        $this
            ->actingAs($user)
            ->getJson(route(
                'reports.saved-view-share-activity-retention.index'
            ))
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
            ]);
    }

    public function test_performance_constants_and_source_guards_are_locked(): void
    {
        $this->assertSame(
            1,
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_MAXIMUM_QUERIES
        );
        $this->assertSame(
            30,
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_TIMEOUT_SECONDS
        );

        $source = file_get_contents(
            app_path(
                'Services/'
                . 'ReportSavedViewShareActivityRetentionExecutionHistoryExportService.php'
            )
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            '->selectRaw(',
            $source
        );
        $this->assertStringNotContainsString(
            '->get()->',
            $source
        );
        $this->assertStringNotContainsString(
            '->chunk(',
            $source
        );
        $this->assertStringNotContainsString(
            '->cursor(',
            $source
        );
        $this->assertStringNotContainsString(
            '->paginate(',
            $source
        );
    }

    private function execution(
        array $overrides
    ): ReportSavedViewShareActivityRetentionExecution {
        return ReportSavedViewShareActivityRetentionExecution::query()
            ->create(array_merge([
                'type' =>
                    ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_EXECUTION,
                'status' =>
                    ReportSavedViewShareActivityRetentionExecution::STATUS_SUCCEEDED,
                'actor_user_id' => null,
                'requested_days' => 30,
                'requested_chunk_size' => 500,
                'candidate_count' => 0,
                'deleted_count' => 0,
                'cutoff_at' => '2026-06-20 00:00:00',
                'duration_ms' => 10,
                'failure_class' => null,
                'failure_message' => null,
                'context' => null,
                'started_at' => '2026-07-20 08:00:00',
                'finished_at' => '2026-07-20 08:00:01',
                'created_at' => '2026-07-20 08:00:01',
            ], $overrides));
    }
}
