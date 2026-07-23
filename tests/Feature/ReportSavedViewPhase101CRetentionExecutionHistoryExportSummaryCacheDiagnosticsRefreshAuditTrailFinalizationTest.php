<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase101CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditTrailFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-101c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-trail-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-101c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-trail-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 101C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '0bfecea19b5cf41bc4a4561388cdc394d542f98b',
            $document['baseline']['commit']
        );
        $this->assertSame(2031, $document['baseline']['tests']);
        $this->assertSame(18867, $document['baseline']['assertions']);
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

    public function test_middleware_events_context_and_order_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'App\\Http\\Middleware\\'
            . 'AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh',
            $locked['middleware']['class']
        );
        $this->assertSame(
            'audit.saved-view-retention-summary-cache-diagnostics-refresh',
            $locked['middleware']['alias']
        );
        $this->assertSame(
            'application_log',
            $locked['middleware']['storage']
        );
        $this->assertSame(
            'info',
            $locked['middleware']['logging_level']
        );

        $this->assertSame(
            'saved_view_retention.'
            . 'summary_cache_diagnostics.refresh_audit.allowed',
            $locked['events']['allowed']
        );
        $this->assertSame(
            'saved_view_retention.'
            . 'summary_cache_diagnostics.refresh_audit.limited',
            $locked['events']['limited']
        );

        $this->assertSame(
            [
                'event',
                'outcome',
                'route_name',
                'request_method',
                'authenticated',
                'permission_checked',
                'rate_limit_name',
            ],
            $locked['allowed_context']
        );
        $this->assertContains(
            'retry_after_seconds',
            $locked['limited_context']
        );

        foreach ($locked['middleware_order'] as $key => $value) {
            $this->assertTrue($value, $key);
        }
    }

    public function test_responses_behavior_and_performance_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        foreach ([
            'allowed_status_unchanged',
            'allowed_body_unchanged',
            'limited_body_unchanged',
            'retry_after_header_unchanged',
        ] as $key) {
            $this->assertTrue($locked['responses'][$key], $key);
        }

        $this->assertSame(
            429,
            $locked['responses']['limited_status_code']
        );
        $this->assertFalse(
            $locked['responses']['audit_failure_changes_response']
        );

        foreach ([
            'allowed_request_calls_controller',
            'allowed_request_calls_diagnostics_service_once',
            'audit_failure_swallowed',
            'observability_events_unchanged',
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
            'middleware_added',
            'bootstrap_modified',
            'route_modified',
            'phase_101b_test_added',
        ] as $key) {
            $this->assertTrue(
                $locked['implementation_scope'][$key],
                $key
            );
        }

        foreach ([
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
            'Phase 102A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-101c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-trail-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
