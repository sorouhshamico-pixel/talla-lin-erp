<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewPhase83CSharingActivityExportFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-83c-saved-view-sharing-activity-export-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-83c-saved-view-sharing-activity-export-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame(
            'Phase 83C',
            $document['phase']
        );
        $this->assertSame(
            'finalization',
            $document['type']
        );
        $this->assertSame(
            '5b56257',
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

    public function test_export_routes_and_services_are_locked(): void
    {
        $locked = $this->document()
            ['locked_implementation'];

        $this->assertSame(
            'App\\Services\\ReportSavedViewShareActivityExportService',
            $locked['service']
        );
        $this->assertSame(
            'App\\Http\\Controllers\\ReportSavedViewShareActivityExportController',
            $locked['controller']
        );

        foreach (
            $locked['routes']
            as $routeName
        ) {
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

    public function test_csv_format_columns_and_streaming_are_locked(): void
    {
        $locked = $this->document()
            ['locked_implementation'];

        $this->assertSame(
            'csv',
            $locked['format']
        );
        $this->assertSame(
            'UTF-8 with BOM',
            $locked['encoding']
        );
        $this->assertSame(
            ',',
            $locked['delimiter']
        );
        $this->assertSame(
            'LF',
            $locked['line_ending']
        );
        $this->assertTrue(
            $locked['streaming']['streamed_response']
        );
        $this->assertTrue(
            $locked['streaming']['cursor_iteration']
        );
        $this->assertFalse(
            $locked['streaming']['unbounded_collection_loading']
        );

        $this->assertCount(
            15,
            $locked['columns']
        );
        $this->assertSame(
            'activity_id',
            $locked['columns'][0]
        );
        $this->assertSame(
            'copied_saved_view_id',
            $locked['columns'][14]
        );
    }

    public function test_scope_filters_metadata_and_compatibility_are_locked(): void
    {
        $locked = $this->document()
            ['locked_implementation'];

        $this->assertSame(
            'owner_user_id equals authenticated user id',
            $locked['audience_scopes']['owner']
        );
        $this->assertSame(
            'recipient_user_id equals authenticated user id',
            $locked['audience_scopes']['recipient']
        );

        $this->assertContains(
            'recipient_user_id',
            $locked['filters']['owner']
        );
        $this->assertNotContains(
            'recipient_user_id',
            $locked['filters']['recipient']
        );

        $this->assertFalse(
            $locked['metadata_policy']['full_metadata_exported']
        );
        $this->assertFalse(
            $locked['metadata_policy']['filters_payload_exported']
        );
        $this->assertTrue(
            $locked['metadata_policy']['copied_saved_view_id_extracted']
        );

        foreach (
            $locked['compatibility']
            as $key => $value
        ) {
            $this->assertTrue(
                $value,
                $key
            );
        }
    }

    public function test_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $workflow = $document['workflow'];

        $this->assertSame(
            'once before commit',
            $workflow['full_suite_runs']
        );
        $this->assertFalse(
            $workflow['post_commit_full_suite']
        );
        $this->assertTrue(
            $workflow['successful_phase_pushed_immediately']
        );
        $this->assertSame(
            'Phase 84A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-83c-saved-view-sharing-activity-export-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
