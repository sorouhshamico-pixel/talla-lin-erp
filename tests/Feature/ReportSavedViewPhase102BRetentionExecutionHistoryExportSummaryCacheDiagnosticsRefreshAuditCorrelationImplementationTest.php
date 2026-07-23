<?php

namespace Tests\Feature;

use App\Http\Middleware\AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ReportSavedViewPhase102BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditCorrelationImplementationTest
    extends TestCase
{
    protected function tearDown(): void
    {
        Context::flush();

        parent::tearDown();
    }

    public function test_allowed_request_adds_one_uuid_to_laravel_context(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (
                string $event,
                array $context
            ): bool {
                $this->assertSame(
                    'saved_view_retention.'
                    . 'summary_cache_diagnostics.refresh_audit.allowed',
                    $event
                );
                $this->assertArrayNotHasKey('correlation_id', $context);
                $this->assertSame(
                    [
                        'event',
                        'outcome',
                        'route_name',
                        'request_method',
                        'authenticated',
                        'permission_checked',
                        'rate_limit_name',
                    ],
                    array_keys($context)
                );

                return true;
            });

        $response = (new AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh())
            ->handle(
                $this->request(),
                static fn (): Response => new Response('allowed', 200)
            );

        $correlationId = Context::get('correlation_id');

        $this->assertIsString($correlationId);
        $this->assertTrue(Str::isUuid($correlationId));
        $this->assertSame(36, strlen($correlationId));
        $this->assertSame(strtolower($correlationId), $correlationId);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('allowed', $response->getContent());
        $this->assertFalse(
            $response->headers->has('X-Correlation-ID')
        );
    }

    public function test_limited_request_adds_context_and_preserves_retry_after(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (
                string $event,
                array $context
            ): bool {
                $this->assertSame(
                    'saved_view_retention.'
                    . 'summary_cache_diagnostics.refresh_audit.limited',
                    $event
                );
                $this->assertSame(24, $context['retry_after_seconds']);
                $this->assertArrayNotHasKey('correlation_id', $context);

                return true;
            });

        $response = (new AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh())
            ->handle(
                $this->request(),
                static fn (): Response => new Response(
                    'limited',
                    429,
                    ['Retry-After' => '24']
                )
            );

        $this->assertTrue(
            Str::isUuid(Context::get('correlation_id'))
        );
        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('limited', $response->getContent());
        $this->assertSame('24', $response->headers->get('Retry-After'));
        $this->assertFalse(
            $response->headers->has('X-Correlation-ID')
        );
    }

    public function test_separate_requests_replace_context_with_distinct_ids(): void
    {
        Log::shouldReceive('info')->twice();

        $middleware =
            new AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh();

        $middleware->handle(
            $this->request(),
            static fn (): Response => new Response('first', 200)
        );

        $first = Context::get('correlation_id');

        $middleware->handle(
            $this->request(),
            static fn (): Response => new Response('second', 200)
        );

        $second = Context::get('correlation_id');

        $this->assertTrue(Str::isUuid($first));
        $this->assertTrue(Str::isUuid($second));
        $this->assertNotSame($first, $second);
    }

    public function test_client_headers_are_never_used_as_correlation_source(): void
    {
        $request = $this->request();
        $request->headers->set(
            'X-Request-ID',
            'client-controlled-request-id'
        );
        $request->headers->set(
            'Traceparent',
            '00-client-controlled-trace'
        );

        Log::shouldReceive('info')->once();

        (new AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh())
            ->handle(
                $request,
                static fn (): Response => new Response('ok', 200)
            );

        $correlationId = Context::get('correlation_id');

        $this->assertTrue(Str::isUuid($correlationId));
        $this->assertNotSame(
            'client-controlled-request-id',
            $correlationId
        );
        $this->assertNotSame(
            '00-client-controlled-trace',
            $correlationId
        );
    }

    public function test_audit_failure_preserves_response_and_context(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->andThrow(new RuntimeException('audit unavailable'));

        $response = (new AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh())
            ->handle(
                $this->request(),
                static fn (): Response => new Response('unchanged', 200)
            );

        $this->assertTrue(
            Str::isUuid(Context::get('correlation_id'))
        );
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('unchanged', $response->getContent());
    }

    public function test_source_guards_lock_context_generation_and_privacy(): void
    {
        $source = file_get_contents(
            app_path(
                'Http/Middleware/'
                . 'AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh.php'
            )
        );

        $this->assertIsString($source);
        $this->assertSame(
            1,
            substr_count($source, 'Str::uuid()')
        );
        $this->assertSame(
            1,
            substr_count($source, "Context::add(")
        );
        $this->assertStringContainsString(
            "'correlation_id'",
            $source
        );
        $this->assertStringNotContainsString(
            'Log::withContext',
            $source
        );

        foreach ([
            '$request->headers',
            '$request->header(',
            'session(',
            'request->ip',
            'getAuthIdentifier',
            'user()->id',
            'X-Request-ID',
            'Traceparent',
            'traceparent',
            'Authorization',
            'cookies',
            'Cache::',
            'DB::',
        ] as $needle) {
            $this->assertStringNotContainsString($needle, $source);
        }
    }

    private function request(): Request
    {
        $request = Request::create(
            '/reports/saved-view-share-activity-retention/'
            . 'summary-cache-diagnostics',
            'GET'
        );

        $request->setRouteResolver(
            static fn () => Route::getRoutes()->getByName(
                'reports.saved-view-share-activity-retention.'
                . 'summary-cache-diagnostics'
            )
        );

        $request->setUserResolver(
            static fn () => new class {
            }
        );

        return $request;
    }
}
