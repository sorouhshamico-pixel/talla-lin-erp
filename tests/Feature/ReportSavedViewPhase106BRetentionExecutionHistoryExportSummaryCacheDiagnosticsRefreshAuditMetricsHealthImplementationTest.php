<?php

namespace Tests\Feature;

use App\Events\SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded;
use App\Support\SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealth;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ReportSavedViewPhase106BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthImplementationTest
    extends TestCase
{
    public function test_current_pipeline_reports_healthy(): void
    {
        $status = app(
            SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealth::class
        )->status();

        $this->assertSame(
            [
                'listener_discovered',
                'listener_count',
                'channel_configured',
                'channel_driver',
                'channel_level',
                'channel_retention_days',
                'channel_path_matches',
                'healthy',
            ],
            array_keys($status)
        );
        $this->assertTrue($status['listener_discovered']);
        $this->assertSame(1, $status['listener_count']);
        $this->assertTrue($status['channel_configured']);
        $this->assertSame('daily', $status['channel_driver']);
        $this->assertSame('info', $status['channel_level']);
        $this->assertSame(14, $status['channel_retention_days']);
        $this->assertTrue($status['channel_path_matches']);
        $this->assertTrue($status['healthy']);
    }

    public function test_missing_listener_reports_unhealthy(): void
    {
        $dispatcher = Mockery::mock(Dispatcher::class);

        $dispatcher->shouldReceive('getListeners')
            ->once()
            ->with(
                SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded::class
            )
            ->andReturn([]);

        $status = $this->health($dispatcher)->status();

        $this->assertFalse($status['listener_discovered']);
        $this->assertSame(0, $status['listener_count']);
        $this->assertFalse($status['healthy']);
    }

    public function test_multiple_listeners_report_unhealthy(): void
    {
        $dispatcher = Mockery::mock(Dispatcher::class);

        $dispatcher->shouldReceive('getListeners')
            ->once()
            ->andReturn([
                static fn () => null,
                static fn () => null,
            ]);

        $status = $this->health($dispatcher)->status();

        $this->assertFalse($status['listener_discovered']);
        $this->assertSame(2, $status['listener_count']);
        $this->assertFalse($status['healthy']);
    }

    public function test_invalid_channel_configuration_reports_unhealthy(): void
    {
        $dispatcher = Mockery::mock(Dispatcher::class);

        $dispatcher->shouldReceive('getListeners')
            ->once()
            ->andReturn([static fn () => null]);

        config()->set(
            'logging.channels.saved_view_retention_audit_metrics',
            [
                'driver' => 'single',
                'path' => storage_path('logs/wrong.log'),
                'level' => 'debug',
                'days' => 7,
            ]
        );

        $status = $this->health($dispatcher)->status();

        $this->assertTrue($status['listener_discovered']);
        $this->assertTrue($status['channel_configured']);
        $this->assertSame('single', $status['channel_driver']);
        $this->assertSame('debug', $status['channel_level']);
        $this->assertSame(7, $status['channel_retention_days']);
        $this->assertFalse($status['channel_path_matches']);
        $this->assertFalse($status['healthy']);
    }

    public function test_unexpected_exception_returns_locked_unhealthy_shape(): void
    {
        $dispatcher = Mockery::mock(Dispatcher::class);

        $dispatcher->shouldReceive('getListeners')
            ->once()
            ->andThrow(new RuntimeException('discovery unavailable'));

        $status = $this->health($dispatcher)->status();

        $this->assertSame(
            [
                'listener_discovered' => false,
                'listener_count' => 0,
                'channel_configured' => false,
                'channel_driver' => null,
                'channel_level' => null,
                'channel_retention_days' => null,
                'channel_path_matches' => false,
                'healthy' => false,
            ],
            $status
        );
    }

    public function test_source_guards_lock_side_effect_free_scope(): void
    {
        $source = file_get_contents(
            app_path(
                'Support/'
                . 'SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealth.php'
            )
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'public function status(): array',
            $source
        );
        $this->assertStringContainsString(
            'getListeners(',
            $source
        );
        $this->assertStringContainsString(
            "config(\n                'logging.channels.'.self::CHANNEL",
            $source
        );

        foreach ([
            'event(',
            'Event::',
            'Log::',
            'logger(',
            'DB::',
            'Cache::',
            'file_get_contents(',
            'fopen(',
            'Storage::',
            'response(',
            'request(',
        ] as $needle) {
            $this->assertStringNotContainsString($needle, $source);
        }
    }

    private function health(
        Dispatcher $dispatcher
    ): SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealth {
        return new SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealth(
            $dispatcher
        );
    }
}
