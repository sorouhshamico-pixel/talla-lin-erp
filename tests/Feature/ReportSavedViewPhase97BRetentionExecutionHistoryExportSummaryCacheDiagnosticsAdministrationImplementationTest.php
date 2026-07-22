<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportSavedViewShareActivityRetentionAdminController;
use App\Services\ReportSavedViewShareActivityRetentionAdminService;
use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Mockery;
use Tests\TestCase;

class ReportSavedViewPhase97BRetentionExecutionHistoryExportSummaryCacheDiagnosticsAdministrationImplementationTest
    extends TestCase
{
    public function test_html_index_passes_diagnostics_and_existing_summary_to_view(): void
    {
        $status = $this->retentionStatus();
        $filters = ['status' => 'failed'];
        $summary = $this->summary();
        $diagnostics = $this->diagnostics('cache', true, true);

        $admin = Mockery::mock(
            ReportSavedViewShareActivityRetentionAdminService::class
        );
        $admin->shouldReceive('status')
            ->once()
            ->andReturn($status);

        $export = Mockery::mock(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        );
        $export->shouldReceive('validatedFilters')
            ->once()
            ->with([
                'status' => 'failed',
            ])
            ->andReturn($filters);
        $export->shouldReceive('summary')
            ->once()
            ->with($filters)
            ->andReturn($summary);
        $export->shouldReceive('summaryCacheDiagnostics')
            ->once()
            ->andReturn($diagnostics);

        $request = Request::create(
            '/reports/saved-view-share-activity-retention',
            'GET',
            ['status' => 'failed']
        );

        $response = (new ReportSavedViewShareActivityRetentionAdminController())
            ->index($request, $admin, $export);

        $this->assertInstanceOf(View::class, $response);
        $this->assertSame(
            'reports.saved-views.share-activity-retention',
            $response->name()
        );
        $this->assertSame($status, $response->getData()['status']);
        $this->assertSame(
            $summary,
            $response->getData()['exportSummary']
        );
        $this->assertSame(
            $diagnostics,
            $response->getData()['exportSummaryCacheDiagnostics']
        );
    }

    public function test_json_index_preserves_status_payload_and_skips_summary_and_diagnostics(): void
    {
        $status = $this->retentionStatus();

        $admin = Mockery::mock(
            ReportSavedViewShareActivityRetentionAdminService::class
        );
        $admin->shouldReceive('status')
            ->once()
            ->andReturn($status);

        $export = Mockery::mock(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        );
        $export->shouldNotReceive('validatedFilters');
        $export->shouldNotReceive('summary');
        $export->shouldNotReceive('summaryCacheDiagnostics');

        $request = Request::create(
            '/reports/saved-view-share-activity-retention',
            'GET'
        );
        $request->headers->set('Accept', 'application/json');

        $response = (new ReportSavedViewShareActivityRetentionAdminController())
            ->index($request, $admin, $export);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame($status, $response->getData(true));
        $this->assertArrayNotHasKey(
            'exportSummaryCacheDiagnostics',
            $response->getData(true)
        );
    }

    public function test_view_renders_healthy_cache_diagnostics(): void
    {
        $html = view(
            'reports.saved-views.share-activity-retention',
            [
                'status' => $this->retentionStatus(),
                'exportFilters' => [],
                'exportSummary' => $this->summary(),
                'exportSummaryCacheDiagnostics' =>
                    $this->diagnostics('cache', true, true),
            ]
        )->render();

        $this->assertStringContainsString(
            'Summary cache diagnostics',
            $html
        );
        $this->assertStringContainsString(
            'The generated cache version is available.',
            $html
        );
        $this->assertStringContainsString('Available', $html);
        $this->assertStringContainsString('Present', $html);
        $this->assertStringContainsString('Enabled', $html);
        $this->assertStringContainsString(
            'reports:saved-view-retention:execution-history-summary:v1',
            $html
        );
    }

    public function test_view_renders_fallback_warning_without_sensitive_values(): void
    {
        $diagnostics = $this->diagnostics(
            'fallback',
            false,
            false
        );

        $html = view(
            'reports.saved-views.share-activity-retention',
            [
                'status' => $this->retentionStatus(),
                'exportFilters' => [],
                'exportSummary' => $this->summary(),
                'exportSummaryCacheDiagnostics' => $diagnostics,
            ]
        )->render();

        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString(
            'cache store could not be read',
            $html
        );
        $this->assertStringContainsString('Unavailable', $html);
        $this->assertStringContainsString('Missing', $html);
        $this->assertStringNotContainsString(
            'sensitive-generation-token',
            $html
        );
    }

    public function test_controller_and_view_source_guards_lock_scope_and_privacy(): void
    {
        $controllerSource = file_get_contents(
            app_path(
                'Http/Controllers/'
                . 'ReportSavedViewShareActivityRetentionAdminController.php'
            )
        );
        $viewSource = file_get_contents(
            resource_path(
                'views/reports/saved-views/'
                . 'share-activity-retention.blade.php'
            )
        );

        $this->assertIsString($controllerSource);
        $this->assertIsString($viewSource);

        $jsonBranch = substr(
            $controllerSource,
            strpos($controllerSource, 'if ($request->expectsJson())'),
            strpos($controllerSource, '$filters =', strpos(
                $controllerSource,
                'if ($request->expectsJson())'
            )) - strpos(
                $controllerSource,
                'if ($request->expectsJson())'
            )
        );

        $this->assertStringNotContainsString(
            'summaryCacheDiagnostics',
            $jsonBranch
        );
        $this->assertStringContainsString(
            "'exportSummaryCacheDiagnostics' =>",
            $controllerSource
        );
        $this->assertStringContainsString(
            'Summary cache diagnostics',
            $viewSource
        );
        $this->assertStringNotContainsString(
            "['generation_token']",
            $viewSource
        );
        $this->assertStringNotContainsString(
            "['raw_cache_key']",
            $viewSource
        );
        $this->assertStringNotContainsString('<form', substr(
            $viewSource,
            strpos(
                $viewSource,
                'retention-summary-cache-diagnostics-heading'
            )
        ));
    }

    private function retentionStatus(): array
    {
        return [
            'retention_enabled' => true,
            'retention_days' => 365,
            'chunk_size' => 500,
            'schedule' => 'daily',
            'candidate_count' => 0,
            'oldest_activity_at' => null,
            'newest_activity_at' => null,
        ];
    }

    private function summary(): array
    {
        return [
            'total_count' => 0,
            'succeeded_count' => 0,
            'failed_count' => 0,
            'conflicted_count' => 0,
            'manual_preview_count' => 0,
            'manual_execution_count' => 0,
            'scheduled_execution_count' => 0,
            'command_execution_count' => 0,
            'candidate_count_sum' => 0,
            'deleted_count_sum' => 0,
            'average_duration_ms' => null,
            'oldest_started_at' => null,
            'newest_started_at' => null,
        ];
    }

    private function diagnostics(
        string $source,
        bool $readAvailable,
        bool $generationPresent
    ): array {
        return [
            'cache_key_prefix' =>
                'reports:saved-view-retention:execution-history-summary:v1',
            'summary_ttl_seconds' => 30,
            'generation_key_prefix' =>
                'reports:saved-view-retention:execution-history-summary:generation:v1',
            'generation_ttl_seconds' => 86400,
            'generation_present' => $generationPresent,
            'generation_source' => $source,
            'cache_store' => 'array',
            'cache_read_available' => $readAvailable,
            'observability_enabled' => true,
        ];
    }
}
