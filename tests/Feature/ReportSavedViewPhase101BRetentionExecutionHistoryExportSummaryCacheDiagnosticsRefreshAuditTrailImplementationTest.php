<?php

namespace Tests\Feature;

use App\Http\Middleware\AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ReportSavedViewPhase101BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditTrailImplementationTest
    extends TestCase
{
    private const SAMPLED_CORRELATION_ID =
        '00000000-0000-4000-8000-000000000010';

    protected function tearDown(): void
    {
        Str::createUuidsNormally();
        Context::flush();

        parent::tearDown();
    }

    public function test_allowed_response_writes_locked_audit_context(): void
    {
        $this->forceSampledCorrelationId();
        $request = $this->request();

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
                $this->assertSame($event, $context['event']);
                $this->assertSame('allowed', $context['outcome']);
                $this->assertSame(
                    'reports.saved-view-share-activity-retention.'
                    . 'summary-cache-diagnostics',
                    $context['route_name']
                );
                $this->assertSame('GET', $context['request_method']);
                $this->assertTrue($context['authenticated']);
                $this->assertTrue($context['permission_checked']);
                $this->assertSame(
                    'saved-view-retention-summary-cache-diagnostics-refresh',
                    $context['rate_limit_name']
                );
                $this->assertArrayNotHasKey(
                    'retry_after_seconds',
                    $context
                );
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
                $request,
                static fn (): Response => new Response(
                    '{"cache_store":"array"}',
                    200,
                    ['Content-Type' => 'application/json']
                )
            );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            '{"cache_store":"array"}',
            $response->getContent()
        );
    }

    public function test_limited_response_writes_retry_after_without_controller_execution(): void
    {
        $request = $this->request();
        $nextCalled = false;

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
                $this->assertSame('limited', $context['outcome']);
                $this->assertSame(27, $context['retry_after_seconds']);
                $this->assertSame(
                    [
                        'event',
                        'outcome',
                        'route_name',
                        'request_method',
                        'authenticated',
                        'permission_checked',
                        'rate_limit_name',
                        'retry_after_seconds',
                    ],
                    array_keys($context)
                );

                return true;
            });

        $response = (new AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh())
            ->handle(
                $request,
                function () use (&$nextCalled): Response {
                    $nextCalled = true;

                    return new Response(
                        'Too Many Attempts.',
                        429,
                        ['Retry-After' => '27']
                    );
                }
            );

        $this->assertTrue($nextCalled);
        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('27', $response->headers->get('Retry-After'));
        $this->assertSame(
            'Too Many Attempts.',
            $response->getContent()
        );
    }

    public function test_audit_failure_never_changes_allowed_response(): void
    {
        $this->forceSampledCorrelationId();

        Log::shouldReceive('info')
            ->once()
            ->andThrow(new RuntimeException('audit unavailable'));

        $response = (new AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh())
            ->handle(
                $this->request(),
                static fn (): Response => new Response(
                    'unchanged',
                    200
                )
            );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('unchanged', $response->getContent());
    }

    public function test_audit_failure_never_changes_limited_response(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->andThrow(new RuntimeException('audit unavailable'));

        $response = (new AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh())
            ->handle(
                $this->request(),
                static fn (): Response => new Response(
                    'limited',
                    429,
                    ['Retry-After' => '10']
                )
            );

        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('limited', $response->getContent());
        $this->assertSame('10', $response->headers->get('Retry-After'));
    }

    public function test_route_orders_audit_before_throttle_and_after_permission(): void
    {
        $route = Route::getRoutes()->getByName(
            'reports.saved-view-share-activity-retention.'
            . 'summary-cache-diagnostics'
        );

        $this->assertNotNull($route);

        $middleware = array_values($route->gatherMiddleware());

        $permission = array_search(
            'App\Http\Middleware\EnsurePartyPermission'
            . ':manage_saved_view_share_activity_retention',
            $middleware,
            true
        );
        $audit = array_search(
            'audit.saved-view-retention-summary-cache-diagnostics-refresh',
            $middleware,
            true
        );
        $throttle = array_search(
            'throttle:'
            . 'saved-view-retention-summary-cache-diagnostics-refresh',
            $middleware,
            true
        );

        $this->assertIsInt($permission);
        $this->assertIsInt($audit);
        $this->assertIsInt($throttle);
        $this->assertLessThan($audit, $permission);
        $this->assertLessThan($throttle, $audit);
    }

    public function test_source_guards_lock_privacy_and_scope(): void
    {
        $source = file_get_contents(
            app_path(
                'Http/Middleware/'
                . 'AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh.php'
            )
        );

        $this->assertIsString($source);

        foreach ([
            'refresh_audit.allowed',
            'refresh_audit.limited',
            "'retry_after_seconds'",
            "'permission_checked' => true",
            'Log::info(',
            'catch (Throwable)',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        foreach ([
            'user()->id',
            'getAuthIdentifier',
            'request->ip',
            'raw_limiter_key',
            'generation_token',
            'raw_cache_key',
            'actor_user_id',
            'diagnostics_payload',
            'getMessage()',
            'getTrace',
            'headers->all',
            'cookies',
            'DB::',
            'Cache::',
        ] as $needle) {
            $this->assertStringNotContainsString($needle, $source);
        }
    }

    private function forceSampledCorrelationId(): void
    {
        Str::createUuidsUsing(
            static fn () => Uuid::fromString(
                self::SAMPLED_CORRELATION_ID
            )
        );
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
