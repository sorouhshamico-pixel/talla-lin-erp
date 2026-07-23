<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase103CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditSamplingFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-103c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-sampling-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-103c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-sampling-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 103C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '140c6f7352504480bda76330f73ca25532563eaa',
            $document['baseline']['commit']
        );
        $this->assertSame(2065, $document['baseline']['tests']);
        $this->assertSame(19346, $document['baseline']['assertions']);
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
            'layout_changes_expected',
            'provider_changes_expected',
            'bootstrap_changes_expected',
            'middleware_changes_expected',
        ] as $key) {
            $this->assertFalse($scope[$key], $key);
        }

        $this->assertTrue($scope['documentation_and_tests_only']);
    }

    public function test_sampling_policy_fixtures_and_failure_modes_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];
        $policy = $locked['sampling_policy'];

        $this->assertTrue($policy['allowed_event_sampling_enabled']);
        $this->assertSame(25, $policy['allowed_sample_rate_percent']);
        $this->assertFalse($policy['limited_event_sampling_enabled']);
        $this->assertSame(100, $policy['limited_event_recording_percent']);
        $this->assertSame(
            'laravel_context_correlation_id',
            $policy['decision_source']
        );
        $this->assertSame(
            'sha256_modulo_100',
            $policy['decision_algorithm']
        );
        $this->assertTrue(
            $policy['sampled_when_bucket_less_than_percent']
        );
        $this->assertSame(0, $policy['bucket_range_min']);
        $this->assertSame(99, $policy['bucket_range_max']);
        $this->assertSame(25, $policy['allowed_threshold_exclusive']);
        $this->assertTrue(
            $policy['deterministic_for_same_correlation_id']
        );
        $this->assertSame(0, $policy['random_runtime_calls']);

        $this->assertSame(
            '00000000-0000-4000-8000-000000000010',
            $locked['fixtures']['sampled_uuid']
        );
        $this->assertSame(22, $locked['fixtures']['sampled_bucket']);
        $this->assertSame(
            '00000000-0000-4000-8000-000000000001',
            $locked['fixtures']['unsampled_uuid']
        );
        $this->assertSame(48, $locked['fixtures']['unsampled_bucket']);

        $this->assertSame(
            'record',
            $locked['failure_modes']['missing_correlation_id_behavior']
        );
        $this->assertSame(
            'record',
            $locked['failure_modes']['invalid_correlation_id_behavior']
        );
        $this->assertTrue(
            $locked['failure_modes']['audit_failure_preserves_response']
        );
        $this->assertTrue(
            $locked['failure_modes']['limited_requests_bypass_sampling']
        );
    }

    public function test_audit_compatibility_migrations_and_privacy_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        foreach ($locked['audit_behavior'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ([
            'phase_101b_test_updated',
            'phase_102b_test_updated',
            'forced_sampled_uuid_used_for_allowed_expectations',
            'limited_test_cases_unchanged',
        ] as $key) {
            $this->assertTrue(
                $locked['compatibility_migrations'][$key],
                $key
            );
        }

        foreach ([
            'historical_runtime_contract_relaxed',
            'production_test_environment_exception_added',
            'sampling_disabled_in_tests',
        ] as $key) {
            $this->assertFalse(
                $locked['compatibility_migrations'][$key],
                $key
            );
        }

        foreach ($locked['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }
    }

    public function test_performance_scope_compatibility_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

        foreach ([
            'additional_database_queries',
            'additional_cache_reads',
            'additional_cache_writes',
            'additional_model_hydration',
            'additional_summary_queries',
            'hash_operations_per_limited_request',
        ] as $key) {
            $this->assertSame(0, $locked['performance'][$key], $key);
        }

        $this->assertSame(
            1,
            $locked['performance']
                ['hash_operations_per_allowed_request_maximum']
        );

        foreach ([
            'middleware_modified',
            'phase_101b_test_modified',
            'phase_102b_test_modified',
            'phase_103b_test_added',
        ] as $key) {
            $this->assertTrue(
                $locked['implementation_scope'][$key],
                $key
            );
        }

        foreach ([
            'bootstrap_changed',
            'route_changed',
            'controller_changed',
            'service_changed',
            'provider_changed',
            'view_changed',
            'layout_changed',
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
            'Phase 104A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-103c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-sampling-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
