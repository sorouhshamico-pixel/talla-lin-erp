<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase93ARetentionExecutionHistoryExportSummaryCachingContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-93a-retention-execution-history-export-summary-caching-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-93a-retention-execution-history-export-summary-caching-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 93A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            'd07e027fdb831701bb38688ea8d16d6faf3d05fb',
            $document['baseline']['commit']
        );
        $this->assertSame(1892, $document['baseline']['tests']);
        $this->assertSame(17263, $document['baseline']['assertions']);
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

    public function test_cache_policy_key_and_value_are_locked(): void
    {
        $contract = $this->document()['caching_contract'];

        $this->assertTrue($contract['cache_policy']['enabled']);
        $this->assertSame(
            30,
            $contract['cache_policy']['ttl_seconds']
        );
        $this->assertFalse(
            $contract['cache_policy']['cache_forever_forbidden']
                === false
        );
        $this->assertSame(
            'reports:saved-view-retention:execution-history-summary:v1',
            $contract['cache_key']['prefix']
        );
        $this->assertSame([
            'type',
            'status',
            'actor_user_id',
            'started_from',
            'started_to',
        ], $contract['cache_key']['filters']);
        $this->assertTrue(
            $contract['cache_key']['sha256_digest_required']
        );

        foreach ($contract['cache_value'] as $key => $value) {
            $this->assertTrue($value, $key);
        }
    }

    public function test_request_invalidation_failure_and_performance_are_locked(): void
    {
        $contract = $this->document()['caching_contract'];

        foreach ($contract['request_behavior'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertTrue(
            $contract['invalidation']['ttl_is_primary_invalidation']
        );
        $this->assertSame(
            30,
            $contract['invalidation']['maximum_staleness_seconds']
        );
        $this->assertFalse(
            $contract['invalidation']
                ['write_event_invalidation_required']
        );
        $this->assertFalse(
            $contract['invalidation']
                ['manual_cache_flush_route_forbidden']
                === false
        );

        foreach ($contract['failure_behavior'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertSame(
            0,
            $contract['performance']['cache_hit_database_queries']
        );
        $this->assertSame(
            1,
            $contract['performance']['cache_miss_database_queries']
        );
    }

    public function test_scope_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['caching_contract'];

        $this->assertFalse(
            $contract['planned_implementation']
                ['controller_changes_expected']
        );
        $this->assertFalse(
            $contract['planned_implementation']
                ['view_changes_expected']
        );
        $this->assertFalse(
            $contract['planned_implementation']
                ['route_changes_expected']
        );
        $this->assertFalse(
            $contract['planned_implementation']
                ['database_changes_expected']
        );
        $this->assertFalse(
            $contract['planned_implementation']
                ['migration_changes_expected']
        );

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
            'Phase 93B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-93a-retention-execution-history-export-summary-caching-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
