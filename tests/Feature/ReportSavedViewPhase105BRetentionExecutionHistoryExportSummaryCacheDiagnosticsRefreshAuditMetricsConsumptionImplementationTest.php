<?php

namespace Tests\Feature;

use App\Events\SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded;
use App\Listeners\RecordSavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetric;
use Illuminate\Events\Dispatcher;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class ReportSavedViewPhase105BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsConsumptionImplementationTest
    extends TestCase
{
    public function test_listener_is_discovered_for_locked_event(): void
    {
        $listeners = app(Dispatcher::class)->getListeners(
            SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded::class
        );

        $this->assertCount(1, $listeners);
    }

    public function test_listener_writes_exact_context_to_dedicated_channel(): void
    {
        $channel = \Mockery::mock();

        Log::shouldReceive('channel')
            ->once()
            ->with('saved_view_retention_audit_metrics')
            ->andReturn($channel);

        $channel->shouldReceive('info')
            ->once()
            ->withArgs(function (
                string $message,
                array $context
            ): bool {
                $this->assertSame(
                    'saved_view_retention.'
                    . 'summary_cache_diagnostics.refresh_audit.metric',
                    $message
                );
                $this->assertSame(
                    [
                        'outcome',
                        'audit_attempted',
                        'audit_succeeded',
                        'rate_limit_name',
                        'route_name',
                        'request_method',
                    ],
                    array_keys($context)
                );
                $this->assertSame(
                    'allowed_sampled',
                    $context['outcome']
                );
                $this->assertTrue($context['audit_attempted']);
                $this->assertTrue($context['audit_succeeded']);
                $this->assertSame(
                    'saved-view-retention-summary-cache-diagnostics-refresh',
                    $context['rate_limit_name']
                );
                $this->assertSame(
                    'reports.saved-view-share-activity-retention.'
                    . 'summary-cache-diagnostics',
                    $context['route_name']
                );
                $this->assertSame('GET', $context['request_method']);

                return true;
            });

        $this->listener()->handle($this->event());
    }

    public function test_listener_failure_is_swallowed(): void
    {
        Log::shouldReceive('channel')
            ->once()
            ->andThrow(new RuntimeException('channel unavailable'));

        $this->listener()->handle($this->event());

        $this->addToAssertionCount(1);
    }

    public function test_dedicated_channel_configuration_is_locked(): void
    {
        $channel = config(
            'logging.channels.saved_view_retention_audit_metrics'
        );

        $this->assertIsArray($channel);
        $this->assertSame('daily', $channel['driver']);
        $this->assertSame(
            storage_path(
                'logs/saved-view-retention-audit-metrics.log'
            ),
            $channel['path']
        );
        $this->assertSame('info', $channel['level']);
        $this->assertSame(14, $channel['days']);
        $this->assertTrue($channel['replace_placeholders']);
    }

    public function test_event_dispatch_invokes_listener_once(): void
    {
        $channel = \Mockery::mock();

        Log::shouldReceive('channel')
            ->once()
            ->with('saved_view_retention_audit_metrics')
            ->andReturn($channel);

        $channel->shouldReceive('info')->once();

        event($this->event());

        $this->addToAssertionCount(1);
    }

    public function test_payload_and_source_guards_lock_privacy_and_scope(): void
    {
        $source = file_get_contents(
            app_path(
                'Listeners/'
                . 'RecordSavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetric.php'
            )
        );
        $logging = file_get_contents(config_path('logging.php'));

        $this->assertIsString($source);
        $this->assertIsString($logging);

        $this->assertSame(
            1,
            substr_count(
                $source,
                "Log::channel(self::CHANNEL)->info("
            )
        );
        $this->assertStringContainsString(
            "private const CHANNEL = 'saved_view_retention_audit_metrics';",
            $source
        );
        $this->assertStringNotContainsString(
            'implements ShouldQueue',
            $source
        );

        foreach ([
            'correlation',
            'user_id',
            'ip_address',
            'session',
            'headers',
            'cookies',
            'retry_after',
            'sampling_bucket',
            'diagnostics_payload',
            'cache_key',
            'DB::',
            'Cache::',
        ] as $needle) {
            $this->assertStringNotContainsString($needle, $source);
        }

        $this->assertStringContainsString(
            "'saved_view_retention_audit_metrics' => [",
            $logging
        );
        $this->assertStringContainsString(
            "'logs/saved-view-retention-audit-metrics.log'",
            $logging
        );
    }

    private function listener():
        RecordSavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetric
    {
        return new RecordSavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetric();
    }

    private function event():
        SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded
    {
        return new SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded(
            outcome: 'allowed_sampled',
            auditAttempted: true,
            auditSucceeded: true,
            rateLimitName:
                'saved-view-retention-summary-cache-diagnostics-refresh',
            routeName:
                'reports.saved-view-share-activity-retention.'
                . 'summary-cache-diagnostics',
            requestMethod: 'GET',
        );
    }
}
