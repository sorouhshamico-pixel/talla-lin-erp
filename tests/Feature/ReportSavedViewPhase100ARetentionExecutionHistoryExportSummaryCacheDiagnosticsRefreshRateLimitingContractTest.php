<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase100ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshRateLimitingContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/phase-100a-retention-execution-history-export-summary-cache-diagnostics-refresh-rate-limiting-contract.json'
        );
        $markdownPath = base_path(
            'docs/phase-100a-retention-execution-history-export-summary-cache-diagnostics-refresh-rate-limiting-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(file_get_contents($jsonPath), true);

        $this->assertIsArray($document);
        $this->assertSame('Phase 100A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            'c5f12701c604f53905aba08ba671d838a7fca272',
            $document['baseline']['commit']
        );
        $this->assertSame(2005, $document['baseline']['tests']);
        $this->assertSame(18491, $document['baseline']['assertions']);
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
        ] as $key) {
            $this->assertFalse($scope[$key], $key);
        }

        $this->assertTrue($scope['documentation_and_tests_only']);
    }

    public function test_limiter_strategy_and_response_are_locked(): void
    {
        $contract = $this->document()['rate_limiting_contract'];

        $this->assertSame(
            'saved-view-retention-summary-cache-diagnostics-refresh',
            $contract['planned_strategy']['limiter_name']
        );
        $this->assertSame(30, $contract['planned_strategy']['maximum_attempts']);
        $this->assertSame(60, $contract['planned_strategy']['decay_seconds']);
        $this->assertSame(
            'authenticated_user',
            $contract['planned_strategy']['key_scope']
        );
        $this->assertSame(
            'ip_address',
            $contract['planned_strategy']['guest_fallback']
        );
        $this->assertSame(200, $contract['response']['allowed_request_status_code']);
        $this->assertSame(429, $contract['response']['limited_request_status_code']);
        $this->assertTrue($contract['response']['allowed_payload_unchanged']);
        $this->assertTrue(
            $contract['response']['limited_response_uses_framework_default']
        );
        $this->assertFalse(
            $contract['response']['limited_response_contains_diagnostics_payload']
        );
        $this->assertTrue($contract['response']['retry_after_header_expected']);
    }

    public function test_security_behavior_and_performance_are_locked(): void
    {
        $contract = $this->document()['rate_limiting_contract'];

        $this->assertTrue($contract['security']['authentication_remains_required']);
        $this->assertTrue($contract['security']['existing_permission_reused']);

        foreach ([
            'new_permission_added',
            'limiter_key_exposes_raw_user_data',
            'raw_generation_token_exposed',
            'raw_cache_key_exposed',
            'raw_filters_exposed',
            'history_payload_exposed',
        ] as $key) {
            $this->assertFalse($contract['security'][$key], $key);
        }

        foreach ([
            'manual_refresh_only',
            'automatic_polling_remains_absent',
            'full_page_reload_remains_absent',
            'concurrent_client_requests_still_prevented',
            'successful_request_calls_diagnostics_service_once',
            'successful_observability_events_unchanged',
            'failure_observability_events_unchanged',
        ] as $key) {
            $this->assertTrue($contract['behavior'][$key], $key);
        }

        $this->assertFalse(
            $contract['behavior']['limited_request_calls_diagnostics_service']
        );

        foreach ([
            'additional_database_queries',
            'additional_model_hydration',
            'additional_summary_queries',
            'limited_request_diagnostics_cache_reads',
        ] as $key) {
            $this->assertSame(0, $contract['performance'][$key], $key);
        }

        $this->assertTrue(
            $contract['performance']['successful_request_cache_reads_unchanged']
        );
    }

    public function test_scope_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['rate_limiting_contract'];

        foreach ([
            'controller_changes_expected',
            'service_changes_expected',
            'view_changes_expected',
            'layout_changes_expected',
            'database_changes_expected',
            'migration_changes_expected',
            'model_changes_expected',
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
        $this->assertFalse($document['workflow']['post_commit_full_suite']);
        $this->assertSame(
            'Phase 100B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/phase-100a-retention-execution-history-export-summary-cache-diagnostics-refresh-rate-limiting-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
