<?php

namespace Tests\Feature;

use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryExportService;
use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryService;
use Tests\TestCase;

class ReportSavedViewPhase95CRetentionExecutionHistoryExportSummaryCacheObservabilityFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-95c-retention-execution-history-export-summary-cache-observability-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-95c-retention-execution-history-export-summary-cache-observability-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 95C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '98dc62ba8784985d89b84ab49bd31a0efc8646fa',
            $document['baseline']['commit']
        );
        $this->assertSame(1937, $document['baseline']['tests']);
        $this->assertSame(17679, $document['baseline']['assertions']);
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

    public function test_events_levels_and_context_policy_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_EVENT_HIT,
            $locked['events']['summary_cache_hit']
        );
        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_EVENT_MISS,
            $locked['events']['summary_cache_miss']
        );
        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_EVENT_FALLBACK,
            $locked['events']['summary_cache_fallback']
        );
        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_EVENT_GENERATION_READ_FALLBACK,
            $locked['events']['generation_read_fallback']
        );
        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryService::SUMMARY_CACHE_EVENT_GENERATION_ROTATED,
            $locked['events']['generation_rotated']
        );
        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryService::SUMMARY_CACHE_EVENT_GENERATION_ROTATION_FAILED,
            $locked['events']['generation_rotation_failed']
        );

        $this->assertSame('debug', $locked['levels']['summary_cache_hit']);
        $this->assertSame('warning', $locked['levels']['summary_cache_fallback']);

        $this->assertContains(
            'filter_count',
            $locked['allowed_context']
        );
        $this->assertContains(
            'raw_filters',
            $locked['forbidden_context']
        );
        $this->assertContains(
            'actor_user_id',
            $locked['forbidden_context']
        );
    }

    public function test_behavior_performance_and_scope_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        foreach ($locked['behavior'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertSame(
            0,
            $locked['performance']['additional_database_queries']
        );
        $this->assertSame(
            0,
            $locked['performance']['additional_model_hydration']
        );
        $this->assertSame(
            0,
            $locked['performance']['cache_hit_summary_queries']
        );
        $this->assertSame(
            1,
            $locked['performance']['cache_miss_summary_queries']
        );

        $this->assertTrue(
            $locked['implementation_scope']['export_service_modified']
        );
        $this->assertTrue(
            $locked['implementation_scope']['history_service_modified']
        );
        $this->assertTrue(
            $locked['implementation_scope']['phase_95b_test_added']
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
            $document['workflow']['successful_phase_pushed_immediately']
        );
        $this->assertSame(
            'Phase 96A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-95c-retention-execution-history-export-summary-cache-observability-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
