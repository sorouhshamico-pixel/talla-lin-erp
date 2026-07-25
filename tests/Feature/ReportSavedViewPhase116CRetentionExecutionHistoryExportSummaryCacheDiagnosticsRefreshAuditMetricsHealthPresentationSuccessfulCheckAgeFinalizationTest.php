<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase116CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationSuccessfulCheckAgeFinalizationTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-116c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-successful-check-age-finalization.json';

    public function test_finalization_documents_exist_and_baseline_is_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-116c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-successful-check-age-finalization.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 116C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '63382e5aa16fa574c76b65ffb35ab85a988acefe',
            $document['baseline']['commit']
        );
        $this->assertSame(2285, $document['baseline']['tests']);
        $this->assertSame(22895, $document['baseline']['assertions']);
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

    public function test_element_state_helpers_validation_and_calculation_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'retention-audit-metrics-health-successful-check-age',
            $locked['element']['id']
        );
        $this->assertSame(
            'Successful check age:',
            $locked['element']['prefix']
        );
        $this->assertSame(
            'Not available',
            $locked['element']['initial_text']
        );

        $this->assertSame(
            'lastSuccessfulCheckAt',
            $locked['state']['variable']
        );
        $this->assertNull($locked['state']['initial_value']);
        $this->assertSame(
            'same completedAt Date used by updateLastSuccessfulCheck',
            $locked['state']['source']
        );
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
            'formatSuccessfulCheckAge',
            $locked['helpers']['formatter']
        );
        $this->assertSame(
            'updateSuccessfulCheckAge',
            $locked['helpers']['renderer']
        );
        $this->assertTrue(
            $locked['helpers']['renderer_uses_state']
        );

        foreach ($locked['validation'] as $key => $value) {
            if ($key === 'invalid_value_text') {
                $this->assertSame('Not available', $value, $key);

                continue;
            }

            $this->assertTrue($value, $key);
        }

        $this->assertTrue(
            $locked['age_calculation']['negative_age_clamped_to_zero']
        );
        $this->assertSame(
            'floor',
            $locked['age_calculation']['rounding']
        );
    }

    public function test_formatting_update_accessibility_privacy_and_scope_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'Less than 1 minute',
            $locked['formatting']['under_one_minute_text']
        );
        $this->assertSame(
            60,
            $locked['formatting']['minutes_upper_exclusive']
        );
        $this->assertSame(
            1440,
            $locked['formatting']['hours_upper_exclusive_minutes']
        );
        $this->assertSame(
            999,
            $locked['formatting']['maximum_display_value']
        );
        $this->assertFalse(
            $locked['formatting']['relative_time_format_used']
        );

        $this->assertFalse(
            $locked['update_rules']['request_start_clears_previous_value']
        );
        $this->assertFalse(
            $locked['update_rules']['validated_unhealthy_response_updates']
        );
        $this->assertFalse(
            $locked['update_rules']['ignored_concurrent_request_updates']
        );
        $this->assertFalse(
            $locked['update_rules']['background_timer_updates']
        );
        $this->assertFalse(
            $locked['update_rules']['polling_updates']
        );

        foreach ([
            'validated_healthy_response_updates',
            'last_successful_check_state_set_before_age_render',
            'age_render_uses_same_completed_date',
            'updates_once_per_validated_healthy_request',
        ] as $key) {
            $this->assertTrue($locked['update_rules'][$key], $key);
        }

        foreach ($locked['accessibility'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($locked['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        foreach ([
            'partial_modified',
            'phase_116b_test_added',
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
            'last_successful_check_unchanged',
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
            'set_interval_added',
            'set_timeout_added',
            'request_animation_frame_added',
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
            'Phase 117A',
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
