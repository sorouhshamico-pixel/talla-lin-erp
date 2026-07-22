<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase95ARetentionExecutionHistoryExportSummaryCacheObservabilityContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-95a-retention-execution-history-export-summary-cache-observability-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-95a-retention-execution-history-export-summary-cache-observability-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 95A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '44a3c29bfada9990afe829c9c9c042d3c70f80f6',
            $document['baseline']['commit']
        );
        $this->assertSame(1924, $document['baseline']['tests']);
        $this->assertSame(17587, $document['baseline']['assertions']);
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

    public function test_events_logging_policy_and_context_are_locked(): void
    {
        $contract = $this->document()['observability_contract'];

        $this->assertSame([
            'summary_cache_hit',
            'summary_cache_miss',
            'summary_cache_fallback',
            'generation_read_fallback',
            'generation_rotated',
            'generation_rotation_failed',
        ], array_keys($contract['events']));

        $this->assertSame(
            'default application log',
            $contract['logging_policy']['channel']
        );
        $this->assertTrue(
            $contract['logging_policy']['structured_context_required']
        );
        $this->assertFalse(
            $contract['logging_policy']['sampling_required']
        );

        $this->assertContains('filter_count', $contract['allowed_context']);
        $this->assertContains('raw_filters', $contract['forbidden_context']);
        $this->assertContains('generation_token', $contract['forbidden_context']);
        $this->assertContains('actor_user_id', $contract['forbidden_context']);
    }

    public function test_behavior_metrics_and_performance_are_locked(): void
    {
        $contract = $this->document()['observability_contract'];

        foreach ($contract['behavior'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertTrue(
            $contract['metrics']['log_based_observability_only']
        );

        foreach ([
            'database_table_required',
            'database_counter_required',
            'cache_counter_required',
            'external_metrics_backend_required',
        ] as $key) {
            $this->assertFalse($contract['metrics'][$key], $key);
        }

        $this->assertSame(
            0,
            $contract['performance']['additional_database_queries']
        );
        $this->assertSame(
            0,
            $contract['performance']['additional_model_hydration']
        );
        $this->assertSame(
            0,
            $contract['performance']['summary_cache_hit_queries_remain']
        );
        $this->assertSame(
            1,
            $contract['performance']['summary_cache_miss_queries_remain']
        );
    }

    public function test_scope_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['observability_contract'];

        foreach ([
            'controller_changes_expected',
            'view_changes_expected',
            'route_changes_expected',
            'database_changes_expected',
            'migration_changes_expected',
        ] as $key) {
            $this->assertFalse(
                $contract['planned_implementation'][$key],
                $key
            );
        }

        foreach ($contract['compatibility'] as $key => $value) {
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
            'Phase 95B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-95a-retention-execution-history-export-summary-cache-observability-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
