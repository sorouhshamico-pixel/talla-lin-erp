<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewPhase89CRetentionExecutionHistoryExportPresetsFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-89c-retention-execution-history-export-presets-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-89c-retention-execution-history-export-presets-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 89C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            'b2f6d4c3c43630ada934554c230e32c7352f8092',
            $document['baseline']['commit']
        );
        $this->assertSame(1842, $document['baseline']['tests']);
        $this->assertSame(16670, $document['baseline']['assertions']);
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

    public function test_presets_and_route_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'reports.saved-view-share-activity-retention.index',
            $locked['administration_route']
        );

        $route = Route::getRoutes()->getByName(
            $locked['administration_route']
        );

        $this->assertNotNull($route);
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains(
            'can:manage_saved_view_share_activity_retention',
            $route->gatherMiddleware()
        );

        $this->assertSame(
            [
                'all',
                'failed',
                'conflicted',
                'manual',
                'scheduled',
                'command',
            ],
            array_column($locked['presets'], 'key')
        );
    }

    public function test_behavior_persistence_scope_and_privacy_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        foreach ($locked['behavior'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($locked['persistence'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        $this->assertTrue(
            $locked['implementation_scope']['view_modified']
        );
        $this->assertTrue(
            $locked['implementation_scope']['phase_89b_test_added']
        );

        foreach ([
            'controller_changed',
            'service_changed',
            'route_changed',
            'database_changed',
            'migration_changed',
        ] as $key) {
            $this->assertFalse(
                $locked['implementation_scope'][$key],
                $key
            );
        }

        foreach ($locked['privacy'] as $key => $value) {
            $this->assertTrue($value, $key);
        }
    }

    public function test_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

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
            'Phase 90A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-89c-retention-execution-history-export-presets-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
