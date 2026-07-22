<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportSavedViewShareActivityRetentionAdminController;
use App\Http\Middleware\EnsurePartyPermission;
use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class ReportSavedViewPhase98BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshImplementationTest
    extends TestCase
{
    public function test_controller_returns_existing_diagnostics_payload_directly(): void
    {
        $diagnostics = $this->diagnostics();

        $export = Mockery::mock(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        );

        $export->shouldReceive('summaryCacheDiagnostics')
            ->once()
            ->andReturn($diagnostics);

        $export->shouldNotReceive('summary');

        $response = (new ReportSavedViewShareActivityRetentionAdminController())
            ->summaryCacheDiagnostics($export);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($diagnostics, $response->getData(true));
        $this->assertArrayNotHasKey('summary', $response->getData(true));
        $this->assertArrayNotHasKey('status', $response->getData(true));
    }

    public function test_route_is_get_named_authenticated_and_permission_protected(): void
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
            EnsurePartyPermission::class
            . ':manage_saved_view_share_activity_retention',
            $middleware
        );
    }

    public function test_refresh_action_executes_zero_database_queries(): void
    {
        $export = Mockery::mock(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        );

        $export->shouldReceive('summaryCacheDiagnostics')
            ->once()
            ->andReturn($this->diagnostics());

        DB::flushQueryLog();
        DB::enableQueryLog();

        (new ReportSavedViewShareActivityRetentionAdminController())
            ->summaryCacheDiagnostics($export);

        $queries = DB::getQueryLog();

        DB::disableQueryLog();

        $this->assertCount(0, $queries);
    }

    public function test_retention_view_is_script_free_and_contains_refresh_controls(): void
    {
        $source = file_get_contents(
            resource_path(
                'views/reports/saved-views/'
                . 'share-activity-retention.blade.php'
            )
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'Refresh diagnostics',
            $source
        );
        $this->assertStringContainsString(
            'type="button"',
            $source
        );
        $this->assertStringContainsString(
            'summary-cache-diagnostics',
            $source
        );
        $this->assertStringNotContainsString('<script', $source);
        $this->assertStringNotContainsString('.innerHTML', $source);
    }

    public function test_layout_contains_safe_non_polling_refresh_script(): void
    {
        $source = file_get_contents(
            resource_path('views/layouts/app.blade.php')
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'retention-summary-cache-diagnostics-refresh',
            $source
        );
        $this->assertStringContainsString(
            "method: 'GET'",
            $source
        );
        $this->assertStringContainsString(
            "Accept: 'application/json'",
            $source
        );
        $this->assertStringContainsString(
            'refreshInProgress',
            $source
        );
        $this->assertStringContainsString(
            'button.disabled = true',
            $source
        );
        $this->assertStringContainsString(
            'button.disabled = false',
            $source
        );
        $this->assertStringContainsString(
            '.textContent',
            $source
        );
        $this->assertStringNotContainsString(
            '.innerHTML',
            $source
        );
        $this->assertStringNotContainsString(
            'setInterval(',
            $source
        );
        $this->assertStringNotContainsString(
            'setTimeout(',
            $source
        );
    }

    public function test_layout_updates_all_diagnostics_fields_without_sensitive_data(): void
    {
        $source = file_get_contents(
            resource_path('views/layouts/app.blade.php')
        );

        foreach ([
            'diagnostics-cache-store',
            'diagnostics-cache-read-available',
            'diagnostics-generation-present',
            'diagnostics-generation-source',
            'diagnostics-summary-ttl-seconds',
            'diagnostics-generation-ttl-seconds',
            'diagnostics-observability-enabled',
            'diagnostics-cache-key-prefix',
            'diagnostics-generation-key-prefix',
        ] as $id) {
            $this->assertStringContainsString($id, $source);
        }

        foreach ([
            'cache_store',
            'cache_read_available',
            'generation_present',
            'generation_source',
            'summary_ttl_seconds',
            'generation_ttl_seconds',
            'observability_enabled',
            'cache_key_prefix',
            'generation_key_prefix',
        ] as $field) {
            $this->assertStringContainsString(
                'diagnostics.' . $field,
                $source
            );
        }

        $this->assertStringNotContainsString(
            'generation_token',
            $source
        );
        $this->assertStringNotContainsString(
            'raw_cache_key',
            $source
        );
        $this->assertStringNotContainsString(
            'actor_user_id',
            $source
        );
    }

    private function diagnostics(): array
    {
        return [
            'cache_key_prefix' =>
                'reports:saved-view-retention:execution-history-summary:v1',
            'summary_ttl_seconds' => 30,
            'generation_key_prefix' =>
                'reports:saved-view-retention:execution-history-summary:generation:v1',
            'generation_ttl_seconds' => 86400,
            'generation_present' => true,
            'generation_source' => 'cache',
            'cache_store' => 'array',
            'cache_read_available' => true,
            'observability_enabled' => true,
        ];
    }
}
