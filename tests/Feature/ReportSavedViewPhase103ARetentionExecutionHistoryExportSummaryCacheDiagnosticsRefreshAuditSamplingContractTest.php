<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase103ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditSamplingContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-103a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-sampling-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-103a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-sampling-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 103A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '39f88d0ba3717a9005e11933916ebbd65815f663',
            $document['baseline']['commit']
        );
        $this->assertSame(2052, $document['baseline']['tests']);
        $this->assertSame(19198, $document['baseline']['assertions']);
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

    public function test_sampling_policy_and_decision_are_locked(): void
    {
        $contract = $this->document()['audit_sampling_contract'];
        $policy = $contract['policy'];
        $decision = $contract['decision'];

        $this->assertTrue($policy['allowed_event_sampling_enabled']);
        $this->assertSame(25, $policy['allowed_sample_rate_percent']);
        $this->assertFalse($policy['limited_event_sampling_enabled']);
        $this->assertSame(100, $policy['limited_event_recording_percent']);
        $this->assertSame(
            'correlation_id',
            $policy['decision_source']
        );
        $this->assertSame(
            'sha256_modulo_100',
            $policy['decision_algorithm']
        );
        $this->assertTrue(
            $policy['deterministic_for_same_correlation_id']
        );
        $this->assertSame(0, $policy['random_runtime_calls']);
        $this->assertSame(
            'class_constant',
            $policy['configuration_source']
        );
        $this->assertFalse(
            $policy['configuration_runtime_mutable']
        );

        $this->assertTrue(
            $decision['sampled_when_bucket_less_than_percent']
        );
        $this->assertSame(0, $decision['bucket_range_min']);
        $this->assertSame(99, $decision['bucket_range_max']);
        $this->assertSame(
            25,
            $decision['allowed_threshold_exclusive']
        );
        $this->assertTrue(
            $decision['limited_requests_bypass_sampling']
        );
        $this->assertSame(
            'record',
            $decision['missing_correlation_id_behavior']
        );
        $this->assertSame(
            'record',
            $decision['invalid_correlation_id_behavior']
        );
    }

    public function test_behavior_privacy_performance_and_compatibility_are_locked(): void
    {
        $contract = $this->document()['audit_sampling_contract'];

        foreach ([
            'sampled_allowed_event_name_unchanged',
            'sampled_allowed_context_unchanged',
            'unsampled_allowed_log_call_skipped',
            'limited_event_name_unchanged',
            'limited_context_unchanged',
            'limited_log_call_always_executed',
            'audit_failure_preserves_response',
            'correlation_generation_unchanged',
        ] as $key) {
            $this->assertTrue(
                $contract['audit_behavior'][$key],
                $key
            );
        }

        foreach ($contract['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        foreach ([
            'additional_database_queries',
            'additional_cache_reads',
            'additional_cache_writes',
            'additional_model_hydration',
            'additional_summary_queries',
            'hash_operations_per_limited_request',
        ] as $key) {
            $this->assertSame(
                0,
                $contract['performance'][$key],
                $key
            );
        }

        $this->assertSame(
            1,
            $contract['performance']
                ['hash_operations_per_allowed_request_maximum']
        );

        foreach ($contract['compatibility'] as $key => $value) {
            $this->assertTrue($value, $key);
        }
    }

    public function test_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $implementation =
            $document['audit_sampling_contract']['planned_implementation'];

        $this->assertSame(
            'app/Http/Middleware/'
            . 'AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh.php',
            $implementation['modified_middleware']
        );

        foreach ([
            'modified_bootstrap',
            'modified_route',
            'modified_controller',
            'modified_service',
            'modified_provider',
            'modified_view',
            'modified_layout',
            'database_changes_expected',
            'migration_changes_expected',
            'model_changes_expected',
        ] as $key) {
            $this->assertFalse($implementation[$key], $key);
        }

        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse(
            $document['workflow']['post_commit_full_suite']
        );
        $this->assertSame(
            'Phase 103B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-103a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-sampling-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
