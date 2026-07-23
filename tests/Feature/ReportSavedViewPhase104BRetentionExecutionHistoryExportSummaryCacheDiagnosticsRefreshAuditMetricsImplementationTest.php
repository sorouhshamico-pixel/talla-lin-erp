<?php

namespace Tests\Feature;

use App\Events\SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded;
use App\Http\Middleware\AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ReportSavedViewPhase104BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsImplementationTest
    extends TestCase
{
    private const SAMPLED_ID =
        '00000000-0000-4000-8000-000000000010';

    private const UNSAMPLED_ID =
        '00000000-0000-4000-8000-000000000001';

    protected function tearDown(): void
    {
        Str::createUuidsNormally();
        Context::flush();

        parent::tearDown();
    }

    public function test_sampled_allowed_request_dispatches_success_metric(): void
    {
        $this->forceUuid(self::SAMPLED_ID);
        Event::fake();

        Log::shouldReceive('info')->once();

        $response = $this->middleware()->handle(
            $this->request(),
            static fn (): Response => new Response('allowed', 200)
        );

        Event::assertDispatched(
            SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded::class,
            function (
                SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded $event
            ): bool {
                $this->assertSame('allowed_sampled', $event->outcome);
                $this->assertTrue($event->auditAttempted);
                $this->assertTrue($event->auditSucceeded);
                $this->assertLockedDimensions($event);

                return true;
            }
        );

        Event::assertDispatchedTimes(
            SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded::class,
            1
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('allowed', $response->getContent());
    }

    public function test_unsampled_allowed_request_dispatches_skipped_metric(): void
    {
        $this->forceUuid(self::UNSAMPLED_ID);
        Event::fake();

        Log::shouldReceive('info')->never();

        $response = $this->middleware()->handle(
            $this->request(),
            static fn (): Response => new Response('allowed', 200)
        );

        Event::assertDispatched(
            SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded::class,
            function (
                SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded $event
            ): bool {
                $this->assertSame('allowed_unsampled', $event->outcome);
                $this->assertFalse($event->auditAttempted);
                $this->assertFalse($event->auditSucceeded);
                $this->assertLockedDimensions($event);

                return true;
            }
        );

        Event::assertDispatchedTimes(
            SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded::class,
            1
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_limited_request_dispatches_metric_and_keeps_response(): void
    {
        $this->forceUuid(self::UNSAMPLED_ID);
        Event::fake();

        Log::shouldReceive('info')->once();

        $response = $this->middleware()->handle(
            $this->request(),
            static fn (): Response => new Response(
                'limited',
                429,
                ['Retry-After' => '19']
            )
        );

        Event::assertDispatched(
            SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded::class,
            function (
                SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded $event
            ): bool {
                $this->assertSame('limited', $event->outcome);
                $this->assertTrue($event->auditAttempted);
                $this->assertTrue($event->auditSucceeded);
                $this->assertLockedDimensions($event);

                return true;
            }
        );

        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('19', $response->headers->get('Retry-After'));
        $this->assertSame('limited', $response->getContent());
    }

    public function test_audit_failure_dispatches_failed_metric_and_preserves_response(): void
    {
        $this->forceUuid(self::SAMPLED_ID);
        Event::fake();

        Log::shouldReceive('info')
            ->once()
            ->andThrow(new RuntimeException('audit unavailable'));

        $response = $this->middleware()->handle(
            $this->request(),
            static fn (): Response => new Response('unchanged', 200)
        );

        Event::assertDispatched(
            SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded::class,
            function (
                SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded $event
            ): bool {
                $this->assertSame('allowed_sampled', $event->outcome);
                $this->assertTrue($event->auditAttempted);
                $this->assertFalse($event->auditSucceeded);

                return true;
            }
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('unchanged', $response->getContent());
    }

    public function test_event_dispatch_failure_preserves_response(): void
    {
        $this->forceUuid(self::UNSAMPLED_ID);

        Log::shouldReceive('info')->never();

        Event::shouldReceive('dispatch')
            ->once()
            ->andThrow(new RuntimeException('event unavailable'));

        $response = $this->middleware()->handle(
            $this->request(),
            static fn (): Response => new Response('unchanged', 200)
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('unchanged', $response->getContent());
    }

    public function test_event_payload_is_exact_and_privacy_safe(): void
    {
        $reflection = new \ReflectionClass(
            SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded::class
        );

        $properties = array_map(
            static fn (\ReflectionProperty $property): string =>
                $property->getName(),
            $reflection->getProperties(\ReflectionProperty::IS_PUBLIC)
        );

        $this->assertSame(
            [
                'outcome',
                'auditAttempted',
                'auditSucceeded',
                'rateLimitName',
                'routeName',
                'requestMethod',
            ],
            $properties
        );

        foreach ([
            'correlationId',
            'userId',
            'ip',
            'sessionId',
            'headers',
            'cookies',
            'retryAfter',
            'samplingBucket',
            'diagnosticsPayload',
            'cacheKey',
        ] as $forbidden) {
            $this->assertFalse($reflection->hasProperty($forbidden));
        }
    }

    public function test_source_guards_lock_scope_and_compatibility(): void
    {
        $middleware = file_get_contents(
            app_path(
                'Http/Middleware/'
                . 'AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh.php'
            )
        );
        $event = file_get_contents(
            app_path(
                'Events/'
                . 'SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded.php'
            )
        );

        $this->assertIsString($middleware);
        $this->assertIsString($event);

        $this->assertSame(
            1,
            substr_count(
                $middleware,
                'new SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded('
            )
        );
        $this->assertStringContainsString(
            'private function audit(string $event, array $context): bool',
            $middleware
        );
        $this->assertStringContainsString(
            '$auditAttempted = $limited || $this->shouldAuditAllowed();',
            $middleware
        );

        foreach ([
            'Cache::',
            'DB::',
            'ShouldQueue',
            'ShouldDispatchAfterCommit',
            'correlationId',
            'samplingBucket',
            'retryAfter',
        ] as $needle) {
            $this->assertStringNotContainsString($needle, $event);
        }
    }

    private function assertLockedDimensions(
        SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricRecorded $event
    ): void {
        $this->assertSame(
            'saved-view-retention-summary-cache-diagnostics-refresh',
            $event->rateLimitName
        );
        $this->assertSame(
            'reports.saved-view-share-activity-retention.'
            . 'summary-cache-diagnostics',
            $event->routeName
        );
        $this->assertSame('GET', $event->requestMethod);
    }

    private function forceUuid(string $uuid): void
    {
        Str::createUuidsUsing(
            static fn () => Uuid::fromString($uuid)
        );
    }

    private function middleware():
        AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh
    {
        return new AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh();
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
