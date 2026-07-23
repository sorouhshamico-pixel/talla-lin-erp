<?php

namespace App\Listeners;

use App\Events\SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded;
use Illuminate\Support\Facades\Log;
use Throwable;

final class RecordSavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetric
{
    private const CHANNEL = 'saved_view_retention_audit_metrics';

    private const MESSAGE =
        'saved_view_retention.summary_cache_diagnostics.refresh_audit.metric';

    public function handle(
        SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded $event
    ): void {
        try {
            Log::channel(self::CHANNEL)->info(
                self::MESSAGE,
                [
                    'outcome' => $event->outcome,
                    'audit_attempted' => $event->auditAttempted,
                    'audit_succeeded' => $event->auditSucceeded,
                    'rate_limit_name' => $event->rateLimitName,
                    'route_name' => $event->routeName,
                    'request_method' => $event->requestMethod,
                ]
            );
        } catch (Throwable) {
        }
    }
}
