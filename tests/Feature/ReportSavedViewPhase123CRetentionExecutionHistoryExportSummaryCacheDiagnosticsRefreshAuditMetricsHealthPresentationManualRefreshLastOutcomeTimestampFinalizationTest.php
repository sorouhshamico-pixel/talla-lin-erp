<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase123CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshLastOutcomeTimestampFinalizationTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-123c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-last-outcome-timestamp-finalization.json';

    public function test_finalization_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-123c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-last-outcome-timestamp-finalization.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 123C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            'eec81077ead4bc636a952a95e498eed5cb7ae880',
            $document['baseline']['commit']
        );
        $this->assertSame(2394, $document['baseline']['tests']);
        $this->assertSame(24511, $document['baseline']['assertions']);
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

    public function test_element_state_formatting_and_updates_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-last-outcome-at',
            $locked['element']['id']
        );
        $this->assertSame('time', $locked['element']['element']);
        $this->assertFalse(
            $locked['element']['datetime_initially_present']
        );

        $this->assertSame(
            'lastManualRefreshOutcomeAt',
            $locked['state_management']['state_variable']
        );
        $this->assertNull(
            $locked['state_management']['initial_value']
        );
        $this->assertSame(
            'renderLastManualRefreshOutcomeTimestamp',
            $locked['state_management']['renderer']
        );
        $this->assertSame(
            'setLastManualRefreshOutcomeTimestamp',
            $locked['state_management']['setter']
        );
        $this->assertSame(
            'manualRefreshOutcomeTimestampFormatter',
            $locked['state_management']['formatter']
        );

        $this->assertSame(
            'client clock',
            $locked['formatting']['source']
        );
        $this->assertSame('medium', $locked['formatting']['date_style']);
        $this->assertSame('medium', $locked['formatting']['time_style']);
        $this->assertSame(
            'Date.toLocaleString()',
            $locked['formatting']['fallback']
        );
        $this->assertSame(
            'ISO 8601',
            $locked['formatting']['valid_datetime_attribute']
        );

        foreach ($locked['update_rules'] as $key => $value) {
            if ($key === 'timestamp_setter_call_count') {
                $this->assertSame(1, $value, $key);
                continue;
            }

            $this->assertTrue($value, $key);
        }
    }

    public function test_legacy_contract_compatibility_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

        $this->assertTrue($locked['legacy_contract']['preserved']);
        $this->assertTrue(
            $locked['legacy_contract']['phase_110b_visual_order_preserved']
        );
        $this->assertTrue(
            $locked['legacy_contract']['phase_111b_refresh_timestamp_preserved']
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

        foreach ($locked['compatibility'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        $this->assertTrue(
            $locked['implementation_scope']['partial_modified']
        );
        $this->assertTrue(
            $locked['implementation_scope']['phase_123b_test_added']
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
            'Phase 124A',
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
