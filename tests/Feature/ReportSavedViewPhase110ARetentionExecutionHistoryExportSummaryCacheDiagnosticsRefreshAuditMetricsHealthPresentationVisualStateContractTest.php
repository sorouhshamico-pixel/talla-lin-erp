<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase110ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationVisualStateContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-110a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-visual-state-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-110a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-visual-state-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(file_get_contents($jsonPath), true);

        $this->assertIsArray($document);
        $this->assertSame('Phase 110A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '3688ec34797b7bc0d232f9bedcb394ee1e14c1a1',
            $document['baseline']['commit']
        );
        $this->assertSame(2169, $document['baseline']['tests']);
        $this->assertSame(21048, $document['baseline']['assertions']);
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

    public function test_state_attribute_transitions_and_indicator_are_locked(): void
    {
        $contract = $this->document()['visual_state_contract'];

        $this->assertSame(
            'data-health-state',
            $contract['state_attribute']['attribute']
        );
        $this->assertSame(
            ['loading', 'healthy', 'unhealthy', 'unavailable'],
            $contract['state_attribute']['allowed_values']
        );
        $this->assertSame(
            'loading',
            $contract['state_attribute']['initial_value']
        );
        $this->assertTrue(
            $contract['state_attribute']['unknown_value_forbidden']
        );
        $this->assertTrue(
            $contract['state_attribute']['attribute_removed_forbidden']
        );

        $this->assertSame(
            [
                'request_start' => 'loading',
                'validated_healthy_payload' => 'healthy',
                'validated_unhealthy_payload' => 'unhealthy',
                'request_or_parse_failure' => 'unavailable',
                'invalid_payload' => 'unavailable',
            ],
            $contract['state_transitions']
        );

        $this->assertTrue($contract['status_indicator']['required']);
        $this->assertTrue(
            $contract['status_indicator']['aria_hidden']
        );
        $this->assertTrue(
            $contract['status_indicator']['textual_state_required']
        );
        $this->assertTrue(
            $contract['status_indicator']['color_only_forbidden']
        );
    }

    public function test_class_accessibility_behavior_and_privacy_are_locked(): void
    {
        $contract = $this->document()['visual_state_contract'];

        $this->assertSame(
            [
                'loading' => 'is-loading',
                'healthy' => 'is-healthy',
                'unhealthy' => 'is-unhealthy',
                'unavailable' => 'is-unavailable',
            ],
            $contract['class_semantics']['state_classes']
        );
        $this->assertTrue(
            $contract['class_semantics']['exactly_one_state_class']
        );
        $this->assertTrue(
            $contract['class_semantics']['stale_state_classes_removed_before_apply']
        );
        $this->assertTrue(
            $contract['class_semantics']['inline_style_updates_forbidden']
        );

        $this->assertTrue(
            $contract['accessibility']['existing_status_region_preserved']
        );
        $this->assertTrue(
            $contract['accessibility']['indicator_aria_hidden']
        );
        $this->assertTrue(
            $contract['accessibility']['status_message_remains_primary_announcement']
        );
        $this->assertTrue(
            $contract['accessibility']['visual_state_not_required_to_understand_health']
        );

        foreach ([
            'loading_applied_before_fetch',
            'healthy_applied_after_validated_payload',
            'unhealthy_applied_after_validated_payload',
            'unavailable_applied_on_failure',
        ] as $key) {
            $this->assertTrue($contract['behavior'][$key], $key);
        }

        foreach ($contract['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }
    }

    public function test_compatibility_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['visual_state_contract'];

        foreach ($contract['compatibility'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        $implementation = $contract['planned_implementation'];

        $this->assertSame(2, $implementation['maximum_modified_files']);
        $this->assertFalse($implementation['parent_view_modified']);

        foreach ([
            'endpoint_controller_modified',
            'route_modified',
            'health_class_modified',
            'listener_modified',
            'event_modified',
            'middleware_modified',
            'logging_configuration_modified',
            'layout_modified',
            'provider_modified',
            'database_changes_expected',
            'migration_changes_expected',
            'model_changes_expected',
        ] as $key) {
            $this->assertFalse($implementation[$key], $key);
        }

        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse(
            $document['workflow']['post_commit_full_suite']
        );
        $this->assertSame(
            'Phase 110B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-110a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-visual-state-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
