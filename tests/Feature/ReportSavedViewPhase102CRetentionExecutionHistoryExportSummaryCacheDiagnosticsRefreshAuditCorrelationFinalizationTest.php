<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase102CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditCorrelationFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-102c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-correlation-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-102c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-correlation-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 102C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            'b36af83444d432f637fdaf632c223e19e87c105f',
            $document['baseline']['commit']
        );
        $this->assertSame(2047, $document['baseline']['tests']);
        $this->assertSame(19095, $document['baseline']['assertions']);
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

    public function test_correlation_and_audit_compatibility_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];
        $correlation = $locked['correlation'];

        $this->assertSame(
            'correlation_id',
            $correlation['context_key']
        );
        $this->assertSame(
            'laravel_context',
            $correlation['transport']
        );
        $this->assertSame(
            'Context::add',
            $correlation['framework_api']
        );
        $this->assertSame(
            'Str::uuid',
            $correlation['generator']
        );
        $this->assertSame(
            'uuid_v4',
            $correlation['format']
        );

        foreach ([
            'generated_once_per_request',
            'stable_within_request',
            'replaced_on_next_request',
        ] as $key) {
            $this->assertTrue($correlation[$key], $key);
        }

        foreach ([
            'client_supplied_value_accepted',
            'request_headers_read',
            'response_header_added',
            'response_body_added',
            'database_persisted',
            'cache_persisted',
            'session_persisted',
        ] as $key) {
            $this->assertFalse($correlation[$key], $key);
        }

        foreach (
            $locked['audit_compatibility']
            as $key => $value
        ) {
            $this->assertTrue($value, $key);
        }
    }

    public function test_privacy_behavior_and_performance_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        foreach ($locked['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        foreach ([
            'allowed_request_calls_controller',
            'allowed_request_calls_diagnostics_service_once',
            'audit_failure_preserves_response',
            'correlation_context_available_after_audit_failure',
            'existing_observability_events_unchanged',
            'rate_limit_behavior_unchanged',
            'diagnostics_payload_unchanged',
        ] as $key) {
            $this->assertTrue($locked['behavior'][$key], $key);
        }

        foreach ([
            'limited_request_calls_controller',
            'limited_request_calls_diagnostics_service',
        ] as $key) {
            $this->assertFalse($locked['behavior'][$key], $key);
        }

        foreach ($locked['performance'] as $key => $value) {
            $this->assertSame(0, $value, $key);
        }
    }

    public function test_scope_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

        foreach ([
            'middleware_modified',
            'phase_102b_test_added',
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
            'Phase 103A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-102c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-correlation-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
