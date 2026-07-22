<?php

namespace Tests\Feature;

use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryExportService;
use Tests\TestCase;

class ReportSavedViewPhase93CRetentionExecutionHistoryExportSummaryCachingFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-93c-retention-execution-history-export-summary-caching-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-93c-retention-execution-history-export-summary-caching-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 93C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '002ebb57ffe0ecbf9eba0af740bb2c567e077d95',
            $document['baseline']['commit']
        );
        $this->assertSame(1903, $document['baseline']['tests']);
        $this->assertSame(17352, $document['baseline']['assertions']);
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

    public function test_cache_policy_key_and_request_behavior_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_TTL_SECONDS,
            $locked['cache_policy']['ttl_seconds']
        );
        $this->assertSame(
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_KEY_PREFIX,
            $locked['cache_key']['prefix']
        );

        foreach ($locked['cache_key'] as $key => $value) {
            if ($key === 'prefix') {
                continue;
            }

            $this->assertTrue($value, $key);
        }

        $this->assertSame([
            'type',
            'status',
            'actor_user_id',
            'started_from',
            'started_to',
        ], $locked['filters']);

        $this->assertSame(
            1,
            $locked['request_behavior']['cache_miss_summary_queries']
        );
        $this->assertSame(
            0,
            $locked['request_behavior']['cache_hit_summary_queries']
        );
        $this->assertTrue(
            $locked['request_behavior']
                ['json_status_request_skips_summary_cache']
        );
    }

    public function test_failure_cache_value_performance_and_scope_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        foreach ($locked['failure_behavior'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($locked['cache_value'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertSame(
            0,
            $locked['performance']['cache_key_generation_queries']
        );

        foreach ($locked['performance'] as $key => $value) {
            if ($key === 'cache_key_generation_queries') {
                continue;
            }

            $this->assertTrue($value, $key);
        }

        $this->assertTrue(
            $locked['implementation_scope']
                ['existing_export_service_modified']
        );
        $this->assertTrue(
            $locked['implementation_scope']['phase_93b_test_added']
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
            $document['workflow']
                ['successful_phase_pushed_immediately']
        );
        $this->assertSame(
            'Phase 94A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-93c-retention-execution-history-export-summary-caching-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
