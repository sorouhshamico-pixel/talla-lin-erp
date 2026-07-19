<?php

namespace Tests\Feature;

use App\Models\ReportSavedViewShareActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportSavedViewPhase82BSharingActivityFinalizationTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-82b-saved-view-sharing-activity-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-82b-saved-view-sharing-activity-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame(
            'Phase 82B Stage 5',
            $document['phase']
        );
        $this->assertSame(
            'finalization',
            $document['type']
        );
        $this->assertSame(
            'f719301',
            $document['baseline']['commit']
        );
    }

    public function test_finalization_is_documentation_and_tests_only(): void
    {
        $scope = $this->document()['scope'];

        foreach ([
            'runtime_changes_expected',
            'database_changes_expected',
            'migration_changes_expected',
            'route_changes_expected',
            'controller_changes_expected',
            'service_changes_expected',
            'view_changes_expected',
        ] as $key) {
            $this->assertFalse(
                $scope[$key],
                $key
            );
        }

        $this->assertTrue(
            $scope['documentation_and_tests_only']
        );
    }

    public function test_activity_schema_model_and_actions_are_locked(): void
    {
        $this->assertTrue(
            Schema::hasTable(
                'report_saved_view_share_activities'
            )
        );

        $this->assertSame(
            [
                'shared',
                'permission_updated',
                'revoked',
                'applied',
                'copied',
                'source_archived',
                'source_restored',
                'source_deleted',
            ],
            ReportSavedViewShareActivity::ACTIONS
        );

        $locked = $this->document()
            ['locked_implementation'];

        $this->assertSame(
            'report_saved_view_share_activities',
            $locked['table']
        );
        $this->assertTrue(
            $locked['write_semantics']['immutable_rows']
        );
        $this->assertTrue(
            $locked['retention']['survives_share_delete']
        );
        $this->assertTrue(
            $locked['retention']['survives_saved_view_delete']
        );
    }

    public function test_owner_and_recipient_routes_are_locked(): void
    {
        foreach ([
            'reports.saved-view-share-activities.owner.index',
            'reports.shared-saved-view-activities.index',
        ] as $routeName) {
            $route = Route::getRoutes()
                ->getByName($routeName);

            $this->assertNotNull($route);
            $this->assertContains(
                'GET',
                $route->methods()
            );
            $this->assertContains(
                'auth',
                $route->gatherMiddleware()
            );
        }
    }

    public function test_read_scope_pagination_and_boundaries_are_locked(): void
    {
        $locked = $this->document()
            ['locked_implementation'];

        $this->assertTrue(
            $locked['owner_history']['strict_owner_scope']
        );
        $this->assertTrue(
            $locked['recipient_history']['strict_recipient_scope']
        );
        $this->assertSame(
            25,
            $locked['pagination']['default']
        );
        $this->assertSame(
            100,
            $locked['pagination']['maximum']
        );

        foreach ([
            'sharing_permissions_unchanged',
            'archive_behavior_unchanged',
            'copy_behavior_unchanged',
            'tags_behavior_unchanged',
            'csv_import_export_unchanged',
            'format_version_unchanged',
        ] as $key) {
            $this->assertTrue(
                $locked['boundaries'][$key],
                $key
            );
        }
    }

    public function test_workflow_policy_and_next_phase_are_locked(): void
    {
        $document = $this->document();

        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse(
            $document['workflow']['post_commit_full_suite']
        );
        $this->assertTrue(
            $document['workflow']['successful_phase_pushed_immediately']
        );
        $this->assertSame(
            'Phase 83A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-82b-saved-view-sharing-activity-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
