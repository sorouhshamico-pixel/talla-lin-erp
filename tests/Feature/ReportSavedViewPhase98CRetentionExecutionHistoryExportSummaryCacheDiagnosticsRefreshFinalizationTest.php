<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase98CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-98c-retention-execution-history-export-summary-cache-diagnostics-refresh-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-98c-retention-execution-history-export-summary-cache-diagnostics-refresh-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 98C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '7cb223733abaff0350cd37e479778affe3557b5b',
            $document['baseline']['commit']
        );
        $this->assertSame(1984, $document['baseline']['tests']);
        $this->assertSame(18221, $document['baseline']['assertions']);
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

    public function test_endpoint_response_and_ui_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'summaryCacheDiagnostics',
            $locked['controller_method']
        );
        $this->assertSame('GET', $locked['route']['method']);
        $this->assertSame(
            'manage_saved_view_share_activity_retention',
            $locked['route']['permission']
        );

        $this->assertSame('json', $locked['response']['format']);
        $this->assertSame(200, $locked['response']['status_code']);
        $this->assertFalse($locked['response']['wrapper_present']);
        $this->assertFalse($locked['response']['summary_present']);
        $this->assertFalse(
            $locked['response']['retention_status_present']
        );

        foreach ([
            'updates_in_place',
            'loading_state',
            'success_state',
            'failure_state',
            'concurrent_requests_prevented',
        ] as $key) {
            $this->assertTrue($locked['ui'][$key], $key);
        }

        foreach ([
            'full_page_reload',
            'summary_recomputation',
            'automatic_polling',
        ] as $key) {
            $this->assertFalse($locked['ui'][$key], $key);
        }
    }

    public function test_javascript_security_and_performance_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertFalse(
            $locked['javascript_placement']
                ['retention_view_contains_script']
        );
        $this->assertTrue(
            $locked['javascript_placement']
                ['shared_layout_contains_guarded_script']
        );
        $this->assertTrue(
            $locked['javascript_placement']
                ['script_runs_only_when_controls_exist']
        );
        $this->assertFalse(
            $locked['javascript_placement']['inner_html_used']
        );
        $this->assertTrue(
            $locked['javascript_placement']['text_content_used']
        );

        foreach ([
            'auth_required',
            'existing_permission_reused',
        ] as $key) {
            $this->assertTrue($locked['security'][$key], $key);
        }

        foreach ([
            'new_permission_added',
            'raw_generation_token_exposed',
            'raw_cache_key_exposed',
            'raw_filters_exposed',
            'actor_user_id_exposed',
            'history_payload_exposed',
            'exception_message_exposed',
            'stack_trace_exposed',
        ] as $key) {
            $this->assertFalse($locked['security'][$key], $key);
        }

        $this->assertSame(
            1,
            $locked['performance']['maximum_cache_reads_per_refresh']
        );
        $this->assertSame(
            0,
            $locked['performance']
                ['maximum_database_queries_per_refresh']
        );
        $this->assertSame(
            0,
            $locked['performance']['summary_queries_per_refresh']
        );
    }

    public function test_scope_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

        foreach ([
            'controller_modified',
            'route_modified',
            'retention_view_modified',
            'shared_layout_modified',
            'phase_98b_test_added',
        ] as $key) {
            $this->assertTrue(
                $locked['implementation_scope'][$key],
                $key
            );
        }

        foreach ([
            'service_changed',
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
            'Phase 99A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-98c-retention-execution-history-export-summary-cache-diagnostics-refresh-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
