<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase110CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationVisualStateFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-110c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-visual-state-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-110c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-visual-state-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(file_get_contents($jsonPath), true);

        $this->assertIsArray($document);
        $this->assertSame('Phase 110C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            'e17822b7848cfcd3e0b40381ccb2508fb55777db',
            $document['baseline']['commit']
        );
        $this->assertSame(2181, $document['baseline']['tests']);
        $this->assertSame(21216, $document['baseline']['assertions']);
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

    public function test_panel_indicator_states_and_transitions_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'data-health-state',
            $locked['panel']['state_attribute']
        );
        $this->assertSame('loading', $locked['panel']['initial_state']);
        $this->assertSame('is-loading', $locked['panel']['initial_class']);

        $this->assertTrue($locked['indicator']['aria_hidden']);
        $this->assertSame('Loading', $locked['indicator']['initial_text']);

        $this->assertSame(
            ['loading', 'healthy', 'unhealthy', 'unavailable'],
            array_keys($locked['allowed_states'])
        );

        $this->assertSame(
            'Audit metrics pipeline is healthy.',
            $locked['allowed_states']['healthy']['status_message']
        );
        $this->assertSame(
            'Audit metrics health status is unavailable.',
            $locked['allowed_states']['unavailable']['status_message']
        );

        foreach ([
            'stale_state_classes_removed',
            'exactly_one_state_class_after_transition',
            'state_attribute_always_present',
        ] as $key) {
            $this->assertTrue($locked['transition_rules'][$key], $key);
        }
    }

    public function test_accessibility_privacy_and_behavior_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'status',
            $locked['accessibility']['status_region_role']
        );
        $this->assertSame(
            'polite',
            $locked['accessibility']['status_region_aria_live']
        );

        foreach ([
            'indicator_aria_hidden',
            'textual_status_preserved',
            'color_only_semantics_forbidden',
            'visual_state_optional_for_understanding',
        ] as $key) {
            $this->assertTrue($locked['accessibility'][$key], $key);
        }

        foreach ($locked['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        $this->assertFalse($locked['behavior']['inline_style_mutation']);
        $this->assertSame('GET', $locked['behavior']['request_method']);
        $this->assertSame(
            'same-origin',
            $locked['behavior']['credentials']
        );
        $this->assertSame(
            'application/json',
            $locked['behavior']['accept_header']
        );
        $this->assertSame(
            1,
            $locked['behavior']['initial_request_count']
        );
        $this->assertSame(
            1,
            $locked['behavior']['manual_refresh_request_count']
        );
        $this->assertTrue(
            $locked['behavior']['concurrent_requests_prevented']
        );
        $this->assertTrue(
            $locked['behavior']['payload_validation_unchanged']
        );
        $this->assertTrue(
            $locked['behavior']['status_messages_unchanged']
        );

        foreach ([
            'polling_added',
            'retry_loop_added',
            'page_reload_added',
        ] as $key) {
            $this->assertFalse($locked['behavior'][$key], $key);
        }
    }

    public function test_scope_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

        foreach ([
            'partial_modified',
            'phase_110b_test_added',
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
            'Phase 111A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-110c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-visual-state-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
