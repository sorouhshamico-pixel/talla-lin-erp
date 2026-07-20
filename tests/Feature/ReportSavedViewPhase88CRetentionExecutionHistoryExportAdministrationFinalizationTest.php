<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewPhase88CRetentionExecutionHistoryExportAdministrationFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-88c-retention-execution-history-export-administration-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-88c-retention-execution-history-export-administration-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 88C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '45bff7e7127dd85fd2701b3f5805e924d9faa34b',
            $document['baseline']['commit']
        );
        $this->assertSame(1827, $document['baseline']['tests']);
        $this->assertSame(16504, $document['baseline']['assertions']);
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

    public function test_administration_routes_controls_and_permission_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'manage_saved_view_share_activity_retention',
            $locked['permission_required']
        );
        $this->assertTrue($locked['authentication_required']);
        $this->assertSame('GET', $locked['controls']['method']);
        $this->assertSame(
            ['csv', 'json'],
            $locked['controls']['formats']
        );
        $this->assertSame(
            [
                'type',
                'status',
                'actor_user_id',
                'started_from',
                'started_to',
            ],
            $locked['controls']['filters']
        );
        $this->assertTrue(
            $locked['controls']['clear_filters_action']
        );
        $this->assertTrue($locked['controls']['non_mutating']);

        foreach ($locked['export_routes'] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route);
            $this->assertContains('auth', $route->gatherMiddleware());
            $this->assertContains(
                'can:manage_saved_view_share_activity_retention',
                $route->gatherMiddleware()
            );
        }
    }

    public function test_presentation_scope_and_safety_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            100000,
            $locked['presentation']['csv_maximum_rows']
        );
        $this->assertSame(
            10000,
            $locked['presentation']['json_maximum_rows']
        );
        $this->assertTrue(
            $locked['presentation']['context_exclusion_visible']
        );
        $this->assertTrue(
            $locked['presentation']['updated_at_exclusion_visible']
        );
        $this->assertTrue(
            $locked['implementation_scope']['view_modified']
        );
        $this->assertTrue(
            $locked['implementation_scope']
                ['phase_85b_regression_test_updated']
        );
        $this->assertTrue(
            $locked['implementation_scope']['phase_88b_test_added']
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

        foreach ($locked['safety'] as $key => $value) {
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
            'Phase 89A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-88c-retention-execution-history-export-administration-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
