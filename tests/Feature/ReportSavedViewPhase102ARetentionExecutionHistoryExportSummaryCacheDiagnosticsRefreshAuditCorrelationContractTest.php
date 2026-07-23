<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase102ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditCorrelationContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-102a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-correlation-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-102a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-correlation-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 102A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '6270658157ee9f3985753009d3d771a939c95bd3',
            $document['baseline']['commit']
        );
        $this->assertSame(2036, $document['baseline']['tests']);
        $this->assertSame(18953, $document['baseline']['assertions']);
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

    public function test_identifier_format_and_context_are_locked(): void
    {
        $contract = $this->document()['audit_correlation_contract'];
        $identifier = $contract['identifier'];
        $format = $contract['format'];

        $this->assertSame(
            'correlation_id',
            $identifier['context_key']
        );
        $this->assertSame(
            'random_uuid',
            $identifier['generation']
        );

        foreach ([
            'generated_once_per_request',
            'stable_within_request',
            'unique_across_requests',
        ] as $key) {
            $this->assertTrue($identifier[$key], $key);
        }

        foreach ([
            'client_supplied_value_accepted',
            'request_header_read',
            'response_header_added',
            'response_body_added',
            'database_persisted',
            'cache_persisted',
            'session_persisted',
        ] as $key) {
            $this->assertFalse($identifier[$key], $key);
        }

        $this->assertSame('uuid_v4', $format['type']);
        $this->assertSame(36, $format['canonical_length']);
        $this->assertTrue($format['lowercase']);
        $this->assertTrue($format['hyphenated']);

        foreach ([
            'contains_user_data',
            'contains_ip_data',
            'contains_session_data',
            'contains_timestamp',
        ] as $key) {
            $this->assertFalse($format[$key], $key);
        }

        $this->assertSame(
            ['correlation_id'],
            $contract['planned_context']['allowed_event_addition']
        );
        $this->assertSame(
            ['correlation_id'],
            $contract['planned_context']['limited_event_addition']
        );
    }

    public function test_behavior_performance_and_compatibility_are_locked(): void
    {
        $contract = $this->document()['audit_correlation_contract'];

        foreach ([
            'allowed_request_gets_one_correlation_id',
            'limited_request_gets_one_correlation_id',
            'audit_failure_does_not_change_response',
            'diagnostics_service_calls_unchanged',
            'existing_audit_events_unchanged',
            'existing_observability_events_unchanged',
            'rate_limit_behavior_unchanged',
        ] as $key) {
            $this->assertTrue($contract['behavior'][$key], $key);
        }

        foreach ([
            'correlation_generation_failure_is_swallowed',
            'limited_request_calls_controller',
            'limited_request_calls_diagnostics_service',
        ] as $key) {
            $this->assertFalse($contract['behavior'][$key], $key);
        }

        foreach ($contract['performance'] as $key => $value) {
            $this->assertSame(0, $value, $key);
        }

        foreach ($contract['compatibility'] as $key => $value) {
            $this->assertTrue($value, $key);
        }
    }

    public function test_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $implementation =
            $document['audit_correlation_contract']['planned_implementation'];

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
            'Phase 102B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-102a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-correlation-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
