<?php

namespace Tests\Feature;

use App\Models\ReportSavedViewShareActivityRetentionExecution;
use App\Models\User;
use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReportSavedViewPhase87BRetentionExecutionHistoryExportImplementationTest
    extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::define(
            'manage_saved_view_share_activity_retention',
            fn (User $user): bool => true
        );
    }

    public function test_export_routes_require_authentication(): void
    {
        $this->get(route(
            'reports.saved-view-share-activity-retention.history.export.csv'
        ))->assertRedirect(route('login'));

        $this->get(route(
            'reports.saved-view-share-activity-retention.history.export.json'
        ))->assertRedirect(route('login'));
    }

    public function test_json_export_filters_orders_limits_fields_and_logs_request(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(
                fn (string $message, array $context): bool =>
                    $message === 'Saved view retention execution history exported.'
                    && $context['format'] === 'json'
                    && $context['exported_count'] === 2
                    && isset($context['duration_ms'])
            );

        $actor = User::factory()->create();

        $older = $this->execution([
            'type' => ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_EXECUTION,
            'status' => ReportSavedViewShareActivityRetentionExecution::STATUS_SUCCEEDED,
            'actor_user_id' => $actor->id,
            'created_at' => now()->subMinutes(2),
            'started_at' => now()->subMinutes(3),
        ]);

        $newer = $this->execution([
            'type' => ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_EXECUTION,
            'status' => ReportSavedViewShareActivityRetentionExecution::STATUS_SUCCEEDED,
            'actor_user_id' => $actor->id,
            'created_at' => now()->subMinute(),
            'started_at' => now()->subMinutes(2),
        ]);

        $this->execution([
            'type' => ReportSavedViewShareActivityRetentionExecution::TYPE_COMMAND_EXECUTION,
            'status' => ReportSavedViewShareActivityRetentionExecution::STATUS_FAILED,
            'actor_user_id' => null,
        ]);

        $response = $this
            ->actingAs($actor)
            ->getJson(route(
                'reports.saved-view-share-activity-retention.history.export.json',
                [
                    'type' => ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_EXECUTION,
                    'status' => ReportSavedViewShareActivityRetentionExecution::STATUS_SUCCEEDED,
                    'actor_user_id' => $actor->id,
                ]
            ));

        $response
            ->assertOk()
            ->assertJsonStructure([
                'exported_at',
                'filters',
                'count',
                'items',
            ])
            ->assertJsonPath('count', 2)
            ->assertJsonPath('items.0.id', $newer->id)
            ->assertJsonPath('items.1.id', $older->id);

        $this->assertArrayNotHasKey(
            'context',
            $response->json('items.0')
        );
        $this->assertArrayNotHasKey(
            'updated_at',
            $response->json('items.0')
        );
        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::COLUMNS,
            array_keys($response->json('items.0'))
        );

        $this->assertDatabaseCount(
            'report_saved_view_share_activity_retention_executions',
            3
        );
    }

    public function test_csv_export_has_bom_headers_crlf_and_excludes_private_fields(): void
    {
        $actor = User::factory()->create();

        $execution = $this->execution([
            'actor_user_id' => $actor->id,
            'failure_message' => 'safe failure message',
            'context' => ['secret' => 'must-not-export'],
        ]);

        $response = $this
            ->actingAs($actor)
            ->get(route(
                'reports.saved-view-share-activity-retention.history.export.csv'
            ));

        $response->assertOk();
        $this->assertStringStartsWith(
            'text/csv',
            (string) $response->headers->get('Content-Type')
        );
        $this->assertStringContainsString(
            'saved-view-retention-execution-history-',
            (string) $response->headers->get('Content-Disposition')
        );

        ob_start();
        $response->sendContent();
        $content = (string) ob_get_clean();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString(
            implode(',', ReportSavedViewShareActivityRetentionExecutionHistoryExportService::COLUMNS),
            $content
        );
        $this->assertStringContainsString("\r\n", $content);
        $this->assertStringContainsString((string) $execution->id, $content);
        $this->assertStringContainsString('safe failure message', $content);
        $this->assertStringNotContainsString('must-not-export', $content);
        $this->assertStringNotContainsString('context', $content);
        $this->assertStringNotContainsString('updated_at', $content);
    }

    public function test_invalid_filter_is_rejected_by_export_service_validation(): void
    {
        $service = app(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class
        );

        try {
            $service->validatedFilters([
                'status' => 'invalid-status',
            ]);

            $this->fail('Invalid status was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'status',
                $exception->errors()
            );
        }
    }

    public function test_service_locks_limits_columns_and_ordering(): void
    {
        $this->assertSame(
            100000,
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::CSV_MAXIMUM_ROWS
        );
        $this->assertSame(
            10000,
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::JSON_MAXIMUM_ROWS
        );
        $this->assertNotContains(
            'context',
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::COLUMNS
        );
        $this->assertNotContains(
            'updated_at',
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::COLUMNS
        );

        $source = file_get_contents(
            app_path(
                'Services/ReportSavedViewShareActivityRetentionExecutionHistoryExportService.php'
            )
        );

        $this->assertStringContainsString(
            "->orderByDesc('created_at')",
            $source
        );
        $this->assertStringContainsString(
            "->orderByDesc('id')",
            $source
        );
    }

    private function execution(array $overrides = []): ReportSavedViewShareActivityRetentionExecution
    {
        return ReportSavedViewShareActivityRetentionExecution::query()->create(
            array_merge([
                'type' => ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_PREVIEW,
                'status' => ReportSavedViewShareActivityRetentionExecution::STATUS_SUCCEEDED,
                'actor_user_id' => null,
                'requested_days' => 30,
                'requested_chunk_size' => 500,
                'candidate_count' => 4,
                'deleted_count' => 0,
                'cutoff_at' => now()->subDays(30),
                'duration_ms' => 12,
                'failure_class' => null,
                'failure_message' => null,
                'context' => ['internal' => true],
                'started_at' => now()->subMinute(),
                'finished_at' => now(),
            ], $overrides)
        );
    }
}
