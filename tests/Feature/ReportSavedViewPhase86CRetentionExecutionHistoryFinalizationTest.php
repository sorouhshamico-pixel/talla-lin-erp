<?php

namespace Tests\Feature;

use App\Models\ReportSavedViewShareActivityRetentionExecution;
use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryService;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewPhase86CRetentionExecutionHistoryFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-86c-saved-view-sharing-activity-retention-execution-history-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-86c-saved-view-sharing-activity-retention-execution-history-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 86C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame('eea711b', $document['baseline']['commit']);
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
            'command_changes_expected',
        ] as $key) {
            $this->assertFalse($scope[$key], $key);
        }

        $this->assertTrue($scope['documentation_and_tests_only']);
    }

    public function test_model_service_and_contract_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecution::class,
            $locked['model']
        );
        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryService::class,
            $locked['history_service']
        );
        $this->assertSame(
            ['succeeded', 'failed', 'conflicted'],
            $locked['statuses']
        );
        $this->assertTrue(
            $locked['immutability']['rows_append_only']
        );
        $this->assertTrue(
            $locked['immutability']['model_updates_forbidden']
        );
        $this->assertTrue(
            $locked['immutability']['model_deletes_forbidden']
        );
    }

    public function test_history_route_is_locked_and_registered(): void
    {
        $read = $this->document()
            ['locked_implementation']['read_interface'];

        $route = Route::getRoutes()->getByName($read['route']);

        $this->assertNotNull($route);
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains(
            'can:manage_saved_view_share_activity_retention',
            $route->gatherMiddleware()
        );
        $this->assertSame(25, $read['default_page_size']);
        $this->assertSame(100, $read['maximum_page_size']);
        $this->assertContains('type', $read['filters']);
        $this->assertContains('started_to', $read['filters']);
    }

    public function test_safety_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

        $this->assertTrue(
            $locked['failure_safety']
                ['history_write_failures_are_logged']
        );
        $this->assertTrue(
            $locked['failure_safety']
                ['history_write_failures_do_not_override_primary_result']
        );
        $this->assertSame(
            2000,
            $locked['failure_safety']
                ['failure_message_maximum_characters']
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
            'Phase 87A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-86c-saved-view-sharing-activity-retention-execution-history-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
