<?php

namespace Tests\Feature;

use App\Http\Middleware\AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ReportSavedViewPhase103BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditSamplingImplementationTest
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

    public function test_known_buckets_match_locked_policy(): void
    {
        $this->assertSame(22, $this->bucket(self::SAMPLED_ID));
        $this->assertSame(48, $this->bucket(self::UNSAMPLED_ID));
    }

    public function test_sampled_allowed_request_writes_existing_event(): void
    {
        $this->forceUuid(self::SAMPLED_ID);

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
                $this->assertArrayNotHasKey('sampling_bucket', $context);
                $this->assertArrayNotHasKey('sampled', $context);

                return true;
            });

        $response = $this->middleware()->handle(
            $this->request(),
            static fn (): Response => new Response('allowed', 200)
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(self::SAMPLED_ID, Context::get('correlation_id'));
    }

    public function test_unsampled_allowed_request_skips_only_log_call(): void
    {
        $this->forceUuid(self::UNSAMPLED_ID);

        Log::shouldReceive('info')->never();

        $response = $this->middleware()->handle(
            $this->request(),
            static fn (): Response => new Response('allowed', 200)
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('allowed', $response->getContent());
        $this->assertSame(
            self::UNSAMPLED_ID,
            Context::get('correlation_id')
        );
    }

    public function test_limited_request_always_logs(): void
    {
        $this->forceUuid(self::UNSAMPLED_ID);

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
                $this->assertSame(12, $context['retry_after_seconds']);

                return true;
            });

        $response = $this->middleware()->handle(
            $this->request(),
            static fn (): Response => new Response(
                'limited',
                429,
                ['Retry-After' => '12']
            )
        );

        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('12', $response->headers->get('Retry-After'));
    }

    public function test_missing_and_invalid_context_fail_open(): void
    {
        $middleware = $this->middleware();
        $method = new \ReflectionMethod(
            $middleware,
            'shouldAuditAllowed'
        );
        $method->setAccessible(true);

        Context::forget('correlation_id');
        $this->assertTrue($method->invoke($middleware));

        Context::add('correlation_id', 'invalid');
        $this->assertTrue($method->invoke($middleware));
    }

    public function test_decision_is_deterministic(): void
    {
        $middleware = $this->middleware();
        $method = new \ReflectionMethod(
            $middleware,
            'shouldAuditAllowed'
        );
        $method->setAccessible(true);

        Context::add('correlation_id', self::SAMPLED_ID);

        $first = $method->invoke($middleware);
        $second = $method->invoke($middleware);

        $this->assertTrue($first);
        $this->assertSame($first, $second);
    }

    public function test_source_guards_lock_policy_and_privacy(): void
    {
        $source = file_get_contents(
            app_path(
                'Http/Middleware/'
                . 'AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh.php'
            )
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'private const ALLOWED_SAMPLE_RATE_PERCENT = 25;',
            $source
        );
        $this->assertStringContainsString(
            "hash('sha256', \$correlationId)",
            $source
        );
        $this->assertStringContainsString('% 100', $source);
        $this->assertStringContainsString(
            '$auditAttempted = $limited || $this->shouldAuditAllowed();',
            $source
        );
        $this->assertStringContainsString(
            'if ($auditAttempted)',
            $source
        );

        foreach ([
            'random_int(',
            'mt_rand(',
            'rand(',
            'sampling_bucket',
            "'sampled' =>",
            '$request->headers',
            '$request->header(',
            'Cache::',
            'DB::',
        ] as $needle) {
            $this->assertStringNotContainsString($needle, $source);
        }
    }

    private function bucket(string $id): int
    {
        return hexdec(substr(hash('sha256', $id), 0, 8)) % 100;
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
