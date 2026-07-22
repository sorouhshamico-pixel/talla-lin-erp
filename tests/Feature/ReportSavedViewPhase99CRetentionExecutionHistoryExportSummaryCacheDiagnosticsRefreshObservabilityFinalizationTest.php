<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase99CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshObservabilityFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-99c-retention-execution-history-export-summary-cache-diagnostics-refresh-observability-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-99c-retention-execution-history-export-summary-cache-diagnostics-refresh-observability-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 99C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            'f98e15583588fccdb16f124a6d75816bc7d5e9c6',
            $document['baseline']['commit']
        );
        $this->assertSame(2000, $document['baseline']['tests']);
        $this->assertSame(18428, $document['baseline']['assertions']);
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
        ] as $key) {
            $this->assertFalse($scope[$key], $key);
        }

        $this->assertTrue($scope['documentation_and_tests_only']);
    }

    public function test_events_levels_and_context_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'saved_view_retention.summary_cache_diagnostics.refresh_succeeded',
            $locked['events']['success']
        );
        $this->assertSame(
            'saved_view_retention.summary_cache_diagnostics.refresh_failed',
            $locked['events']['failure']
        );
        $this->assertSame('debug', $locked['levels']['success']);
        $this->assertSame('warning', $locked['levels']['failure']);

        $this->assertSame(
            [
                'event',
                'cache_store',
                'cache_read_available',
                'generation_present',
                'generation_source',
                'observability_enabled',
            ],
            $locked['success_context']
        );
        $this->assertSame(
            ['event', 'failure_reason_class'],
            $locked['failure_context']
        );
        $this->assertSame(
            [
                'raw_generation_token',
                'raw_cache_key',
                'raw_filters',
                'actor_user_id',
                'history_payload',
                'exception_message',
                'stack_trace',
                'request_headers',
                'session_id',
            ],
            $locked['forbidden_context']
        );
    }

    public function test_behavior_and_performance_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            1,
            $locked['behavior']['diagnostics_service_calls_per_request']
        );

        foreach ([
            'success_payload_unchanged',
            'success_status_code_unchanged',
            'service_exception_rethrown_unchanged',
            'logging_failure_swallowed',
        ] as $key) {
            $this->assertTrue($locked['behavior'][$key], $key);
        }

        foreach ([
            'logging_failure_changes_success_response',
            'logging_failure_changes_service_exception',
        ] as $key) {
            $this->assertFalse($locked['behavior'][$key], $key);
        }

        foreach ($locked['performance'] as $key => $value) {
            $this->assertSame(0, $value, $key);
        }
    }

    public function test_scope_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

        $this->assertTrue(
            $locked['implementation_scope']['controller_modified']
        );
        $this->assertTrue(
            $locked['implementation_scope']['phase_99b_test_added']
        );

        foreach ([
            'service_changed',
            'route_changed',
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

        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse(
            $document['workflow']['post_commit_full_suite']
        );
        $this->assertSame(
            'Phase 100A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-99c-retention-execution-history-export-summary-cache-diagnostics-refresh-observability-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
