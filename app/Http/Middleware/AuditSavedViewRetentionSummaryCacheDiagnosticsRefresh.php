<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    public function handle(Request $request, Closure $next): Response
    {
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

        $this->audit($event, $context);

        return $response;
    }

    private function audit(string $event, array $context): void
    {
        try {
            Log::info($event, $context);
        } catch (Throwable) {
        }
    }
}
