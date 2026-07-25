<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase109CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationStatusSemanticsFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-109c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-status-semantics-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-109c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-status-semantics-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(file_get_contents($jsonPath), true);

        $this->assertIsArray($document);
        $this->assertSame('Phase 109C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            'cf0d0f04bd75c964e7b33918ec8ca0984fc4193c',
            $document['baseline']['commit']
        );
        $this->assertSame(2164, $document['baseline']['tests']);
        $this->assertSame(20951, $document['baseline']['assertions']);
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

    public function test_locked_states_rendering_and_validation(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            ['loading', 'healthy', 'unhealthy', 'unavailable'],
            array_keys($locked['overall_states'])
        );

        $this->assertSame(
            [
                'listener_discovered',
                'channel_configured',
                'channel_path_matches',
                'healthy',
            ],
            $locked['boolean_fields']
        );

        $this->assertSame('Yes', $locked['rendering']['true_text']);
        $this->assertSame('No', $locked['rendering']['false_text']);
        $this->assertSame(
            'Not available',
            $locked['rendering']['null_text']
        );
        $this->assertSame('0', $locked['rendering']['zero_text']);

        foreach ([
            'payload_must_be_object',
            'arrays_rejected',
            'all_eight_keys_required',
            'extra_keys_ignored',
            'boolean_type_validation',
            'non_negative_integer_validation',
            'nullable_integer_validation',
            'nullable_string_validation',
        ] as $key) {
            $this->assertTrue($locked['validation'][$key], $key);
        }

        $this->assertSame(
            'unavailable',
            $locked['validation']['invalid_payload_state']
        );
        $this->assertFalse(
            $locked['validation']['partial_invalid_payload_rendering']
        );
    }

    public function test_request_privacy_scope_and_compatibility_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame('GET', $locked['request_behavior']['method']);
        $this->assertSame(
            'same-origin',
            $locked['request_behavior']['credentials']
        );
        $this->assertSame(
            'application/json',
            $locked['request_behavior']['accept_header']
        );
        $this->assertSame(
            1,
            $locked['request_behavior']['initial_request_count']
        );
        $this->assertSame(
            1,
            $locked['request_behavior']['manual_refresh_request_count']
        );
        $this->assertTrue(
            $locked['request_behavior']['concurrent_requests_prevented']
        );

        foreach ([
            'polling_added',
            'retry_loop_added',
            'page_reload_added',
        ] as $key) {
            $this->assertFalse($locked['request_behavior'][$key], $key);
        }

        foreach ($locked['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        foreach ([
            'partial_modified',
            'phase_109b_test_added',
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
            'Phase 110A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-109c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-status-semantics-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
