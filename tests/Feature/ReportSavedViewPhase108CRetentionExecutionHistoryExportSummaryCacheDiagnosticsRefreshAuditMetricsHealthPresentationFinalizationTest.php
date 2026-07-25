<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase108CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-108c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-108c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(file_get_contents($jsonPath), true);

        $this->assertIsArray($document);
        $this->assertSame('Phase 108C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            'b607e9ca54e3e1bd6afbc1fd7d8b3930046077cb',
            $document['baseline']['commit']
        );
        $this->assertSame(2147, $document['baseline']['tests']);
        $this->assertSame(20676, $document['baseline']['assertions']);
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

    public function test_locked_presentation_contract(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertTrue($locked['parent_view']['partial_included_once']);
        $this->assertTrue($locked['partial']['read_only']);
        $this->assertFalse($locked['partial']['raw_json_visible']);
        $this->assertFalse($locked['partial']['exception_details_visible']);

        $this->assertSame('GET', $locked['endpoint']['method']);
        $this->assertSame('same-origin', $locked['endpoint']['credentials']);
        $this->assertSame(
            'application/json',
            $locked['endpoint']['accept_header']
        );

        $this->assertSame(8, $locked['fields']['exact_property_count']);
        $this->assertSame(8, $locked['fields']['table_row_count']);

        $this->assertSame(
            ['loading', 'healthy', 'unhealthy', 'unavailable'],
            array_keys($locked['states'])
        );
    }

    public function test_behavior_privacy_performance_and_compatibility_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(1, $locked['client_behavior']['initial_request_count']);
        $this->assertSame(
            1,
            $locked['client_behavior']['manual_refresh_request_count']
        );
        $this->assertTrue(
            $locked['client_behavior']['concurrent_requests_prevented']
        );
        $this->assertTrue(
            $locked['client_behavior']['refresh_button_disabled_during_request']
        );
        $this->assertTrue(
            $locked['client_behavior']['duplicate_initialization_prevented']
        );

        foreach ([
            'polling_added',
            'retry_loop_added',
            'page_reload_added',
            'timeout_added',
        ] as $key) {
            $this->assertFalse($locked['client_behavior'][$key], $key);
        }

        foreach ($locked['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        foreach ([
            'polling_requests',
            'additional_database_queries',
            'additional_cache_reads',
            'additional_cache_writes',
            'event_dispatches',
            'log_writes',
        ] as $key) {
            $this->assertSame(0, $locked['performance'][$key], $key);
        }

        foreach ($locked['compatibility'] as $key => $value) {
            $this->assertTrue($value, $key);
        }
    }

    public function test_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $scope = $document['locked_implementation']['implementation_scope'];

        foreach ([
            'partial_added',
            'parent_view_modified',
            'phase_108b_test_added',
        ] as $key) {
            $this->assertTrue($scope[$key], $key);
        }

        foreach ([
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
            $this->assertFalse($scope[$key], $key);
        }

        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse($document['workflow']['post_commit_full_suite']);
        $this->assertSame(
            'Phase 109A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-108c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
