<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase120CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshFailureCounterFinalizationTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-120c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-failure-counter-finalization.json';

    public function test_finalization_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-120c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-failure-counter-finalization.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 120C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            'd199074847a61ac01e4127cc2d7d7f0476e79df1',
            $document['baseline']['commit']
        );
        $this->assertSame(2350, $document['baseline']['tests']);
        $this->assertSame(23893, $document['baseline']['assertions']);
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

    public function test_element_state_helpers_and_classification_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-failures',
            $locked['element']['id']
        );
        $this->assertSame(
            'manualRefreshFailures',
            $locked['state']['variable']
        );
        $this->assertSame(0, $locked['state']['initial_value']);
        $this->assertSame(999, $locked['state']['maximum']);
        $this->assertTrue($locked['state']['client_memory_only']);
        $this->assertFalse($locked['state']['persistent_storage_used']);

        $this->assertSame(
            'renderManualRefreshFailures',
            $locked['helpers']['renderer']
        );
        $this->assertSame(
            'recordManualRefreshFailure',
            $locked['helpers']['recorder']
        );
        $this->assertTrue(
            $locked['helpers']['invalid_value_normalizes_to_zero']
        );

        foreach ([
            'http_error_counts',
            'network_failure_counts',
            'json_parse_failure_counts',
            'payload_validation_failure_counts',
        ] as $key) {
            $this->assertTrue($locked['classification'][$key], $key);
        }

        foreach ([
            'validated_healthy_counts',
            'validated_unhealthy_counts',
            'initial_automatic_request_counts',
            'ignored_concurrent_manual_request_counts',
        ] as $key) {
            $this->assertFalse($locked['classification'][$key], $key);
        }
    }

    public function test_update_order_and_legacy_contract_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        foreach ($locked['update_order'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertTrue($locked['legacy_contract']['preserved']);
        $this->assertSame(
            'const loadHealth = async () => {',
            $locked['legacy_contract']['load_health_signature']
        );
        $this->assertSame(
            "refresh.addEventListener('click', loadHealth);",
            $locked['legacy_contract']['refresh_listener']
        );
        $this->assertSame(
            'loadHealth();',
            $locked['legacy_contract']['initial_load']
        );
    }

    public function test_compatibility_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

        foreach ($locked['compatibility'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        $this->assertTrue(
            $locked['implementation_scope']['partial_modified']
        );
        $this->assertTrue(
            $locked['implementation_scope']['phase_120b_test_added']
        );
        $this->assertSame(
            2,
            $locked['implementation_scope']['maximum_modified_files']
        );

        foreach ([
            'parent_view_modified',
            'controller_modified',
            'route_modified',
            'health_class_modified',
            'database_modified',
            'migration_modified',
            'model_modified',
        ] as $key) {
            $this->assertFalse(
                $locked['implementation_scope'][$key],
                $key
            );
        }

        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse(
            $document['workflow']['post_commit_full_suite']
        );
        $this->assertSame(
            'Phase 121A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(base_path(self::JSON_PATH)),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
