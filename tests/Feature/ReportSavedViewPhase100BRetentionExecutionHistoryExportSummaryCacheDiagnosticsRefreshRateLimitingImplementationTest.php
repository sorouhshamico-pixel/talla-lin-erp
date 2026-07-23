<?php

namespace Tests\Feature;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewPhase100BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshRateLimitingImplementationTest
    extends TestCase
{
    private const LIMITER =
        'saved-view-retention-summary-cache-diagnostics-refresh';

    public function test_named_limiter_is_registered_with_locked_limits(): void
    {
        $callback = RateLimiter::limiter(self::LIMITER);

        $this->assertNotNull($callback);

        $request = Request::create(
            '/reports/saved-view-share-activity-retention/'
            . 'summary-cache-diagnostics',
            'GET',
            [],
            [],
            [],
            ['REMOTE_ADDR' => '203.0.113.10']
        );

        $request->setUserResolver(
            static fn () => new class {
                public function getAuthIdentifier(): int
                {
                    return 42;
                }
            }
        );

        $limit = $callback($request);

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertSame(30, $limit->maxAttempts);
        $this->assertSame(60, $limit->decaySeconds);
        $this->assertSame(
            hash('sha256', 'user:42'),
            $limit->key
        );
        $this->assertStringNotContainsString('42', $limit->key);
    }

    public function test_guest_fallback_uses_hashed_ip_key(): void
    {
        $callback = RateLimiter::limiter(self::LIMITER);

        $this->assertNotNull($callback);

        $request = Request::create(
            '/reports/saved-view-share-activity-retention/'
            . 'summary-cache-diagnostics',
            'GET',
            [],
            [],
            [],
            ['REMOTE_ADDR' => '198.51.100.23']
        );

        $limit = $callback($request);

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertSame(
            hash('sha256', 'ip:198.51.100.23'),
            $limit->key
        );
        $this->assertStringNotContainsString(
            '198.51.100.23',
            $limit->key
        );
    }

    public function test_route_keeps_auth_permission_and_adds_throttle(): void
    {
        $route = Route::getRoutes()->getByName(
            'reports.saved-view-share-activity-retention.'
            . 'summary-cache-diagnostics'
        );

        $this->assertNotNull($route);
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertSame(
            'reports/saved-view-share-activity-retention/'
            . 'summary-cache-diagnostics',
            $route->uri()
        );

        $middleware = $route->gatherMiddleware();

        $this->assertContains('auth', $middleware);
        $this->assertContains(
            'App\Http\Middleware\EnsurePartyPermission'
            . ':manage_saved_view_share_activity_retention',
            $middleware
        );
        $this->assertContains(
            'throttle:' . self::LIMITER,
            $middleware
        );
    }

    public function test_rate_limiter_blocks_after_thirty_attempts(): void
    {
        $key = 'phase100b:' . bin2hex(random_bytes(8));

        RateLimiter::clear($key);

        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $this->assertTrue(
                RateLimiter::attempt(
                    $key,
                    30,
                    static fn (): bool => true,
                    60
                )
            );
        }

        $this->assertFalse(
            RateLimiter::attempt(
                $key,
                30,
                static fn (): bool => true,
                60
            )
        );
        $this->assertGreaterThanOrEqual(
            1,
            RateLimiter::availableIn($key)
        );

        RateLimiter::clear($key);
    }

    public function test_source_guards_lock_scope_and_privacy(): void
    {
        $provider = file_get_contents(
            app_path('Providers/AppServiceProvider.php')
        );
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertIsString($provider);
        $this->assertIsString($routes);

        foreach ([
            'RateLimiter::for(',
            "'saved-view-retention-summary-cache-diagnostics-refresh'",
            'Limit::perMinute(30)',
            "hash('sha256', \$identity)",
            "'user:'",
            "'ip:'",
        ] as $needle) {
            $this->assertStringContainsString($needle, $provider);
        }

        $this->assertStringContainsString(
            'throttle:saved-view-retention-summary-cache-diagnostics-refresh',
            $routes
        );

        foreach ([
            'DB::',
            'Cache::',
            'ReportSavedViewShareActivityRetentionExecution',
            'summaryCacheDiagnostics()',
        ] as $needle) {
            $this->assertStringNotContainsString($needle, $provider);
        }
    }
}
