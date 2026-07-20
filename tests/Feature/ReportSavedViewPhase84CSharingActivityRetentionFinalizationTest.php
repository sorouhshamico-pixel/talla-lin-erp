<?php

namespace Tests\Feature;

use App\Console\Commands\PruneReportSavedViewShareActivities;
use App\Services\ReportSavedViewShareActivityRetentionService;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ReportSavedViewPhase84CSharingActivityRetentionFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-84c-saved-view-sharing-activity-retention-policy-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-84c-saved-view-sharing-activity-retention-policy-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame(
            'Phase 84C',
            $document['phase']
        );
        $this->assertSame(
            'finalization',
            $document['type']
        );
        $this->assertSame(
            'fe41d7d',
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
            'command_changes_expected',
            'scheduler_changes_expected',
            'configuration_changes_expected',
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

    public function test_service_command_and_registration_are_locked(): void
    {
        $locked = $this->document()
            ['locked_implementation'];

        $this->assertSame(
            ReportSavedViewShareActivityRetentionService::class,
            $locked['service']
        );
        $this->assertSame(
            PruneReportSavedViewShareActivities::class,
            $locked['command_class']
        );
        $this->assertSame(
            'reports:prune-saved-view-share-activities',
            $locked['command']
        );
        $this->assertSame(
            'bootstrap/app.php withCommands',
            $locked['command_registration']
        );

        $commands = Artisan::all();

        $this->assertArrayHasKey(
            'reports:prune-saved-view-share-activities',
            $commands
        );
    }

    public function test_configuration_defaults_and_bounds_are_locked(): void
    {
        $locked = $this->document()
            ['locked_implementation'];

        $configuration = $locked['configuration'];
        $bounds = $locked['retention_bounds'];

        $this->assertFalse(
            $configuration['default_enabled']
        );
        $this->assertNull(
            $configuration['default_days']
        );
        $this->assertSame(
            500,
            $configuration['default_chunk_size']
        );
        $this->assertSame(
            'daily',
            $configuration['default_schedule']
        );

        $this->assertSame(
            30,
            $bounds['minimum_days']
        );
        $this->assertSame(
            3650,
            $bounds['maximum_days']
        );
        $this->assertSame(
            1,
            $bounds['minimum_chunk_size']
        );
        $this->assertSame(
            10000,
            $bounds['maximum_chunk_size']
        );
    }

    public function test_execution_observability_and_scheduler_are_locked(): void
    {
        $locked = $this->document()
            ['locked_implementation'];

        foreach (
            $locked['execution']
            as $key => $value
        ) {
            $this->assertTrue(
                $value,
                $key
            );
        }

        $this->assertTrue(
            $locked['observability']['candidate_count_reported']
        );
        $this->assertTrue(
            $locked['observability']['deleted_count_reported']
        );
        $this->assertTrue(
            $locked['observability']['cutoff_reported']
        );
        $this->assertTrue(
            $locked['observability']['duration_reported']
        );
        $this->assertFalse(
            $locked['observability']['pruning_creates_activity_rows']
        );

        $this->assertSame(
            [
                'hourly',
                'daily',
                'weekly',
                'monthly',
            ],
            $locked['scheduler']['supported_schedules']
        );
        $this->assertSame(
            'daily',
            $locked['scheduler']['fallback_schedule']
        );
    }

    public function test_immutability_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

        $this->assertTrue(
            $locked['immutability_boundary']
                ['normal_model_updates_forbidden']
        );
        $this->assertTrue(
            $locked['immutability_boundary']
                ['normal_model_deletes_forbidden']
        );
        $this->assertTrue(
            $locked['immutability_boundary']
                ['retention_query_builder_delete_is_policy_exception']
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
            'Phase 85A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-84c-saved-view-sharing-activity-retention-policy-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
