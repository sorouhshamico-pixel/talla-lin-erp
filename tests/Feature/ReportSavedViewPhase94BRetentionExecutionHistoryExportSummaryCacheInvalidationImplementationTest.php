<?php

namespace Tests\Feature;

use App\Models\ReportSavedViewShareActivityRetentionExecution;
use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryExportService;
use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class ReportSavedViewPhase94BRetentionExecutionHistoryExportSummaryCacheInvalidationImplementationTest
    extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_successful_history_write_invalidates_cached_summary(): void
    {
        $export = app(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        );
        $history = app(
            ReportSavedViewShareActivityRetentionExecutionHistoryService::class
        );

        $this->assertSame(0, $export->summary([])['total_count']);

        $history->success(
            ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_EXECUTION,
            null,
            30,
            500,
            [
                'candidate_count' => 4,
                'deleted_count' => 3,
                'cutoff' => '2026-06-20 00:00:00',
                'duration_ms' => 12,
            ],
            '2026-07-20 08:00:00'
        );

        DB::flushQueryLog();
        DB::enableQueryLog();

        $summary = $export->summary([]);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(1, $queries);
        $this->assertSame(1, $summary['total_count']);
        $this->assertSame(4, $summary['candidate_count_sum']);
        $this->assertSame(3, $summary['deleted_count_sum']);
    }

    public function test_failure_and_conflict_writes_each_rotate_generation(): void
    {
        $history = app(
            ReportSavedViewShareActivityRetentionExecutionHistoryService::class
        );

        $history->failure(
            ReportSavedViewShareActivityRetentionExecution::TYPE_COMMAND_EXECUTION,
            null,
            30,
            null,
            new RuntimeException('expected failure'),
            '2026-07-20 09:00:00'
        );

        $failureGeneration = Cache::get(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_GENERATION_KEY
        );

        $this->assertIsString($failureGeneration);
        $this->assertNotSame('', $failureGeneration);

        $history->conflict(null, 30, 500);

        $conflictGeneration = Cache::get(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_GENERATION_KEY
        );

        $this->assertIsString($conflictGeneration);
        $this->assertNotSame(
            $failureGeneration,
            $conflictGeneration
        );

        $this->assertDatabaseCount(
            'report_saved_view_share_activity_retention_executions',
            2
        );
    }

    public function test_failed_history_create_does_not_rotate_generation(): void
    {
        Cache::put(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_GENERATION_KEY,
            'existing-generation',
            now()->addHour()
        );

        Schema::drop(
            'report_saved_view_share_activity_retention_executions'
        );

        app(
            ReportSavedViewShareActivityRetentionExecutionHistoryService::class
        )->success(
            ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_EXECUTION,
            null,
            30,
            500,
            [],
            now()
        );

        $this->assertSame(
            'existing-generation',
            Cache::get(
                ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_GENERATION_KEY
            )
        );
    }

    public function test_cache_invalidation_failure_does_not_fail_history_write(): void
    {
        Cache::shouldReceive('put')
            ->once()
            ->andThrow(new RuntimeException('cache unavailable'));

        app(
            ReportSavedViewShareActivityRetentionExecutionHistoryService::class
        )->success(
            ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_EXECUTION,
            null,
            30,
            500,
            ['candidate_count' => 1],
            now()
        );

        $this->assertDatabaseCount(
            'report_saved_view_share_activity_retention_executions',
            1
        );
    }

    public function test_missing_generation_uses_stable_default_and_cache_hit(): void
    {
        $export = app(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        );

        $first = $export->summary([]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $second = $export->summary([]);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertSame($first, $second);
        $this->assertCount(0, $queries);
        $this->assertSame(
            '0',
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_DEFAULT_GENERATION
        );
    }

    public function test_generation_constants_and_source_guards_are_locked(): void
    {
        $this->assertSame(
            'reports:saved-view-retention:execution-history-summary:generation:v1',
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_GENERATION_KEY
        );
        $this->assertSame(
            86400,
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_GENERATION_TTL_SECONDS
        );

        $exportSource = file_get_contents(
            app_path(
                'Services/'
                . 'ReportSavedViewShareActivityRetentionExecutionHistoryExportService.php'
            )
        );
        $historySource = file_get_contents(
            app_path(
                'Services/'
                . 'ReportSavedViewShareActivityRetentionExecutionHistoryService.php'
            )
        );

        $this->assertIsString($exportSource);
        $this->assertIsString($historySource);
        $this->assertStringContainsString(
            "\$generation . '|' . \$encoded",
            $exportSource
        );
        $this->assertStringContainsString(
            'Cache::put(',
            $historySource
        );
        $this->assertStringContainsString(
            '(string) Str::uuid()',
            $historySource
        );
        $this->assertStringNotContainsString(
            'Cache::flush(',
            $historySource
        );
    }
}
