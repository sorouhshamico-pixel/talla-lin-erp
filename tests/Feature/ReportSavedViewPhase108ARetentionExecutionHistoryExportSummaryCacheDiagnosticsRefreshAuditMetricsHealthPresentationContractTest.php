<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase108ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-108a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-108a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 108A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            'ecbf3111d8ff57a96d7e96bb99c02a4197ad727d',
            $document['baseline']['commit']
        );
        $this->assertSame(2136, $document['baseline']['tests']);
        $this->assertSame(20490, $document['baseline']['assertions']);
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

    public function test_placement_presentation_client_behavior_and_states_are_locked(): void
    {
        $contract = $this->document()['health_presentation_contract'];

        $this->assertSame(
            'resources/views/reports/saved-views/'
            . 'share-activity-retention.blade.php',
            $contract['placement']['view']
        );
        $this->assertTrue($contract['placement']['partial_required']);
        $this->assertSame(
            'after_summary_cache_diagnostics_section_before_privacy_notice',
            $contract['placement']['position']
        );
        $this->assertSame(
            'Privacy notice: context and updated_at are excluded from exports.',
            $contract['placement']['insertion_anchor']
        );
        $this->assertSame(
            'resources/views/reports/saved-views/partials/'
            . 'share-activity-retention-audit-metrics-health.blade.php',
            $contract['placement']['partial']
        );

        $this->assertSame(
            8,
            $contract['presentation']['exact_field_count']
        );
        $this->assertFalse(
            $contract['presentation']['raw_json_visible']
        );
        $this->assertFalse(
            $contract['presentation']['exception_details_visible']
        );

        $this->assertSame(
            'GET',
            $contract['client_behavior']['request_method']
        );
        $this->assertTrue(
            $contract['client_behavior']['automatic_request_on_page_load']
        );
        $this->assertTrue(
            $contract['client_behavior']['manual_refresh_button']
        );
        $this->assertTrue(
            $contract['client_behavior']['concurrent_requests_prevented']
        );
        $this->assertFalse(
            $contract['client_behavior']['polling_added']
        );
        $this->assertFalse(
            $contract['client_behavior']['retry_loop_added']
        );
        $this->assertSame(
            'partial_inline_script',
            $contract['client_behavior']['script_location']
        );
        $this->assertSame(
            'same-origin',
            $contract['client_behavior']['fetch_credentials']
        );
        $this->assertSame(
            'application/json',
            $contract['client_behavior']['accept_header']
        );

        $this->assertSame(
            [
                'loading',
                'healthy',
                'unhealthy',
                'unavailable',
            ],
            array_keys($contract['states'])
        );
    }

    public function test_accessibility_privacy_compatibility_and_performance_are_locked(): void
    {
        $contract = $this->document()['health_presentation_contract'];

        $this->assertSame(
            'status',
            $contract['accessibility']['status_region_role']
        );
        $this->assertSame(
            'polite',
            $contract['accessibility']['aria_live']
        );
        $this->assertSame(
            'button',
            $contract['accessibility']['button_type']
        );
        $this->assertTrue(
            $contract['accessibility']['table_headers_present']
        );
        $this->assertTrue(
            $contract['accessibility']['color_only_status_forbidden']
        );

        foreach ($contract['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        foreach ($contract['compatibility'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        $this->assertSame(
            1,
            $contract['performance']['requests_on_initial_load']
        );
        $this->assertSame(
            1,
            $contract['performance']['requests_per_manual_refresh']
        );
        $this->assertSame(
            0,
            $contract['performance']['polling_requests']
        );

        foreach ([
            'additional_database_queries',
            'additional_cache_reads',
            'additional_cache_writes',
            'event_dispatches',
            'log_writes',
        ] as $key) {
            $this->assertSame(
                0,
                $contract['performance'][$key],
                $key
            );
        }
    }

    public function test_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $implementation =
            $document['health_presentation_contract']['planned_implementation'];

        $this->assertSame(
            'resources/views/reports/saved-views/partials/'
            . 'share-activity-retention-audit-metrics-health.blade.php',
            $implementation['new_partial']
        );
        $this->assertSame(
            'resources/views/reports/saved-views/'
            . 'share-activity-retention.blade.php',
            $implementation['parent_view']
        );
        $this->assertSame(3, $implementation['maximum_modified_files']);
        $this->assertSame(
            'Privacy notice: context and updated_at are excluded from exports.',
            $implementation['parent_view_insertion_anchor']
        );

        foreach ([
            'modified_endpoint_controller',
            'modified_health_class',
            'modified_route',
            'modified_listener',
            'modified_event',
            'modified_middleware',
            'modified_logging_configuration',
            'modified_layout',
            'modified_provider',
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
            'Phase 108B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-108a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
