<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase118CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshAttemptCounterFinalizationTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-118c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-attempt-counter-finalization.json';

    public function test_finalization_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-118c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-attempt-counter-finalization.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 118C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '11bcf76215e76ded738cada70d3befadea8dd685',
            $document['baseline']['commit']
        );
        $this->assertSame(2318, $document['baseline']['tests']);
        $this->assertSame(23447, $document['baseline']['assertions']);
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

    public function test_element_state_helpers_and_legacy_contract_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-attempts',
            $locked['element']['id']
        );
        $this->assertSame(
            'manualRefreshAttempts',
            $locked['state']['counter_variable']
        );
        $this->assertSame(
            'manualRefreshRequested',
            $locked['state']['manual_request_flag']
        );
        $this->assertSame(999, $locked['state']['maximum']);
        $this->assertTrue($locked['state']['client_memory_only']);
        $this->assertFalse($locked['state']['persistent_storage_used']);

        $this->assertSame(
            'renderManualRefreshAttempts',
            $locked['helpers']['renderer']
        );
        $this->assertSame(
            'recordManualRefreshAttempt',
            $locked['helpers']['recorder']
        );
        $this->assertTrue(
            $locked['helpers']['invalid_counter_normalizes_to_zero']
        );
        $this->assertTrue(
            $locked['helpers']['counter_clamps_to_999']
        );

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

    public function test_manual_flow_and_all_counted_outcomes_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        foreach ([
            'flag_listener_registered_before_load_listener',
            'flag_set_on_manual_click',
            'flag_captured_at_request_start',
            'flag_cleared_before_concurrency_guard',
            'accepted_manual_request_counts',
            'increment_occurs_after_concurrency_guard',
            'increment_occurs_before_request_execution',
            'increment_once_per_accepted_manual_attempt',
        ] as $key) {
            $this->assertTrue($locked['manual_request_flow'][$key], $key);
        }

        $this->assertFalse(
            $locked['manual_request_flow']['ignored_concurrent_manual_request_counts']
        );
        $this->assertFalse(
            $locked['manual_request_flow']['initial_automatic_request_counts']
        );

        foreach ($locked['outcomes'] as $key => $value) {
            $this->assertTrue($value, $key);
        }
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
            $locked['implementation_scope']['phase_118b_test_added']
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
            'Phase 119A',
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
