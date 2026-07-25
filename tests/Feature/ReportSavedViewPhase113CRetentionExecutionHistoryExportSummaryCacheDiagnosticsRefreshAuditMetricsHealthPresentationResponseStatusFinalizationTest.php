<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase113CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationResponseStatusFinalizationTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-113c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-response-status-finalization.json';

    public function test_finalization_documents_exist_and_baseline_is_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-113c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-response-status-finalization.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 113C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '2543dd26a9b4d1d8bcf90504ba873aebbd4501e5',
            $document['baseline']['commit']
        );
        $this->assertSame(2233, $document['baseline']['tests']);
        $this->assertSame(21999, $document['baseline']['assertions']);
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

    public function test_element_source_formatting_and_updates_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'retention-audit-metrics-health-response-status',
            $locked['response_status_element']['id']
        );
        $this->assertSame(
            'Last response:',
            $locked['response_status_element']['prefix']
        );
        $this->assertSame(
            'Not received yet',
            $locked['response_status_element']['initial_text']
        );

        $this->assertSame(
            'Response.status',
            $locked['source']['status_code']
        );
        $this->assertSame(
            'Response.statusText',
            $locked['source']['status_text']
        );
        $this->assertSame(
            'Response.ok',
            $locked['source']['response_ok']
        );

        $this->assertSame(
            100,
            $locked['formatting']['valid_status_minimum']
        );
        $this->assertSame(
            599,
            $locked['formatting']['valid_status_maximum']
        );
        $this->assertTrue(
            $locked['formatting']['status_text_trimmed']
        );
        $this->assertSame(
            'Network error',
            $locked['formatting']['network_failure_text']
        );

        $this->assertFalse(
            $locked['update_rules']['request_start_clears_previous_status']
        );
        $this->assertFalse(
            $locked['update_rules']['ignored_concurrent_request_updates_status']
        );

        foreach ([
            'response_received_updates_before_http_ok_check',
            'response_received_updates_before_json_parse',
            'http_success_updates_status',
            'http_error_updates_status',
            'network_failure_updates_status',
            'json_parse_failure_preserves_received_status',
            'payload_validation_failure_preserves_received_status',
        ] as $key) {
            $this->assertTrue($locked['update_rules'][$key], $key);
        }

        $this->assertSame(
            1,
            $locked['update_rules']['http_status_update_count_per_response']
        );
        $this->assertSame(
            1,
            $locked['update_rules']['network_failure_update_count_per_request']
        );
    }

    public function test_network_accessibility_privacy_behavior_and_scope_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertFalse(
            $locked['network_failure_control']['response_received_flag_initial_value']
        );

        foreach ([
            'response_received_flag_set_after_fetch',
            'network_error_only_without_response',
            'http_error_not_reclassified_as_network_error',
        ] as $key) {
            $this->assertTrue(
                $locked['network_failure_control'][$key],
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
            'phase_113b_test_added',
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
            'Phase 114A',
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
