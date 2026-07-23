<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase106CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-106c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-106c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 106C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '66b23795f07bc8cd01bc339ac5785832b5e079c1',
            $document['baseline']['commit']
        );
        $this->assertSame(2115, $document['baseline']['tests']);
        $this->assertSame(20117, $document['baseline']['assertions']);
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
            'event_changes_expected',
            'listener_changes_expected',
            'logging_configuration_changes_expected',
            'health_class_changes_expected',
        ] as $key) {
            $this->assertFalse($scope[$key], $key);
        }

        $this->assertTrue($scope['documentation_and_tests_only']);
    }

    public function test_health_shape_rule_and_failure_behavior_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'App\\Support\\'
            . 'SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealth',
            $locked['health_class']['class']
        );
        $this->assertSame('status', $locked['health_class']['method']);
        $this->assertSame('array', $locked['health_class']['return_type']);
        $this->assertTrue($locked['health_class']['side_effect_free']);
        $this->assertFalse($locked['health_class']['throws']);

        $this->assertSame(
            [
                'listener_discovered',
                'listener_count',
                'channel_configured',
                'channel_driver',
                'channel_level',
                'channel_retention_days',
                'channel_path_matches',
                'healthy',
            ],
            $locked['status_shape']['properties']
        );
        $this->assertSame(
            8,
            $locked['status_shape']['exact_property_count']
        );

        $this->assertTrue(
            $locked['healthy_rule']['all_conditions_required']
        );
        $this->assertSame(
            1,
            $locked['healthy_rule']['listener_count']
        );
        $this->assertSame(
            'daily',
            $locked['healthy_rule']['channel_driver']
        );
        $this->assertSame(
            'info',
            $locked['healthy_rule']['channel_level']
        );
        $this->assertSame(
            14,
            $locked['healthy_rule']['channel_retention_days']
        );

        foreach ($locked['failure_behavior'] as $key => $value) {
            if ($key === 'exception_details_exposed'
                || $key === 'throws_to_caller') {
                $this->assertFalse($value, $key);

                continue;
            }

            $this->assertTrue($value, $key);
        }
    }

    public function test_privacy_performance_scope_and_compatibility_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        foreach ($locked['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        foreach ($locked['performance'] as $key => $value) {
            $this->assertSame(0, $value, $key);
        }

        $this->assertTrue(
            $locked['implementation_scope']['health_class_added']
        );
        $this->assertTrue(
            $locked['implementation_scope']['phase_106b_test_added']
        );

        foreach ([
            'listener_modified',
            'event_modified',
            'middleware_modified',
            'logging_configuration_modified',
            'bootstrap_changed',
            'route_changed',
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
    }

    public function test_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();

        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse(
            $document['workflow']['post_commit_full_suite']
        );
        $this->assertSame(
            'Phase 107A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-106c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
