<?php

namespace Tests\Feature;

use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryExportService;
use Tests\TestCase;

class ReportSavedViewPhase94CRetentionExecutionHistoryExportSummaryCacheInvalidationFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-94c-retention-execution-history-export-summary-cache-invalidation-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-94c-retention-execution-history-export-summary-cache-invalidation-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 94C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            'db55ad131f7d13114c56d4364706c6a88df3dafc',
            $document['baseline']['commit']
        );
        $this->assertSame(1919, $document['baseline']['tests']);
        $this->assertSame(17515, $document['baseline']['assertions']);
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

    public function test_generation_and_write_behavior_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_GENERATION_KEY,
            $locked['generation']['key']
        );
        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_GENERATION_TTL_SECONDS,
            $locked['generation']['ttl_seconds']
        );
        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_DEFAULT_GENERATION,
            $locked['generation']['default_generation']
        );

        foreach ($locked['write_behavior'] as $key => $value) {
            $this->assertTrue($value, $key);
        }
    }

    public function test_read_cache_privacy_and_performance_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            0,
            $locked['read_behavior']['cache_hit_summary_queries']
        );
        $this->assertSame(
            1,
            $locked['read_behavior']['cache_miss_summary_queries']
        );

        foreach ($locked['read_behavior'] as $key => $value) {
            if (
                in_array(
                    $key,
                    [
                        'cache_hit_summary_queries',
                        'cache_miss_summary_queries',
                    ],
                    true
                )
            ) {
                continue;
            }

            $this->assertTrue($value, $key);
        }

        $this->assertTrue(
            $locked['cache_operations']['cache_put_used_for_generation']
        );
        $this->assertTrue(
            $locked['cache_operations']['cache_flush_forbidden']
        );
        $this->assertFalse(
            $locked['cache_operations']['cache_tags_required']
        );
        $this->assertTrue(
            $locked['cache_operations']
                ['filter_key_enumeration_forbidden']
        );
        $this->assertTrue(
            $locked['cache_operations']
                ['old_entries_expire_by_existing_summary_ttl']
        );

        foreach ($locked['privacy'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertSame(
            0,
            $locked['performance']['generation_cache_queries']
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
            $locked['implementation_scope']['history_service_modified']
        );
        $this->assertTrue(
            $locked['implementation_scope']
                ['phase_93b_regression_test_updated']
        );
        $this->assertTrue(
            $locked['implementation_scope']['phase_94b_test_added']
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
            'Phase 95A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-94c-retention-execution-history-export-summary-cache-invalidation-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
