<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase94ARetentionExecutionHistoryExportSummaryCacheInvalidationContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/phase-94a-retention-execution-history-export-summary-cache-invalidation-contract.json'
        );
        $markdownPath = base_path(
            'docs/phase-94a-retention-execution-history-export-summary-cache-invalidation-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(file_get_contents($jsonPath), true);

        $this->assertIsArray($document);
        $this->assertSame('Phase 94A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            'f497728ebaf351c1a60014f09bdbe0d8ac2680c0',
            $document['baseline']['commit']
        );
        $this->assertSame(1908, $document['baseline']['tests']);
        $this->assertSame(17422, $document['baseline']['assertions']);
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

    public function test_strategy_write_and_read_behavior_are_locked(): void
    {
        $contract = $this->document()['invalidation_contract'];

        $this->assertSame(
            'generation token',
            $contract['strategy']['type']
        );
        $this->assertSame(
            'reports:saved-view-retention:execution-history-summary:generation:v1',
            $contract['strategy']['generation_key']
        );
        $this->assertSame(
            86400,
            $contract['strategy']['generation_ttl_seconds']
        );

        foreach ($contract['write_behavior'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($contract['read_behavior'] as $key => $value) {
            $this->assertTrue($value, $key);
        }
    }

    public function test_freshness_privacy_and_performance_are_locked(): void
    {
        $contract = $this->document()['invalidation_contract'];

        foreach ($contract['freshness'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($contract['privacy'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertSame(
            0,
            $contract['performance']['cache_hit_summary_database_queries']
        );
        $this->assertSame(
            1,
            $contract['performance']['cache_miss_summary_database_queries']
        );
        $this->assertSame(
            0,
            $contract['performance']['generation_token_database_queries']
        );
        $this->assertTrue(
            $contract['performance']['history_write_database_queries_unchanged']
        );
    }

    public function test_scope_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['invalidation_contract'];

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
            'Phase 94B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/phase-94a-retention-execution-history-export-summary-cache-invalidation-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
