<?php

namespace Tests\Feature;

use App\Http\Controllers\Reports\SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealthController;
use App\Support\SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealth;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Route;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ReportSavedViewPhase107BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthExposureImplementationTest
    extends TestCase
{
    private const ROUTE_NAME =
        'reports.saved-view-share-activity-retention.'
        . 'summary-cache-diagnostics.audit-metrics-health';

    public function test_route_contract_is_registered_exactly_once(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(
                static fn (Route $route): bool =>
                    $route->getName() === self::ROUTE_NAME
            )
            ->values();

        $this->assertCount(1, $routes);

        $route = $routes->first();

        $this->assertSame(
            'reports/saved-view-share-activity-retention/'
            . 'summary-cache-diagnostics/audit-metrics-health',
            $route->uri()
        );
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertSame(
            SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealthController::class,
            $route->getActionName()
        );
        $this->assertSame(
            [
                'web',
                'auth',
                'can:manage_saved_view_share_activity_retention',
            ],
            $route->gatherMiddleware()
        );
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route(self::ROUTE_NAME))
            ->assertRedirect(route('login'));
    }

    public function test_controller_returns_exact_healthy_status_without_wrapping(): void
    {
        $dispatcher = Mockery::mock(Dispatcher::class);

        $dispatcher->shouldReceive('getListeners')
            ->once()
            ->andReturn([static fn () => null]);

        $response = $this->controller()(
            new SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealth(
                $dispatcher
            )
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            'application/json',
            $response->headers->get('Content-Type')
        );
        $this->assertSame(
            [
                'listener_discovered' => true,
                'listener_count' => 1,
                'channel_configured' => true,
                'channel_driver' => 'daily',
                'channel_level' => 'info',
                'channel_retention_days' => 14,
                'channel_path_matches' => true,
                'healthy' => true,
            ],
            $response->getData(true)
        );
    }

    public function test_controller_returns_locked_unhealthy_status_with_http_200(): void
    {
        $dispatcher = Mockery::mock(Dispatcher::class);

        $dispatcher->shouldReceive('getListeners')
            ->once()
            ->andThrow(new RuntimeException('discovery unavailable'));

        $response = $this->controller()(
            new SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealth(
                $dispatcher
            )
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            [
                'listener_discovered' => false,
                'listener_count' => 0,
                'channel_configured' => false,
                'channel_driver' => null,
                'channel_level' => null,
                'channel_retention_days' => null,
                'channel_path_matches' => false,
                'healthy' => false,
            ],
            $response->getData(true)
        );
    }

    public function test_controller_source_is_thin_and_privacy_safe(): void
    {
        $source = file_get_contents(
            app_path(
                'Http/Controllers/Reports/'
                . 'SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealthController.php'
            )
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'return response()->json($health->status());',
            $source
        );

        foreach ([
            'try {',
            'catch (',
            'event(',
            'Event::',
            'Log::',
            'logger(',
            'DB::',
            'Cache::',
            'file_get_contents(',
            'request(',
            'auth(',
            'correlation',
            'user_id',
            'ip_address',
            'session',
            'headers',
            'cookies',
            'retry_after',
            'sampling_bucket',
            'diagnostics_payload',
            'cache_key',
        ] as $needle) {
            $this->assertStringNotContainsString($needle, $source);
        }
    }

    public function test_exact_route_block_has_no_extra_middleware(): void
    {
        $source = file_get_contents(base_path('routes/web.php'));

        $this->assertIsString($source);

        $nameNeedle =
            "'summary-cache-diagnostics.audit-metrics-health'";

        $namePosition = strpos($source, $nameNeedle);

        $this->assertNotFalse($namePosition);

        $routeStart = strrpos(
            substr($source, 0, $namePosition),
            'Route::get('
        );

        $this->assertNotFalse($routeStart);

        $routeEnd = strpos($source, "\n    );", $namePosition);

        $this->assertNotFalse($routeEnd);

        $routeBlock = substr(
            $source,
            $routeStart,
            $routeEnd - $routeStart + strlen("\n    );")
        );

        $this->assertStringContainsString(
            'SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealthController::class',
            $routeBlock
        );
        $this->assertStringNotContainsString(
            '->middleware(',
            $routeBlock
        );
        $this->assertStringNotContainsString(
            'throttle:',
            $routeBlock
        );
        $this->assertStringNotContainsString(
            'audit.saved-view-retention',
            $routeBlock
        );
        $this->assertStringNotContainsString(
            'permission:',
            $routeBlock
        );
    }

    private function controller():
        SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealthController
    {
        return app(
            SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealthController::class
        );
    }
}
