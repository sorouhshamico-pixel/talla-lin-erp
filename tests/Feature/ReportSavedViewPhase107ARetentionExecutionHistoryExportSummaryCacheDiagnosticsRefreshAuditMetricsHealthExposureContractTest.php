<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase107ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthExposureContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-107a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-exposure-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-107a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-exposure-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 107A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            'c32bd71c516f3bde26be5fd7009fbfdb022fef5f',
            $document['baseline']['commit']
        );
        $this->assertSame(2120, $document['baseline']['tests']);
        $this->assertSame(20225, $document['baseline']['assertions']);
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

    public function test_route_controller_response_and_authorization_are_locked(): void
    {
        $contract = $this->document()['health_exposure_contract'];

        $this->assertSame('GET', $contract['route']['method']);
        $this->assertSame(
            'reports/saved-view-share-activity-retention/'
            . 'summary-cache-diagnostics/audit-metrics-health',
            $contract['route']['uri']
        );
        $this->assertSame(
            'reports.saved-view-share-activity-retention.'
            . 'summary-cache-diagnostics.audit-metrics-health',
            $contract['route']['name']
        );
        $this->assertSame(
            ['auth', 'permission:reports.view'],
            $contract['route']['middleware_order']
        );
        $this->assertFalse($contract['route']['rate_limiter_added']);

        $this->assertSame(
            'App\\Http\\Controllers\\Reports\\'
            . 'SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealthController',
            $contract['controller']['class']
        );
        $this->assertSame('__invoke', $contract['controller']['method']);
        $this->assertFalse($contract['controller']['response_wrapping']);

        $this->assertSame(200, $contract['response']['success_status']);
        $this->assertSame(
            8,
            $contract['response']['exact_property_count']
        );
        $this->assertSame(
            200,
            $contract['response']['healthy_status_code']
        );
        $this->assertSame(
            200,
            $contract['response']['unhealthy_status_code']
        );

        $this->assertTrue(
            $contract['authorization']['authentication_required']
        );
        $this->assertSame(
            'reports.view',
            $contract['authorization']['permission_required']
        );
        $this->assertSame(302, $contract['authorization']['guest_status']);
        $this->assertSame(
            403,
            $contract['authorization']['unauthorized_status']
        );
    }

    public function test_failure_privacy_compatibility_and_performance_are_locked(): void
    {
        $contract = $this->document()['health_exposure_contract'];

        $this->assertTrue(
            $contract['failure_behavior']
                ['health_class_exception_handled_by_health_class']
        );
        $this->assertFalse(
            $contract['failure_behavior']
                ['controller_exception_translation_added']
        );
        $this->assertTrue(
            $contract['failure_behavior']
                ['unhealthy_result_returned_as_json']
        );
        $this->assertFalse(
            $contract['failure_behavior']['exception_details_exposed']
        );

        foreach ($contract['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        foreach ($contract['compatibility'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        foreach ($contract['performance'] as $key => $value) {
            $this->assertSame(0, $value, $key);
        }
    }

    public function test_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $implementation =
            $document['health_exposure_contract']['planned_implementation'];

        $this->assertSame(
            'app/Http/Controllers/Reports/'
            . 'SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealthController.php',
            $implementation['new_controller']
        );
        $this->assertSame('routes/web.php', $implementation['route_file']);
        $this->assertSame(3, $implementation['maximum_modified_files']);

        foreach ([
            'modified_health_class',
            'modified_listener',
            'modified_event',
            'modified_middleware',
            'modified_logging_configuration',
            'modified_bootstrap',
            'modified_provider',
            'modified_view',
            'modified_layout',
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
            'Phase 107B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-107a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-exposure-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
