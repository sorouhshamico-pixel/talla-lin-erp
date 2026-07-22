<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportSavedViewShareActivityRetentionAdminController;
use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ReportSavedViewPhase99BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshObservabilityImplementationTest
    extends TestCase
{
    public function test_success_logs_privacy_safe_debug_event_and_preserves_payload(): void
    {
        $diagnostics = $this->diagnostics();

        $export = Mockery::mock(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        );

        $export->shouldReceive('summaryCacheDiagnostics')
            ->once()
            ->andReturn($diagnostics);

        Log::shouldReceive('log')
            ->once()
            ->withArgs(function (
                string $level,
                string $message,
                array $context
            ) use ($diagnostics): bool {
                $this->assertSame('debug', $level);
                $this->assertSame(
                    'saved_view_retention.'
                    . 'summary_cache_diagnostics.refresh_succeeded',
                    $message
                );
                $this->assertSame($message, $context['event']);
                $this->assertSame(
                    $diagnostics['cache_store'],
                    $context['cache_store']
                );
                $this->assertSame(
                    $diagnostics['cache_read_available'],
                    $context['cache_read_available']
                );
                $this->assertSame(
                    $diagnostics['generation_present'],
                    $context['generation_present']
                );
                $this->assertSame(
                    $diagnostics['generation_source'],
                    $context['generation_source']
                );
                $this->assertSame(
                    $diagnostics['observability_enabled'],
                    $context['observability_enabled']
                );
                $this->assertSame(
                    [
                        'event',
                        'cache_store',
                        'cache_read_available',
                        'generation_present',
                        'generation_source',
                        'observability_enabled',
                    ],
                    array_keys($context)
                );

                return true;
            });

        $response = (new ReportSavedViewShareActivityRetentionAdminController())
            ->summaryCacheDiagnostics($export);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($diagnostics, $response->getData(true));
    }

    public function test_failure_logs_reason_class_and_rethrows_same_exception(): void
    {
        $exception = new RuntimeException(
            'sensitive diagnostics failure message'
        );

        $export = Mockery::mock(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        );

        $export->shouldReceive('summaryCacheDiagnostics')
            ->once()
            ->andThrow($exception);

        Log::shouldReceive('log')
            ->once()
            ->withArgs(function (
                string $level,
                string $message,
                array $context
            ): bool {
                $this->assertSame('warning', $level);
                $this->assertSame(
                    'saved_view_retention.'
                    . 'summary_cache_diagnostics.refresh_failed',
                    $message
                );
                $this->assertSame($message, $context['event']);
                $this->assertSame(
                    RuntimeException::class,
                    $context['failure_reason_class']
                );
                $this->assertSame(
                    ['event', 'failure_reason_class'],
                    array_keys($context)
                );
                $this->assertNotContains(
                    'sensitive diagnostics failure message',
                    $context
                );

                return true;
            });

        try {
            (new ReportSavedViewShareActivityRetentionAdminController())
                ->summaryCacheDiagnostics($export);

            $this->fail('Expected the original exception to propagate.');
        } catch (RuntimeException $caught) {
            $this->assertSame($exception, $caught);
        }
    }

    public function test_successful_response_survives_logging_failure(): void
    {
        $diagnostics = $this->diagnostics();

        $export = Mockery::mock(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        );

        $export->shouldReceive('summaryCacheDiagnostics')
            ->once()
            ->andReturn($diagnostics);

        Log::shouldReceive('log')
            ->once()
            ->andThrow(new RuntimeException('logging unavailable'));

        $response = (new ReportSavedViewShareActivityRetentionAdminController())
            ->summaryCacheDiagnostics($export);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($diagnostics, $response->getData(true));
    }

    public function test_original_service_exception_survives_logging_failure(): void
    {
        $exception = new RuntimeException('service failure');

        $export = Mockery::mock(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        );

        $export->shouldReceive('summaryCacheDiagnostics')
            ->once()
            ->andThrow($exception);

        Log::shouldReceive('log')
            ->once()
            ->andThrow(new RuntimeException('logging unavailable'));

        try {
            (new ReportSavedViewShareActivityRetentionAdminController())
                ->summaryCacheDiagnostics($export);

            $this->fail('Expected the original service exception.');
        } catch (RuntimeException $caught) {
            $this->assertSame($exception, $caught);
        }
    }

    public function test_observability_adds_zero_database_queries(): void
    {
        $export = Mockery::mock(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        );

        $export->shouldReceive('summaryCacheDiagnostics')
            ->once()
            ->andReturn($this->diagnostics());

        Log::shouldReceive('log')->once();

        DB::flushQueryLog();
        DB::enableQueryLog();

        (new ReportSavedViewShareActivityRetentionAdminController())
            ->summaryCacheDiagnostics($export);

        $queries = DB::getQueryLog();

        DB::disableQueryLog();

        $this->assertCount(0, $queries);
    }

    public function test_source_guards_lock_events_context_and_scope(): void
    {
        $source = file_get_contents(
            app_path(
                'Http/Controllers/'
                . 'ReportSavedViewShareActivityRetentionAdminController.php'
            )
        );

        $this->assertIsString($source);

        foreach ([
            'summary_cache_diagnostics.refresh_succeeded',
            'summary_cache_diagnostics.refresh_failed',
            "'debug'",
            "'warning'",
            "'failure_reason_class'",
            "'cache_store'",
            "'cache_read_available'",
            "'generation_present'",
            "'generation_source'",
            "'observability_enabled'",
            'private function observe(',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        $diagnosticsMethodStart = strpos(
            $source,
            'public function summaryCacheDiagnostics('
        );
        $previewMethodStart = strpos(
            $source,
            'public function preview('
        );
        $observeMethodStart = strpos(
            $source,
            'private function observe('
        );

        $this->assertIsInt($diagnosticsMethodStart);
        $this->assertIsInt($previewMethodStart);
        $this->assertIsInt($observeMethodStart);

        $observabilitySource =
            substr(
                $source,
                $diagnosticsMethodStart,
                $previewMethodStart - $diagnosticsMethodStart
            )
            . substr($source, $observeMethodStart);

        foreach ([
            'generation_token',
            'raw_cache_key',
            'request_headers',
            'session_id',
            'getMessage()',
            'getTrace',
        ] as $needle) {
            $this->assertStringNotContainsString(
                $needle,
                $observabilitySource
            );
        }
    }

    private function diagnostics(): array
    {
        return [
            'cache_key_prefix' =>
                'reports:saved-view-retention:execution-history-summary:v1',
            'summary_ttl_seconds' => 30,
            'generation_key_prefix' =>
                'reports:saved-view-retention:execution-history-summary:generation:v1',
            'generation_ttl_seconds' => 86400,
            'generation_present' => true,
            'generation_source' => 'cache',
            'cache_store' => 'array',
            'cache_read_available' => true,
            'observability_enabled' => true,
        ];
    }
}
