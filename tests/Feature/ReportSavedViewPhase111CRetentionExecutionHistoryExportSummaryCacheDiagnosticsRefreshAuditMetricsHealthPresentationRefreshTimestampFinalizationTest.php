<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase111CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationRefreshTimestampFinalizationTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-111c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-refresh-timestamp-finalization.json';

    public function test_finalization_documents_exist_and_baseline_is_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-111c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-refresh-timestamp-finalization.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 111C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '2a034ef2e2c6458c2878165fe3541129c5cd864a',
            $document['baseline']['commit']
        );
        $this->assertSame(2197, $document['baseline']['tests']);
        $this->assertSame(21461, $document['baseline']['assertions']);
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

    public function test_timestamp_element_source_formatting_and_update_rules_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame('time', $locked['timestamp_element']['element']);
        $this->assertSame(
            'retention-audit-metrics-health-updated-at',
            $locked['timestamp_element']['id']
        );
        $this->assertSame(
            'Not updated yet',
            $locked['timestamp_element']['initial_text']
        );
        $this->assertNull(
            $locked['timestamp_element']['initial_datetime_attribute']
        );
        $this->assertSame('off', $locked['timestamp_element']['aria_live']);
        $this->assertSame(
            'Last checked:',
            $locked['timestamp_element']['prefix']
        );

        $this->assertSame('client', $locked['timestamp_source']['clock']);
        $this->assertSame(
            'new Date()',
            $locked['timestamp_source']['constructor']
        );
        $this->assertSame(
            'browser_local_timezone',
            $locked['timestamp_source']['timezone']
        );

        $this->assertSame(
            'Intl.DateTimeFormat',
            $locked['formatting']['primary_formatter']
        );
        $this->assertSame(
            'Date.prototype.toISOString',
            $locked['formatting']['machine_readable_generator']
        );

        $this->assertFalse(
            $locked['update_rules']['request_start_updates_timestamp']
        );
        $this->assertFalse(
            $locked['update_rules']['ignored_concurrent_request_updates_timestamp']
        );

        foreach ([
            'validated_healthy_completion_updates_timestamp',
            'validated_unhealthy_completion_updates_timestamp',
            'request_failure_updates_timestamp',
            'parse_failure_updates_timestamp',
            'validation_failure_updates_timestamp',
            'updates_once_per_completed_request',
        ] as $key) {
            $this->assertTrue($locked['update_rules'][$key], $key);
        }

        $this->assertSame(
            'request_finally_block',
            $locked['update_rules']['implementation_location']
        );
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
            'phase_111b_test_added',
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
            'Phase 112A',
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
