<?php

namespace Tests\Feature;

use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryExportService;
use Tests\TestCase;

class ReportSavedViewPhase92CRetentionExecutionHistoryExportSummaryPerformanceFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-92c-retention-execution-history-export-summary-performance-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-92c-retention-execution-history-export-summary-performance-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 92C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '0b765c2619508951116044c6ea3a7828e215a207',
            $document['baseline']['commit']
        );
        $this->assertSame(1887, $document['baseline']['tests']);
        $this->assertSame(17191, $document['baseline']['assertions']);
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

    public function test_performance_constants_and_query_guards_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_MAXIMUM_QUERIES,
            $locked['performance_constants']['summary_maximum_queries']
        );
        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_TIMEOUT_SECONDS,
            $locked['performance_constants']['summary_timeout_seconds']
        );

        $this->assertSame(
            0,
            $locked['query_guards']['execution_models_hydrated']
        );

        foreach ($locked['query_guards'] as $key => $value) {
            if ($key === 'execution_models_hydrated') {
                continue;
            }

            $this->assertTrue($value, $key);
        }

        foreach ($locked['aggregation'] as $key => $value) {
            $this->assertTrue($value, $key);
        }
    }

    public function test_request_behavior_scope_and_observability_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        foreach ($locked['request_behavior'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertTrue(
            $locked['implementation_scope']
                ['existing_export_service_modified']
        );
        $this->assertTrue(
            $locked['implementation_scope']['phase_92b_test_added']
        );

        foreach ([
            'controller_changed',
            'view_changed',
            'route_changed',
            'database_changed',
            'migration_changed',
            'model_changed',
        ] as $key) {
            $this->assertFalse(
                $locked['implementation_scope'][$key],
                $key
            );
        }

        foreach ($locked['observability'] as $key => $value) {
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
            'Phase 93A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-92c-retention-execution-history-export-summary-performance-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
