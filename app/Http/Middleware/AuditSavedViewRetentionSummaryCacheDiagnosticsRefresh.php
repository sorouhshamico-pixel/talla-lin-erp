<?php

namespace App\Http\Middleware;

use App\Events\SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh
{
    private const ALLOWED_EVENT =
        'saved_view_retention.summary_cache_diagnostics.refresh_audit.allowed';

    private const LIMITED_EVENT =
        'saved_view_retention.summary_cache_diagnostics.refresh_audit.limited';

    private const RATE_LIMIT_NAME =
        'saved-view-retention-summary-cache-diagnostics-refresh';

    private const ALLOWED_SAMPLE_RATE_PERCENT = 25;

    public function handle(Request $request, Closure $next): Response
    {
        Context::add(
            'correlation_id',
            (string) Str::uuid()
        );

        $response = $next($request);

        $limited = $response->getStatusCode()
            === Response::HTTP_TOO_MANY_REQUESTS;

        $event = $limited
            ? self::LIMITED_EVENT
            : self::ALLOWED_EVENT;

        $context = [
            'event' => $event,
            'outcome' => $limited ? 'limited' : 'allowed',
            'route_name' => (string) $request->route()?->getName(),
            'request_method' => $request->getMethod(),
            'authenticated' => $request->user() !== null,
            'permission_checked' => true,
            'rate_limit_name' => self::RATE_LIMIT_NAME,
        ];

        if ($limited) {
            $context['retry_after_seconds'] = max(
                0,
                (int) $response->headers->get('Retry-After', 0)
            );
        }

        $auditAttempted = $limited || $this->shouldAuditAllowed();
        $auditSucceeded = false;

        if ($auditAttempted) {
            $auditSucceeded = $this->audit($event, $context);
        }

        $outcome = $limited
            ? 'limited'
            : ($auditAttempted ? 'allowed_sampled' : 'allowed_unsampled');

        $this->recordMetric(
            $outcome,
            $auditAttempted,
            $auditSucceeded,
            (string) $request->route()?->getName(),
            $request->getMethod()
        );

        return $response;
    }

    private function shouldAuditAllowed(): bool
    {
        $correlationId = Context::get('correlation_id');

        if (! is_string($correlationId) || ! Str::isUuid($correlationId)) {
            return true;
        }

        $bucket = hexdec(
            substr(hash('sha256', $correlationId), 0, 8)
        ) % 100;

        return $bucket < self::ALLOWED_SAMPLE_RATE_PERCENT;
    }

    private function audit(string $event, array $context): bool
    {
        try {
            Log::info($event, $context);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function recordMetric(
        string $outcome,
        bool $auditAttempted,
        bool $auditSucceeded,
        string $routeName,
        string $requestMethod
    ): void {
        try {
            event(
                new SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded(
                    outcome: $outcome,
                    auditAttempted: $auditAttempted,
                    auditSucceeded: $auditSucceeded,
                    rateLimitName: self::RATE_LIMIT_NAME,
                    routeName: $routeName,
                    requestMethod: $requestMethod,
                )
            );
        } catch (Throwable) {
        }
    }
}
