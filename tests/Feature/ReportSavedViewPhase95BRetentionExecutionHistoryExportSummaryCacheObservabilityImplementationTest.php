<?php

namespace Tests\Feature;

use App\Models\ReportSavedViewShareActivityRetentionExecution;
use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryExportService;
use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ReportSavedViewPhase95BRetentionExecutionHistoryExportSummaryCacheObservabilityImplementationTest
    extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_cache_miss_and_hit_emit_bounded_debug_events(): void
    {
        Log::spy();

        $service = app(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        );

        $service->summary(['status' => 'failed']);
        $service->summary(['status' => 'failed']);

        Log::shouldHaveReceived('log')
            ->withArgs(function (
                string $level,
                string $message,
                array $context
            ): bool {
                return $level === 'debug'
                    && $message ===
                        ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_EVENT_MISS
                    && $this->safeContext($context)
                    && $context['filter_count'] === 1
                    && $context['ttl_seconds'] === 30;
            })
            ->once();

        Log::shouldHaveReceived('log')
            ->withArgs(function (
                string $level,
                string $message,
                array $context
            ): bool {
                return $level === 'debug'
                    && $message ===
                        ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_EVENT_HIT
                    && $this->safeContext($context)
                    && $context['filter_count'] === 1;
            })
            ->once();
    }

    public function test_cache_fallback_emits_warning_without_sensitive_context(): void
    {
        Log::spy();

        Cache::shouldReceive('get')
            ->once()
            ->andReturn(null);
        Cache::shouldReceive('remember')
            ->once()
            ->andThrow(new RuntimeException('cache unavailable'));

        $summary = app(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        )->summary([
            'status' => 'failed',
            'actor_user_id' => 77,
        ]);

        $this->assertSame(0, $summary['total_count']);

        Log::shouldHaveReceived('log')
            ->withArgs(function (
                string $level,
                string $message,
                array $context
            ): bool {
                return $level === 'warning'
                    && $message ===
                        ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_EVENT_FALLBACK
                    && $this->safeContext($context)
                    && $context['filter_count'] === 2
                    && $context['fallback_reason_class'] ===
                        RuntimeException::class;
            })
            ->once();
    }

    public function test_generation_read_failure_emits_warning_and_uses_default(): void
    {
        Log::spy();

        Cache::shouldReceive('get')
            ->once()
            ->andThrow(new RuntimeException('generation unavailable'));
        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(
                fn (
                    string $key,
                    mixed $ttl,
                    callable $callback
                ): array => $callback()
            );

        $summary = app(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        )->summary([]);

        $this->assertSame(0, $summary['total_count']);

        Log::shouldHaveReceived('log')
            ->withArgs(function (
                string $level,
                string $message,
                array $context
            ): bool {
                return $level === 'warning'
                    && $message ===
                        ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_EVENT_GENERATION_READ_FALLBACK
                    && $this->safeContext($context)
                    && $context['generation_present'] === false
                    && $context['fallback_reason_class'] ===
                        RuntimeException::class;
            })
            ->once();
    }

    public function test_generation_rotation_success_emits_bounded_debug_event(): void
    {
        Log::spy();

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

        Log::shouldHaveReceived('log')
            ->withArgs(function (
                string $level,
                string $message,
                array $context
            ): bool {
                return $level === 'debug'
                    && $message ===
                        ReportSavedViewShareActivityRetentionExecutionHistoryService::SUMMARY_CACHE_EVENT_GENERATION_ROTATED
                    && $this->safeContext($context)
                    && $context['ttl_seconds'] === 86400;
            })
            ->once();
    }

    public function test_generation_rotation_failure_is_observed_without_failing_write(): void
    {
        Log::spy();

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
            [],
            now()
        );

        $this->assertDatabaseCount(
            'report_saved_view_share_activity_retention_executions',
            1
        );

        Log::shouldHaveReceived('log')
            ->withArgs(function (
                string $level,
                string $message,
                array $context
            ): bool {
                return $level === 'warning'
                    && $message ===
                        ReportSavedViewShareActivityRetentionExecutionHistoryService::SUMMARY_CACHE_EVENT_GENERATION_ROTATION_FAILED
                    && $this->safeContext($context)
                    && $context['fallback_reason_class'] ===
                        RuntimeException::class;
            });
    }

    public function test_observability_adds_no_database_queries(): void
    {
        $service = app(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        );

        DB::flushQueryLog();
        DB::enableQueryLog();

        $service->summary([]);
        $missQueries = DB::getQueryLog();

        DB::flushQueryLog();

        $service->summary([]);
        $hitQueries = DB::getQueryLog();

        DB::disableQueryLog();

        $this->assertCount(1, $missQueries);
        $this->assertCount(0, $hitQueries);
    }

    public function test_logging_failure_does_not_change_summary_or_history_behavior(): void
    {
        Log::shouldReceive('log')
            ->andThrow(new RuntimeException('logger unavailable'));

        $summary = app(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        )->summary([]);

        $this->assertSame(0, $summary['total_count']);

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

        $this->assertDatabaseCount(
            'report_saved_view_share_activity_retention_executions',
            1
        );
    }

    public function test_event_constants_and_forbidden_context_source_guards_are_locked(): void
    {
        $this->assertSame(
            'saved_view_retention.summary_cache.hit',
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_EVENT_HIT
        );
        $this->assertSame(
            'saved_view_retention.summary_cache.generation_rotation_failed',
            ReportSavedViewShareActivityRetentionExecutionHistoryService::SUMMARY_CACHE_EVENT_GENERATION_ROTATION_FAILED
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
        $this->assertStringNotContainsString(
            "'raw_cache_key'",
            $exportSource
        );
        $this->assertStringNotContainsString(
            "'raw_filters'",
            $exportSource
        );
        $this->assertStringNotContainsString(
            "'generation_token'",
            $historySource
        );

        $observabilitySource = substr(
            $historySource,
            strpos(
                $historySource,
                'private function invalidateSummaryCache'
            )
        );

        $this->assertIsString($observabilitySource);
        $this->assertStringNotContainsString(
            "'actor_user_id'",
            $observabilitySource
        );
    }

    private function safeContext(array $context): bool
    {
        foreach ([
            'raw_cache_key',
            'generation_token',
            'raw_filters',
            'actor_user_id',
            'history_payload',
            'failure_message',
            'failure_stack_trace',
        ] as $forbidden) {
            if (array_key_exists($forbidden, $context)) {
                return false;
            }
        }

        return isset($context['event'], $context['cache_key_prefix']);
    }
}
