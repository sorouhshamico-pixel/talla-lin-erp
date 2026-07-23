<?php

namespace App\Support;

use App\Events\SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded;
use Illuminate\Contracts\Events\Dispatcher;
use Throwable;

final class SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealth
{
    private const CHANNEL =
        'saved_view_retention_audit_metrics';

    private const EXPECTED_DRIVER = 'daily';

    private const EXPECTED_LEVEL = 'info';

    private const EXPECTED_RETENTION_DAYS = 14;

    private const EXPECTED_PATH =
        'logs/saved-view-retention-audit-metrics.log';

    public function __construct(
        private readonly Dispatcher $events
    ) {
    }

    public function status(): array
    {
        try {
            $listeners = $this->events->getListeners(
                SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded::class
            );

            $listenerCount = count($listeners);
            $channel = config(
                'logging.channels.'.self::CHANNEL
            );

            $channelConfigured = is_array($channel);
            $driver = $channelConfigured
                ? ($channel['driver'] ?? null)
                : null;
            $level = $channelConfigured
                ? ($channel['level'] ?? null)
                : null;
            $retentionDays = $channelConfigured
                ? ($channel['days'] ?? null)
                : null;
            $path = $channelConfigured
                ? ($channel['path'] ?? null)
                : null;

            $listenerDiscovered = $listenerCount === 1;
            $channelPathMatches = is_string($path)
                && $path === storage_path(self::EXPECTED_PATH);

            $healthy = $listenerDiscovered
                && $channelConfigured
                && $driver === self::EXPECTED_DRIVER
                && $level === self::EXPECTED_LEVEL
                && $retentionDays === self::EXPECTED_RETENTION_DAYS
                && $channelPathMatches;

            return [
                'listener_discovered' => $listenerDiscovered,
                'listener_count' => $listenerCount,
                'channel_configured' => $channelConfigured,
                'channel_driver' => $driver,
                'channel_level' => $level,
                'channel_retention_days' => $retentionDays,
                'channel_path_matches' => $channelPathMatches,
                'healthy' => $healthy,
            ];
        } catch (Throwable) {
            return [
                'listener_discovered' => false,
                'listener_count' => 0,
                'channel_configured' => false,
                'channel_driver' => null,
                'channel_level' => null,
                'channel_retention_days' => null,
                'channel_path_matches' => false,
                'healthy' => false,
            ];
        }
    }
}
