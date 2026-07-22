<?php

namespace Tests\Feature;

use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ReportSavedViewPhase96BRetentionExecutionHistoryExportSummaryCacheDiagnosticsImplementationTest
    extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_missing_generation_returns_locked_default_snapshot(): void
    {
        $diagnostics = app(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        )->summaryCacheDiagnostics();

        $this->assertSame([
            'cache_key_prefix',
            'summary_ttl_seconds',
            'generation_key_prefix',
            'generation_ttl_seconds',
            'generation_present',
            'generation_source',
            'cache_store',
            'cache_read_available',
            'observability_enabled',
        ], array_keys($diagnostics));

        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_KEY_PREFIX,
            $diagnostics['cache_key_prefix']
        );
        $this->assertSame(30, $diagnostics['summary_ttl_seconds']);
        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_GENERATION_KEY,
            $diagnostics['generation_key_prefix']
        );
        $this->assertSame(
            86400,
            $diagnostics['generation_ttl_seconds']
        );
        $this->assertFalse($diagnostics['generation_present']);
        $this->assertSame('default', $diagnostics['generation_source']);
        $this->assertSame(
            (string) config('cache.default'),
            $diagnostics['cache_store']
        );
        $this->assertTrue($diagnostics['cache_read_available']);
        $this->assertTrue($diagnostics['observability_enabled']);
    }

    public function test_present_generation_reports_presence_without_exposing_token(): void
    {
        Cache::put(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_GENERATION_KEY,
            'sensitive-generation-token',
            now()->addHour()
        );

        $diagnostics = app(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        )->summaryCacheDiagnostics();

        $this->assertTrue($diagnostics['generation_present']);
        $this->assertSame('cache', $diagnostics['generation_source']);
        $this->assertNotContains(
            'sensitive-generation-token',
            array_values($diagnostics),
            true
        );
        $this->assertArrayNotHasKey(
            'generation_token',
            $diagnostics
        );
        $this->assertArrayNotHasKey('raw_cache_key', $diagnostics);
    }

    public function test_cache_read_failure_returns_fallback_snapshot_without_throwing(): void
    {
        Cache::shouldReceive('get')
            ->once()
            ->with(
                ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_GENERATION_KEY
            )
            ->andThrow(new RuntimeException('cache unavailable'));

        $diagnostics = app(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        )->summaryCacheDiagnostics();

        $this->assertFalse($diagnostics['generation_present']);
        $this->assertSame('fallback', $diagnostics['generation_source']);
        $this->assertFalse($diagnostics['cache_read_available']);
        $this->assertArrayNotHasKey(
            'exception_message',
            $diagnostics
        );
        $this->assertArrayNotHasKey('stack_trace', $diagnostics);
    }

    public function test_diagnostics_reads_cache_once_and_executes_zero_database_queries(): void
    {
        Cache::shouldReceive('get')
            ->once()
            ->with(
                ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_GENERATION_KEY
            )
            ->andReturn('generation');

        DB::flushQueryLog();
        DB::enableQueryLog();

        $diagnostics = app(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        )->summaryCacheDiagnostics();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertSame('cache', $diagnostics['generation_source']);
        $this->assertCount(0, $queries);
    }

    public function test_diagnostics_does_not_compute_summary_or_hydrate_models(): void
    {
        DB::listen(function ($query): void {
            $this->fail(
                'Diagnostics executed an unexpected database query: '
                . $query->sql
            );
        });

        $diagnostics = app(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        )->summaryCacheDiagnostics();

        $this->assertIsArray($diagnostics);
        $this->assertSame(9, count($diagnostics));
    }

    public function test_source_guards_lock_privacy_and_non_query_behavior(): void
    {
        $source = file_get_contents(
            app_path(
                'Services/'
                . 'ReportSavedViewShareActivityRetentionExecutionHistoryExportService.php'
            )
        );

        $this->assertIsString($source);

        $start = strpos(
            $source,
            'public function summaryCacheDiagnostics'
        );
        $end = strpos(
            $source,
            'public function summary(',
            $start
        );

        $this->assertIsInt($start);
        $this->assertIsInt($end);

        $methodSource = substr(
            $source,
            $start,
            $end - $start
        );

        $this->assertStringContainsString(
            'Cache::get(',
            $methodSource
        );
        $this->assertStringNotContainsString(
            'ReportSavedViewShareActivityRetentionExecution::query()',
            $methodSource
        );
        $this->assertStringNotContainsString(
            "'generation_token'",
            $methodSource
        );
        $this->assertStringNotContainsString(
            "'raw_filters'",
            $methodSource
        );
        $this->assertStringNotContainsString(
            "'actor_user_id'",
            $methodSource
        );
        $this->assertStringNotContainsString(
            "'exception_message'",
            $methodSource
        );
    }
}
