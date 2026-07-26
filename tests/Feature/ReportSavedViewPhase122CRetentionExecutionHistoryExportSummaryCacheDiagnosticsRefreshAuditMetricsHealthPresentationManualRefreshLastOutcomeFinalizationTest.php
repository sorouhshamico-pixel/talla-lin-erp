<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase122CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshLastOutcomeFinalizationTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-122c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-last-outcome-finalization.json';

    public function test_finalization_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-122c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-last-outcome-finalization.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 122C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            'c01cad8b9c0eec4aa306a92fd01ede08bcdec88f',
            $document['baseline']['commit']
        );
        $this->assertSame(2379, $document['baseline']['tests']);
        $this->assertSame(24306, $document['baseline']['assertions']);
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

    public function test_element_states_and_state_management_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-last-outcome',
            $locked['element']['id']
        );
        $this->assertSame(
            [
                'unavailable' => 'Not available',
                'healthy' => 'Healthy',
                'unhealthy' => 'Requires attention',
                'failed' => 'Failed',
            ],
            $locked['states']
        );
        $this->assertSame(
            'lastManualRefreshOutcome',
            $locked['state_management']['state_variable']
        );
        $this->assertSame(
            'manualRefreshOutcomeLabels',
            $locked['state_management']['labels_object']
        );
        $this->assertSame(
            'renderLastManualRefreshOutcome',
            $locked['state_management']['renderer']
        );
        $this->assertSame(
            'setLastManualRefreshOutcome',
            $locked['state_management']['setter']
        );
        $this->assertSame(
            'unavailable',
            $locked['state_management']['invalid_state_normalizes_to']
        );
        $this->assertTrue(
            $locked['state_management']['client_memory_only']
        );
        $this->assertFalse(
            $locked['state_management']['persistent_storage_used']
        );
    }

    public function test_update_rules_and_legacy_contract_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        foreach ($locked['update_rules'] as $key => $value) {
            if ($key === 'runtime_setter_call_count') {
                $this->assertSame(2, $value, $key);

                continue;
            }

            $this->assertTrue($value, $key);
        }

        $this->assertTrue($locked['legacy_contract']['preserved']);
        $this->assertTrue(
            $locked['legacy_contract']['phase_110b_visual_order_preserved']
        );
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
            $locked['implementation_scope']['phase_122b_test_added']
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
            'Phase 123A',
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
