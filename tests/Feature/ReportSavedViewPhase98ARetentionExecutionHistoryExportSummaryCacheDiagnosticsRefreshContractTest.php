<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase98ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-98a-retention-execution-history-export-summary-cache-diagnostics-refresh-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-98a-retention-execution-history-export-summary-cache-diagnostics-refresh-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 98A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            'd4ff639482e1dc3aa3e16b107f03fa075f7aaba9',
            $document['baseline']['commit']
        );
        $this->assertSame(1973, $document['baseline']['tests']);
        $this->assertSame(18093, $document['baseline']['assertions']);
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
            'javascript_changes_expected',
        ] as $key) {
            $this->assertFalse($scope[$key], $key);
        }

        $this->assertTrue($scope['documentation_and_tests_only']);
    }

    public function test_route_response_and_authorization_are_locked(): void
    {
        $contract = $this->document()['refresh_contract'];

        $this->assertSame(
            'summaryCacheDiagnostics',
            $contract['planned_controller_method']
        );
        $this->assertSame(
            'GET',
            $contract['planned_route']['method']
        );
        $this->assertSame(
            'reports.saved-view-share-activity-retention.summary-cache-diagnostics',
            $contract['planned_route']['name']
        );
        $this->assertSame('json', $contract['response']['format']);
        $this->assertSame(200, $contract['response']['status_code']);
        $this->assertFalse($contract['response']['wrapper_required']);
        $this->assertFalse($contract['response']['summary_included']);
        $this->assertFalse(
            $contract['response']['retention_status_included']
        );

        $this->assertSame(
            'manage_saved_view_share_activity_retention',
            $contract['authorization']['permission']
        );
        $this->assertTrue(
            $contract['authorization']['existing_middleware_reused']
        );
        $this->assertFalse(
            $contract['authorization']['new_permission_required']
        );
    }

    public function test_view_client_privacy_and_performance_are_locked(): void
    {
        $contract = $this->document()['refresh_contract'];

        foreach ([
            'refresh_button_present',
            'full_page_reload_required',
            'summary_recomputation_required',
            'diagnostics_section_updated_in_place',
            'loading_state_required',
            'success_state_required',
            'failure_state_required',
            'button_disabled_while_loading',
        ] as $key) {
            if (in_array($key, [
                'full_page_reload_required',
                'summary_recomputation_required',
            ], true)) {
                $this->assertFalse(
                    $contract['view_behavior'][$key],
                    $key
                );
            } else {
                $this->assertTrue(
                    $contract['view_behavior'][$key],
                    $key
                );
            }
        }

        $this->assertSame(
            'GET',
            $contract['client_behavior']['fetch_method']
        );
        $this->assertFalse(
            $contract['client_behavior']['automatic_polling']
        );
        $this->assertTrue(
            $contract['client_behavior']['concurrent_requests_prevented']
        );
        $this->assertFalse(
            $contract['client_behavior']['raw_html_from_server_inserted']
        );
        $this->assertTrue(
            $contract['client_behavior']['text_content_updates_required']
        );

        foreach ($contract['privacy'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertSame(
            1,
            $contract['performance']['maximum_cache_reads_per_refresh']
        );
        $this->assertSame(
            0,
            $contract['performance']['maximum_database_queries_per_refresh']
        );
        $this->assertSame(
            0,
            $contract['performance']['summary_queries_per_refresh']
        );
    }

    public function test_scope_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['refresh_contract'];

        foreach ([
            'export_service_changes_expected',
            'history_service_changes_expected',
            'database_changes_expected',
            'migration_changes_expected',
            'model_changes_expected',
        ] as $key) {
            $this->assertFalse(
                $contract['planned_implementation'][$key],
                $key
            );
        }

        foreach ($contract['compatibility'] as $key => $value) {
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
            'Phase 98B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-98a-retention-execution-history-export-summary-cache-diagnostics-refresh-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
