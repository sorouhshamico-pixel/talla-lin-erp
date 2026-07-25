<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase112CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationRequestDurationFinalizationTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-112c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-request-duration-finalization.json';

    public function test_finalization_documents_exist_and_baseline_is_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-112c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-request-duration-finalization.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 112C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '54e64e28d76a50a5c5cf5fd29b61952013ffa82e',
            $document['baseline']['commit']
        );
        $this->assertSame(2215, $document['baseline']['tests']);
        $this->assertSame(21727, $document['baseline']['assertions']);
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

    public function test_duration_element_measurement_formatting_and_updates_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame('span', $locked['duration_element']['element']);
        $this->assertSame(
            'retention-audit-metrics-health-request-duration',
            $locked['duration_element']['id']
        );
        $this->assertSame(
            'Last request duration:',
            $locked['duration_element']['prefix']
        );
        $this->assertSame(
            'Not measured yet',
            $locked['duration_element']['initial_text']
        );
        $this->assertSame('off', $locked['duration_element']['aria_live']);

        $this->assertSame(
            "performance['now']()",
            $locked['measurement']['syntax']
        );
        $this->assertSame(
            'immediately_before_fetch',
            $locked['measurement']['start_point']
        );
        $this->assertSame(
            'request_finally_block',
            $locked['measurement']['end_point']
        );
        $this->assertSame(
            2,
            $locked['measurement']['clock_reads_per_executed_request']
        );
        $this->assertTrue(
            $locked['measurement']['negative_values_clamped_to_zero']
        );

        $this->assertSame(
            'Intl.NumberFormat',
            $locked['formatting']['formatter']
        );
        $this->assertSame(
            'Number.prototype.toFixed',
            $locked['formatting']['fallback']
        );

        $this->assertFalse(
            $locked['update_rules']['request_start_clears_previous_duration']
        );
        $this->assertFalse(
            $locked['update_rules']['ignored_concurrent_request_updates_duration']
        );

        foreach ([
            'validated_healthy_completion_updates_duration',
            'validated_unhealthy_completion_updates_duration',
            'request_failure_updates_duration',
            'parse_failure_updates_duration',
            'validation_failure_updates_duration',
            'updates_once_per_completed_request',
        ] as $key) {
            $this->assertTrue($locked['update_rules'][$key], $key);
        }
    }

    public function test_accessibility_privacy_behavior_and_scope_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        foreach ($locked['accessibility'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($locked['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        foreach ([
            'refresh_timestamp_unchanged',
            'status_messages_unchanged',
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
            'countdown_added',
            'elapsed_timer_added',
            'page_reload_added',
        ] as $key) {
            $this->assertFalse($locked['behavior'][$key], $key);
        }

        foreach ([
            'partial_modified',
            'phase_112b_test_added',
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
            'Phase 113A',
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
