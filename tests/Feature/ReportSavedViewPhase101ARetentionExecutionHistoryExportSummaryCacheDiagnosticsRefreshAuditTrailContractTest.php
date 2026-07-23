<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase101ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditTrailContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-101a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-trail-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-101a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-trail-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 101A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            'f40b515b5e86e1fc406918644aa48e84fc81a9df',
            $document['baseline']['commit']
        );
        $this->assertSame(2020, $document['baseline']['tests']);
        $this->assertSame(18707, $document['baseline']['assertions']);
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
        ] as $key) {
            $this->assertFalse($scope[$key], $key);
        }

        $this->assertTrue($scope['documentation_and_tests_only']);
    }

    public function test_events_storage_and_context_are_locked(): void
    {
        $contract = $this->document()['audit_trail_contract'];

        $this->assertSame(
            'saved_view_retention.summary_cache_diagnostics.refresh_audit.allowed',
            $contract['planned_events']['allowed']
        );
        $this->assertSame(
            'saved_view_retention.summary_cache_diagnostics.refresh_audit.limited',
            $contract['planned_events']['limited']
        );

        $this->assertSame(
            'application_log',
            $contract['planned_storage']['type']
        );
        $this->assertTrue(
            $contract['planned_storage']['append_only']
        );

        foreach ([
            'database_table_added',
            'migration_added',
            'model_added',
            'existing_history_table_reused',
        ] as $key) {
            $this->assertFalse(
                $contract['planned_storage'][$key],
                $key
            );
        }

        $this->assertContains(
            'retry_after_seconds',
            $contract['planned_context']['limited']
        );
        $this->assertNotContains(
            'retry_after_seconds',
            $contract['planned_context']['allowed']
        );

        foreach ($contract['forbidden_context'] as $value) {
            $this->assertIsString($value);
            $this->assertNotSame('', $value);
        }
    }

    public function test_behavior_performance_and_compatibility_are_locked(): void
    {
        $contract = $this->document()['audit_trail_contract'];

        foreach ([
            'allowed_request_audit_written_after_rate_limit_allows',
            'limited_request_audit_written_without_controller_execution',
            'allowed_request_calls_diagnostics_service_once',
            'audit_failure_is_swallowed',
            'existing_observability_events_unchanged',
            'existing_rate_limit_response_unchanged',
            'existing_diagnostics_payload_unchanged',
        ] as $key) {
            $this->assertTrue($contract['behavior'][$key], $key);
        }

        foreach ([
            'limited_request_calls_diagnostics_service',
            'audit_failure_changes_response',
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
            $document['audit_trail_contract']['planned_implementation'];

        $this->assertSame(
            'custom route middleware',
            $implementation['preferred_location']
        );
        $this->assertSame(
            'AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh',
            $implementation['middleware_name']
        );

        foreach ([
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

        $this->assertTrue(
            $implementation['modified_bootstrap_or_kernel']
        );
        $this->assertTrue($implementation['modified_route']);

        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse(
            $document['workflow']['post_commit_full_suite']
        );
        $this->assertSame(
            'Phase 101B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-101a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-trail-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
