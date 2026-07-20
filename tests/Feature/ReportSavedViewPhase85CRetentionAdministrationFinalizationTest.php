<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportSavedViewShareActivityRetentionAdminController;
use App\Services\ReportSavedViewShareActivityRetentionAdminService;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewPhase85CRetentionAdministrationFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-85c-saved-view-sharing-activity-retention-administration-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-85c-saved-view-sharing-activity-retention-administration-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame(
            'Phase 85C',
            $document['phase']
        );
        $this->assertSame(
            'finalization',
            $document['type']
        );
        $this->assertSame(
            'fd7fbe3',
            $document['baseline']['commit']
        );
    }

    public function test_phase_is_documentation_and_tests_only(): void
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
            'provider_changes_expected',
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

    public function test_ability_controller_service_and_routes_are_locked(): void
    {
        $locked = $this->document()
            ['locked_implementation'];

        $this->assertSame(
            'manage_saved_view_share_activity_retention',
            $locked['ability']
        );
        $this->assertSame(
            ReportSavedViewShareActivityRetentionAdminController::class,
            $locked['controller']
        );
        $this->assertSame(
            ReportSavedViewShareActivityRetentionAdminService::class,
            $locked['service']
        );

        foreach (
            $locked['routes']
            as $routeName
        ) {
            $route = Route::getRoutes()
                ->getByName($routeName);

            $this->assertNotNull($route);
            $this->assertContains(
                'auth',
                $route->gatherMiddleware()
            );
            $this->assertContains(
                'can:manage_saved_view_share_activity_retention',
                $route->gatherMiddleware()
            );
        }
    }

    public function test_status_preview_and_execution_contract_are_locked(): void
    {
        $locked = $this->document()
            ['locked_implementation'];

        $this->assertTrue(
            $locked['status_interface']['supports_html']
        );
        $this->assertTrue(
            $locked['status_interface']['supports_json']
        );
        $this->assertTrue(
            $locked['status_interface']
                ['configuration_is_read_only']
        );

        $this->assertFalse(
            $locked['manual_preview']['deletes_rows']
        );
        $this->assertSame(
            30,
            $locked['manual_preview']['minimum_days']
        );
        $this->assertSame(
            3650,
            $locked['manual_preview']['maximum_days']
        );

        $this->assertSame(
            'PRUNE',
            $locked['manual_execution']
                ['confirmation_token']
        );
        $this->assertSame(
            1,
            $locked['manual_execution']
                ['minimum_chunk_size']
        );
        $this->assertSame(
            10000,
            $locked['manual_execution']
                ['maximum_chunk_size']
        );
    }

    public function test_concurrency_audit_and_response_contract_are_locked(): void
    {
        $locked = $this->document()
            ['locked_implementation'];

        $this->assertSame(
            ReportSavedViewShareActivityRetentionAdminService::LOCK_NAME,
            $locked['concurrency']['lock_name']
        );
        $this->assertSame(
            3600,
            $locked['concurrency']['lock_ttl_seconds']
        );
        $this->assertSame(
            409,
            $locked['concurrency']['conflict_status']
        );
        $this->assertTrue(
            $locked['concurrency']['overlap_forbidden']
        );

        foreach ([
            'actor_user_id',
            'requested_days',
            'requested_chunk_size',
            'candidate_count',
            'deleted_count',
            'cutoff',
            'duration_ms',
        ] as $key) {
            $this->assertContains(
                $key,
                $locked['audit_context']
            );
        }

        $this->assertSame(
            200,
            $locked['responses']['success']
        );
        $this->assertSame(
            403,
            $locked['responses']['forbidden']
        );
        $this->assertSame(
            422,
            $locked['responses']['validation_error']
        );
        $this->assertSame(
            409,
            $locked['responses']['lock_conflict']
        );
    }

    public function test_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();

        foreach (
            $document['locked_implementation']
                ['compatibility']
            as $key => $value
        ) {
            $this->assertTrue(
                $value,
                $key
            );
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
            'Phase 86A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-85c-saved-view-sharing-activity-retention-administration-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
