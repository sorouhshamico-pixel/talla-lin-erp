<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase100CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshRateLimitingFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-100c-retention-execution-history-export-summary-cache-diagnostics-refresh-rate-limiting-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-100c-retention-execution-history-export-summary-cache-diagnostics-refresh-rate-limiting-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 100C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '2a5d9dece7e3119d3acf372646cc1473acfa940e',
            $document['baseline']['commit']
        );
        $this->assertSame(2015, $document['baseline']['tests']);
        $this->assertSame(18628, $document['baseline']['assertions']);
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
        ] as $key) {
            $this->assertFalse($scope[$key], $key);
        }

        $this->assertTrue($scope['documentation_and_tests_only']);
    }

    public function test_limiter_registration_and_route_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'saved-view-retention-summary-cache-diagnostics-refresh',
            $locked['limiter']['name']
        );
        $this->assertSame(30, $locked['limiter']['maximum_attempts']);
        $this->assertSame(60, $locked['limiter']['decay_seconds']);
        $this->assertSame(
            'sha256',
            $locked['limiter']['key_hash_algorithm']
        );
        $this->assertFalse($locked['limiter']['raw_identity_exposed']);

        $this->assertSame(
            'App\\Providers\\AppServiceProvider',
            $locked['registration']['provider']
        );
        $this->assertSame(
            'RateLimiter::for',
            $locked['registration']['framework_api']
        );
        $this->assertSame(
            'Limit::perMinute',
            $locked['registration']['limit_factory']
        );

        $this->assertSame('GET', $locked['route']['method']);
        $this->assertTrue(
            $locked['route']['authentication_required']
        );
        $this->assertSame(
            'manage_saved_view_share_activity_retention',
            $locked['route']['permission']
        );
        $this->assertSame(
            'throttle:saved-view-retention-summary-cache-diagnostics-refresh',
            $locked['route']['middleware']
        );
    }

    public function test_responses_behavior_and_performance_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            200,
            $locked['responses']['allowed_status_code']
        );
        $this->assertSame(
            429,
            $locked['responses']['limited_status_code']
        );
        $this->assertTrue(
            $locked['responses']['allowed_payload_unchanged']
        );
        $this->assertTrue(
            $locked['responses']['limited_response_framework_default']
        );
        $this->assertFalse(
            $locked['responses']
                ['limited_response_contains_diagnostics_payload']
        );
        $this->assertTrue(
            $locked['responses']['retry_after_header_expected']
        );

        foreach ([
            'manual_refresh_only',
            'automatic_polling_absent',
            'full_page_reload_absent',
            'client_concurrency_guard_preserved',
            'allowed_request_calls_diagnostics_service_once',
            'observability_events_unchanged',
            'diagnostics_payload_unchanged',
        ] as $key) {
            $this->assertTrue($locked['behavior'][$key], $key);
        }

        $this->assertFalse(
            $locked['behavior']['limited_request_calls_diagnostics_service']
        );

        foreach ([
            'additional_database_queries',
            'additional_model_hydration',
            'additional_summary_queries',
            'limited_request_diagnostics_cache_reads',
        ] as $key) {
            $this->assertSame(0, $locked['performance'][$key], $key);
        }

        $this->assertTrue(
            $locked['performance']
                ['allowed_request_diagnostics_cache_reads_unchanged']
        );
    }

    public function test_scope_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

        foreach ([
            'provider_modified',
            'routes_modified',
            'phase_100b_test_added',
        ] as $key) {
            $this->assertTrue(
                $locked['implementation_scope'][$key],
                $key
            );
        }

        foreach ([
            'controller_changed',
            'service_changed',
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
            'Phase 101A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-100c-retention-execution-history-export-summary-cache-diagnostics-refresh-rate-limiting-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
