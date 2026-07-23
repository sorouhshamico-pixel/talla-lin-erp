<?php

namespace App\Events;

final class SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded
{
    public function __construct(
        public readonly string $outcome,
        public readonly bool $auditAttempted,
        public readonly bool $auditSucceeded,
        public readonly string $rateLimitName,
        public readonly string $routeName,
        public readonly string $requestMethod,
    ) {
    }
}
