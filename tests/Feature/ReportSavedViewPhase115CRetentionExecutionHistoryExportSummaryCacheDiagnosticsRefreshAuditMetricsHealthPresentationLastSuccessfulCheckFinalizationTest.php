<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase115CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationLastSuccessfulCheckFinalizationTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-115c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-last-successful-check-finalization.json';

    public function test_finalization_documents_exist_and_baseline_is_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-115c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-last-successful-check-finalization.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 115C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            'd58b87c5e2a8c46830b497fd5a4ce325f080b812',
            $document['baseline']['commit']
        );
        $this->assertSame(2268, $document['baseline']['tests']);
        $this->assertSame(22585, $document['baseline']['assertions']);
    }

    public function test_phase_is_documentation_and_tests_only(): void
    {
        $scope = $this->document()['scope'];

        foreach ($scope as $key => $value) {
            if ($key === 'documentation_and_tests_only') {
                $this->assertTrue($value, $key);

                continue;
            }

            $this->assertFalse($value, $key);
        }
    }

    public function test_element_helper_success_definition_and_update_rules_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame('time', $locked['element']['element']);
        $this->assertSame(
            'retention-audit-metrics-health-last-successful-check',
            $locked['element']['id']
        );
        $this->assertSame(
            'Last successful check:',
            $locked['element']['prefix']
        );
        $this->assertSame(
            'No successful check yet',
            $locked['element']['initial_text']
        );
        $this->assertFalse(
            $locked['element']['initial_datetime_attribute_present']
        );

        $this->assertSame(
            'updateLastSuccessfulCheck',
            $locked['helper']['name']
        );
        $this->assertSame(
            'new Date()',
            $locked['helper']['clock']
        );
        $this->assertTrue(
            $locked['helper']['invalid_date_removes_datetime']
        );
        $this->assertSame(
            'completedAt.toISOString()',
            $locked['helper']['datetime_value']
        );

        foreach ([
            'payload_validated',
            'fields_rendered_before_update',
            'payload_healthy_true_required',
            'validated_healthy_response_updates',
        ] as $key) {
            $this->assertTrue(
                $locked['success_definition'][$key],
                $key
            );
        }

        foreach ([
            'validated_unhealthy_response_updates',
            'http_error_response_updates',
            'network_failure_updates',
            'json_parse_failure_updates',
            'payload_validation_failure_updates',
        ] as $key) {
            $this->assertFalse(
                $locked['success_definition'][$key],
                $key
            );
        }

        $this->assertFalse(
            $locked['update_rules']['request_start_clears_previous_value']
        );
        $this->assertFalse(
            $locked['update_rules']['ignored_concurrent_request_updates']
        );

        foreach ([
            'update_occurs_before_request_success_flag',
            'update_occurs_before_request_finally',
            'updates_once_per_validated_healthy_request',
            'unhealthy_preserves_previous_value',
            'failure_preserves_previous_value',
        ] as $key) {
            $this->assertTrue($locked['update_rules'][$key], $key);
        }
    }

    public function test_timestamp_accessibility_privacy_behavior_and_scope_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertTrue(
            $locked['timestamp_source']['client_completion_time']
        );

        foreach ([
            'server_timestamp_used',
            'payload_timestamp_used',
            'response_header_timestamp_used',
            'date_now_used',
            'persistent_storage_used',
        ] as $key) {
            $this->assertFalse(
                $locked['timestamp_source'][$key],
                $key
            );
        }

        foreach ($locked['accessibility'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($locked['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        foreach ([
            'consecutive_failure_counter_unchanged',
            'response_status_unchanged',
            'request_duration_unchanged',
            'refresh_timestamp_unchanged',
            'health_status_messages_unchanged',
            'visual_state_unchanged',
            'field_rendering_unchanged',
            'payload_validation_unchanged',
            'concurrent_requests_prevented',
        ] as $key) {
            $this->assertTrue($locked['behavior'][$key], $key);
        }

        foreach ([
            'polling_added',
            'retry_loop_added',
            'page_reload_added',
        ] as $key) {
            $this->assertFalse($locked['behavior'][$key], $key);
        }

        foreach ([
            'partial_modified',
            'phase_115b_test_added',
        ] as $key) {
            $this->assertTrue(
                $locked['implementation_scope'][$key],
                $key
            );
        }

        foreach ([
            'parent_view_modified',
            'controller_modified',
            'route_modified',
            'health_class_modified',
            'listener_modified',
            'event_modified',
            'middleware_modified',
            'logging_configuration_modified',
            'layout_modified',
            'provider_modified',
            'database_modified',
            'migration_modified',
            'model_modified',
        ] as $key) {
            $this->assertFalse(
                $locked['implementation_scope'][$key],
                $key
            );
        }
    }

    public function test_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

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
            'Phase 116A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(base_path(self::JSON_PATH)),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
