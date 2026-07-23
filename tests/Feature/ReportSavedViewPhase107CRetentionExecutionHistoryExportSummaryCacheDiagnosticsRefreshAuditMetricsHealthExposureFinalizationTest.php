<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase107CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthExposureFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-107c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-exposure-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-107c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-exposure-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 107C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '963eab5c741090cffcdd78cf6a22f33a0f6ac05f',
            $document['baseline']['commit']
        );
        $this->assertSame(2131, $document['baseline']['tests']);
        $this->assertSame(20386, $document['baseline']['assertions']);
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

    public function test_route_controller_and_response_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame('GET', $locked['route']['method']);
        $this->assertSame(
            'reports/saved-view-share-activity-retention/'
            . 'summary-cache-diagnostics/audit-metrics-health',
            $locked['route']['uri']
        );
        $this->assertSame(
            [
                'web',
                'auth',
                'can:manage_saved_view_share_activity_retention',
            ],
            $locked['route']['middleware']
        );
        $this->assertFalse(
            $locked['route']['route_specific_middleware_added']
        );
        $this->assertFalse($locked['route']['rate_limiter_added']);
        $this->assertFalse($locked['route']['audit_middleware_added']);

        $this->assertSame('__invoke', $locked['controller']['method']);
        $this->assertFalse(
            $locked['controller']['response_wrapping']
        );
        $this->assertFalse(
            $locked['controller']['response_transformation']
        );
        $this->assertFalse(
            $locked['controller']['exception_translation_added']
        );

        $this->assertSame(200, $locked['response']['healthy_status']);
        $this->assertSame(200, $locked['response']['unhealthy_status']);
        $this->assertSame(302, $locked['response']['guest_status']);
        $this->assertSame(
            'redirect_to_login',
            $locked['response']['guest_behavior']
        );
        $this->assertSame(
            403,
            $locked['response']['unauthorized_status']
        );
        $this->assertSame(
            8,
            $locked['response']['exact_property_count']
        );
    }

    public function test_testability_privacy_performance_scope_and_compatibility_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertTrue(
            $locked['testability']['health_class_is_final']
        );
        $this->assertFalse(
            $locked['testability']['direct_final_class_mocking_used']
        );
        $this->assertTrue(
            $locked['testability']['real_health_instance_used']
        );
        $this->assertTrue(
            $locked['testability']['mocked_dispatcher_used']
        );
        $this->assertTrue(
            $locked['testability']['exact_route_block_source_guard_used']
        );

        foreach ($locked['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        foreach ($locked['performance'] as $key => $value) {
            $this->assertSame(0, $value, $key);
        }

        foreach ([
            'controller_added',
            'route_added',
            'phase_107b_test_added',
        ] as $key) {
            $this->assertTrue(
                $locked['implementation_scope'][$key],
                $key
            );
        }

        foreach ([
            'health_class_modified',
            'listener_modified',
            'event_modified',
            'middleware_modified',
            'logging_configuration_modified',
            'bootstrap_changed',
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
            'Phase 108A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-107c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-exposure-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
