<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase114CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationConsecutiveFailureCounterFinalizationTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-114c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-consecutive-failure-counter-finalization.json';

    public function test_finalization_documents_exist_and_baseline_is_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-114c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-consecutive-failure-counter-finalization.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 114C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            'a5232aa7b69e866c5f1e29ce010130823ae5b872',
            $document['baseline']['commit']
        );
        $this->assertSame(2251, $document['baseline']['tests']);
        $this->assertSame(22289, $document['baseline']['assertions']);
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

    public function test_counter_element_state_helpers_and_classification_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'retention-audit-metrics-health-consecutive-failures',
            $locked['counter_element']['id']
        );
        $this->assertSame(
            'Consecutive failures:',
            $locked['counter_element']['prefix']
        );
        $this->assertSame(
            '0',
            $locked['counter_element']['initial_text']
        );

        $this->assertSame(
            'consecutiveFailures',
            $locked['state']['variable']
        );
        $this->assertSame(0, $locked['state']['initial_value']);
        $this->assertSame(999, $locked['state']['maximum']);
        $this->assertTrue($locked['state']['integer_required']);
        $this->assertTrue($locked['state']['client_memory_only']);

        foreach ([
            'local_storage_used',
            'session_storage_used',
            'indexed_db_used',
            'cookie_used',
            'database_used',
            'cache_used',
        ] as $key) {
            $this->assertFalse($locked['state'][$key], $key);
        }

        $this->assertSame(
            'renderConsecutiveFailures',
            $locked['helpers']['render_helper']
        );
        $this->assertSame(
            'recordSuccessfulRequest',
            $locked['helpers']['success_helper']
        );
        $this->assertSame(
            'recordFailedRequest',
            $locked['helpers']['failure_helper']
        );

        $this->assertSame(
            'success',
            $locked['classification']['validated_healthy_response']
        );
        $this->assertSame(
            'success',
            $locked['classification']['validated_unhealthy_response']
        );

        foreach ([
            'http_error_response',
            'network_failure',
            'json_parse_failure',
            'payload_validation_failure',
        ] as $key) {
            $this->assertSame(
                'failure',
                $locked['classification'][$key],
                $key
            );
        }
    }

    public function test_update_display_accessibility_privacy_and_scope_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertFalse(
            $locked['update_rules']['request_start_changes_counter']
        );
        $this->assertFalse(
            $locked['update_rules']['request_success_initial_value']
        );
        $this->assertFalse(
            $locked['update_rules']['ignored_concurrent_request_changes_counter']
        );

        foreach ([
            'request_success_set_only_after_payload_validation',
            'success_resets_to_zero',
            'failure_increments_by_one',
            'counter_clamped_to_maximum',
            'updates_once_per_executed_request',
            'http_error_counted_once',
            'network_failure_counted_once',
            'parse_failure_counted_once',
            'validation_failure_counted_once',
        ] as $key) {
            $this->assertTrue($locked['update_rules'][$key], $key);
        }

        $this->assertSame(
            'request_finally_block',
            $locked['update_rules']['update_location']
        );
        $this->assertSame('0', $locked['display']['zero']);
        $this->assertSame('0', $locked['display']['invalid_value']);
        $this->assertFalse(
            $locked['display']['relative_wording_used']
        );

        foreach ($locked['accessibility'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($locked['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        foreach ([
            'partial_modified',
            'phase_114b_test_added',
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

    public function test_behavior_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

        foreach ([
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
            'Phase 115A',
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
