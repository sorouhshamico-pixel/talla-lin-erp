<?php

namespace Tests\Feature;

use App\Models\ReportSavedViewShareActivityRetentionExecution;
use App\Models\User;
use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ReportSavedViewPhase93BRetentionExecutionHistoryExportSummaryCachingImplementationTest
    extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_cache_miss_executes_one_query_and_hit_executes_zero_queries(): void
    {
        $this->execution([
            'status' => ReportSavedViewShareActivityRetentionExecution::STATUS_FAILED,
            'candidate_count' => 4,
        ]);

        $service = app(ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $first = $service->summary(['status' => 'failed']);
        $missQueries = DB::getQueryLog();

        DB::flushQueryLog();
        $second = $service->summary(['status' => 'failed']);
        $hitQueries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(1, $missQueries);
        $this->assertCount(0, $hitQueries);
        $this->assertSame($first, $second);
        $this->assertSame(1, $second['total_count']);
    }

    public function test_filter_order_does_not_change_cache_key(): void
    {
        $actor = User::factory()->create();

        $this->execution([
            'type' => ReportSavedViewShareActivityRetentionExecution::TYPE_COMMAND_EXECUTION,
            'status' => ReportSavedViewShareActivityRetentionExecution::STATUS_FAILED,
            'actor_user_id' => $actor->id,
        ]);

        $service = app(ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class);

        $service->summary([
            'status' => 'failed',
            'actor_user_id' => $actor->id,
            'type' => 'command_execution',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $summary = $service->summary([
            'type' => 'command_execution',
            'status' => 'failed',
            'actor_user_id' => $actor->id,
        ]);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(0, $queries);
        $this->assertSame(1, $summary['total_count']);
    }

    public function test_null_empty_and_missing_filters_share_cache_entry(): void
    {
        $this->execution([]);

        $service = app(ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class);

        $service->summary([
            'type' => null,
            'status' => '',
            'actor_user_id' => null,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $summary = $service->summary([]);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(0, $queries);
        $this->assertSame(1, $summary['total_count']);
    }

    public function test_cache_value_expires_after_locked_ttl(): void
    {
        $this->travelTo('2026-07-20 12:00:00');

        $service = app(ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class);

        $first = $service->summary([]);
        $this->assertSame(0, $first['total_count']);

        $this->execution([]);

        $cached = $service->summary([]);
        $this->assertSame(0, $cached['total_count']);

        $this->travel(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_TTL_SECONDS + 1
        )->seconds();

        $refreshed = $service->summary([]);
        $this->assertSame(1, $refreshed['total_count']);
    }

    public function test_cache_failure_falls_back_to_live_summary(): void
    {
        $this->execution(['candidate_count' => 9]);

        Cache::shouldReceive('remember')
            ->once()
            ->andThrow(new RuntimeException('cache unavailable'));

        $summary = app(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        )->summary([]);

        $this->assertSame(1, $summary['total_count']);
        $this->assertSame(9, $summary['candidate_count_sum']);
    }

    public function test_cache_constants_and_key_privacy_are_locked(): void
    {
        $this->assertSame(
            30,
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_TTL_SECONDS
        );
        $this->assertSame(
            'reports:saved-view-retention:execution-history-summary:v1',
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_KEY_PREFIX
        );

        $source = file_get_contents(
            app_path('Services/ReportSavedViewShareActivityRetentionExecutionHistoryExportService.php')
        );

        $this->assertIsString($source);
        $this->assertStringContainsString("hash('sha256', \$encoded)", $source);
        $this->assertStringContainsString('Cache::remember(', $source);
        $this->assertStringContainsString('catch (Throwable)', $source);
    }

    private function execution(array $overrides): ReportSavedViewShareActivityRetentionExecution
    {
        return ReportSavedViewShareActivityRetentionExecution::query()
            ->create(array_merge([
                'type' => ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_EXECUTION,
                'status' => ReportSavedViewShareActivityRetentionExecution::STATUS_SUCCEEDED,
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
