<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase126CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryFinalizationTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-126c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-finalization.json';

    public function test_finalization_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-126c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-finalization.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 126C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '293cb72860a66d06916c020146aee17728967278',
            $document['baseline']['commit']
        );
        $this->assertSame(2436, $document['baseline']['tests']);
        $this->assertSame(25144, $document['baseline']['assertions']);
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

    public function test_summary_element_states_formatters_and_format_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary',
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
            [
                'lastManualRefreshOutcome',
                'lastManualRefreshOutcomeAt',
            ],
            $locked['state_management']['sources']
        );
        $this->assertSame(
            'formatLastManualRefreshOutcomeTimestamp',
            $locked['state_management']['timestamp_formatter']
        );
        $this->assertSame(
            'formatLastManualRefreshOutcomeAge',
            $locked['state_management']['age_formatter']
        );
        $this->assertSame(
            'formatLastManualRefreshOutcomeFreshness',
            $locked['state_management']['freshness_formatter']
        );
        $this->assertSame(
            'formatManualRefreshOutcomeSummary',
            $locked['state_management']['summary_formatter']
        );
        $this->assertSame(
            'renderManualRefreshOutcomeSummary',
            $locked['state_management']['summary_renderer']
        );
        $this->assertSame(
            ' · ',
            $locked['summary_format']['separator']
        );
        $this->assertFalse(
            $locked['summary_format']['duplicates_business_logic']
        );
    }

    public function test_update_rules_and_legacy_literal_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        foreach ($locked['update_rules'] as $key => $value) {
            if ($key === 'renderer_call_count') {
                $this->assertSame(1, $value, $key);
                continue;
            }

            if (in_array($key, [
                'timer_added',
                'polling_added',
                'periodic_recalculation_added',
            ], true)) {
                $this->assertFalse($value, $key);
                continue;
            }

            $this->assertTrue($value, $key);
        }

        $this->assertSame(
            'lastManualRefreshOutcomeAt.toLocaleString();',
            $locked['legacy_contract']['phase_123b_literal_fallback_preserved']
        );
        $this->assertTrue($locked['legacy_contract']['preserved']);
        $this->assertTrue(
            $locked['legacy_contract']['phase_123b_last_outcome_timestamp_preserved']
        );
        $this->assertTrue(
            $locked['legacy_contract']['phase_124b_last_outcome_age_preserved']
        );
        $this->assertTrue(
            $locked['legacy_contract']['phase_125b_last_outcome_freshness_preserved']
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
            $locked['implementation_scope']['phase_126b_test_added']
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
            'Phase 127A',
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
