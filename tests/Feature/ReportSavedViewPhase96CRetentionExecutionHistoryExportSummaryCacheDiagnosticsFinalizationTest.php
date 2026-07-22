<?php

namespace Tests\Feature;

use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryExportService;
use Tests\TestCase;

class ReportSavedViewPhase96CRetentionExecutionHistoryExportSummaryCacheDiagnosticsFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-96c-retention-execution-history-export-summary-cache-diagnostics-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-96c-retention-execution-history-export-summary-cache-diagnostics-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 96C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '3e0834af1b3acd82913f90d0929ee0a18636c5a3',
            $document['baseline']['commit']
        );
        $this->assertSame(1953, $document['baseline']['tests']);
        $this->assertSame(17850, $document['baseline']['assertions']);
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

    public function test_method_shape_and_generation_sources_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'summaryCacheDiagnostics',
            $locked['method']
        );

        $this->assertSame([
            'cache_key_prefix',
            'summary_ttl_seconds',
            'generation_key_prefix',
            'generation_ttl_seconds',
            'generation_present',
            'generation_source',
            'cache_store',
            'cache_read_available',
            'observability_enabled',
        ], $locked['return_shape']);

        $this->assertSame([
            'cache',
            'default',
            'fallback',
        ], $locked['generation_sources']);

        $this->assertTrue(
            method_exists(
                ReportSavedViewShareActivityRetentionExecutionHistoryExportService::class,
                'summaryCacheDiagnostics'
            )
        );
    }

    public function test_behavior_privacy_and_performance_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        foreach ($locked['behavior'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($locked['privacy'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertSame(
            1,
            $locked['performance']['maximum_cache_reads']
        );
        $this->assertSame(
            0,
            $locked['performance']['maximum_database_queries']
        );
        $this->assertSame(
            0,
            $locked['performance']['maximum_model_hydration']
        );
        $this->assertTrue(
            $locked['performance']['constant_result_size']
        );
    }

    public function test_scope_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

        $this->assertTrue(
            $locked['implementation_scope']['export_service_modified']
        );
        $this->assertTrue(
            $locked['implementation_scope']['phase_96b_test_added']
        );

        foreach ([
            'history_service_changed',
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
        $this->assertSame(
            'Phase 97A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-96c-retention-execution-history-export-summary-cache-diagnostics-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
