<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase117CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationSuccessfulCheckFreshnessStateFinalizationTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-117c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-successful-check-freshness-state-finalization.json';

    public function test_finalization_documents_exist_and_baseline_is_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-117c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-successful-check-freshness-state-finalization.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 117C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '95db37f8eb60a5f6c55206ec5beb97aa4e6e10da',
            $document['baseline']['commit']
        );
        $this->assertSame(2302, $document['baseline']['tests']);
        $this->assertSame(23199, $document['baseline']['assertions']);
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

    public function test_element_formatter_calculation_states_and_renderer_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'retention-audit-metrics-health-successful-check-freshness',
            $locked['element']['id']
        );
        $this->assertSame(
            'Successful check freshness:',
            $locked['element']['prefix']
        );
        $this->assertSame(
            'Unavailable',
            $locked['element']['initial_text']
        );
        $this->assertSame(
            'unavailable',
            $locked['element']['initial_data_freshness_state']
        );

        $this->assertSame(
            'formatSuccessfulCheckFreshness',
            $locked['formatter']['name']
        );
        $this->assertTrue(
            $locked['formatter']['valid_date_instances_required']
        );
        $this->assertSame(
            'unavailable',
            $locked['formatter']['invalid_result_state']
        );

        $this->assertSame(
            'lastSuccessfulCheckAt',
            $locked['calculation']['state_source']
        );
        $this->assertTrue(
            $locked['calculation']['negative_age_clamped_to_zero']
        );
        $this->assertFalse(
            $locked['calculation']['rendered_age_text_parsed']
        );

        $this->assertSame(
            14,
            $locked['states']['fresh']['maximum_age_minutes_inclusive']
        );
        $this->assertSame(
            15,
            $locked['states']['stale']['minimum_age_minutes']
        );
        $this->assertTrue(
            $locked['states']['unavailable']['used_without_valid_dates']
        );

        $this->assertSame(
            'updateSuccessfulCheckFreshness',
            $locked['renderer']['name']
        );
        $this->assertTrue(
            $locked['renderer']['writes_text_content']
        );
        $this->assertTrue(
            $locked['renderer']['writes_dataset_freshness_state']
        );
        $this->assertSame(
            ['fresh', 'stale', 'unavailable'],
            $locked['renderer']['allowed_values']
        );
    }

    public function test_update_visual_accessibility_privacy_and_scope_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

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
            'last_successful_check_state_set_before_freshness_render',
            'freshness_render_uses_same_completed_date',
            'updates_once_per_validated_healthy_request',
        ] as $key) {
            $this->assertTrue($locked['update_rules'][$key], $key);
        }

        foreach ($locked['visual_state'] as $key => $value) {
            if (in_array($key, [
                'freshness_text_required',
                'color_only_meaning_forbidden',
            ], true)) {
                $this->assertTrue($value, $key);

                continue;
            }

            $this->assertFalse($value, $key);
        }

        foreach ($locked['accessibility'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($locked['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        foreach ([
            'partial_modified',
            'phase_117b_test_added',
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
            'successful_check_age_unchanged',
            'last_successful_check_unchanged',
            'consecutive_failure_counter_unchanged',
            'response_status_unchanged',
            'request_duration_unchanged',
            'refresh_timestamp_unchanged',
            'health_status_messages_unchanged',
            'panel_visual_state_unchanged',
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
            'Phase 118A',
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
