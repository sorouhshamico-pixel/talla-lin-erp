<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportSavedViewShareActivityRetentionExecutionHistoryExportController;
use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryExportService;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewPhase87CRetentionExecutionHistoryExportFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-87c-retention-execution-history-export-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-87c-retention-execution-history-export-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 87C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '0dfd2baf8a34387081f4044db2971de1422c63c7',
            $document['baseline']['commit']
        );
        $this->assertSame(1813, $document['baseline']['tests']);
        $this->assertSame(16354, $document['baseline']['assertions']);
    }

    public function test_phase_is_documentation_and_tests_only(): void
    {
        $scope = $this->document()['scope'];

        foreach ([
            'runtime_changes_expected',
            'database_changes_expected',
            'migration_changes_expected',
            'model_changes_expected',
            'service_changes_expected',
            'controller_changes_expected',
            'route_changes_expected',
            'view_changes_expected',
        ] as $key) {
            $this->assertFalse($scope[$key], $key);
        }

        $this->assertTrue($scope['documentation_and_tests_only']);
    }

    public function test_controller_service_routes_and_middleware_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportController::class,
            $locked['controller']
        );
        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class,
            $locked['service']
        );

        foreach ($locked['routes'] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route);
            $this->assertContains('auth', $route->gatherMiddleware());
            $this->assertContains(
                'can:manage_saved_view_share_activity_retention',
                $route->gatherMiddleware()
            );
        }
    }

    public function test_formats_filters_ordering_columns_and_limits_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(['csv', 'json'], $locked['formats']);
        $this->assertSame([
            'type',
            'status',
            'actor_user_id',
            'started_from',
            'started_to',
        ], $locked['filters']);
        $this->assertSame([
            'created_at desc',
            'id desc',
        ], $locked['ordering']);

        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::COLUMNS,
            $locked['columns']
        );
        $this->assertNotContains('context', $locked['columns']);
        $this->assertNotContains('updated_at', $locked['columns']);
        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::CSV_MAXIMUM_ROWS,
            $locked['csv']['maximum_rows']
        );
        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::JSON_MAXIMUM_ROWS,
            $locked['json']['maximum_rows']
        );
        $this->assertTrue($locked['csv']['utf8_bom']);
        $this->assertSame('CRLF', $locked['csv']['line_ending']);
        $this->assertTrue($locked['csv']['streamed_download']);
    }

    public function test_audit_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

        $this->assertTrue($locked['audit']['export_request_logged']);
        $this->assertTrue($locked['audit']['actor_user_id_logged']);
        $this->assertTrue($locked['audit']['format_logged']);
        $this->assertTrue($locked['audit']['filters_logged']);
        $this->assertTrue($locked['audit']['exported_count_logged']);
        $this->assertTrue($locked['audit']['duration_logged']);
        $this->assertFalse(
            $locked['audit']['creates_execution_history_row']
        );
        $this->assertFalse(
            $locked['audit']['creates_sharing_activity_row']
        );

        foreach ($locked['compatibility'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse(
            $document['workflow']['post_commit_full_suite']
        );
        $this->assertTrue(
            $document['workflow']
                ['successful_phase_pushed_immediately']
        );
        $this->assertSame(
            'Phase 88A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-87c-retention-execution-history-export-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
