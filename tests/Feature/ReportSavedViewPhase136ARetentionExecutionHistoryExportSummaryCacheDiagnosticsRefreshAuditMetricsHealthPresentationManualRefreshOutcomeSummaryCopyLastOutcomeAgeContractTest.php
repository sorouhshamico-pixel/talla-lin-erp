<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase136ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyLastOutcomeAgeContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-136a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-last-outcome-age-contract.json';

    public function test_contract_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-136a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-last-outcome-age-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 136A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '5e285e3ff4fe945b82858546051494b7d02a31c2',
            $document['baseline']['commit']
        );
        $this->assertSame(2588, $document['baseline']['tests']);
        $this->assertSame(27375, $document['baseline']['assertions']);
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

    public function test_age_element_sources_and_formatting_rules_are_locked(): void
    {
        $contract = $this->document()['copy_last_outcome_age_contract'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary-copy-last-outcome-age',
            $contract['age_element']['id']
        );
        $this->assertSame(
            'Last copy outcome age:',
            $contract['age_element']['prefix']
        );
        $this->assertSame(
            'lastManualRefreshOutcomeSummaryCopyOutcomeAt',
            $contract['state_sources']['timestamp_variable']
        );
        $this->assertSame(
            'formatManualRefreshOutcomeSummaryCopyLastOutcomeAge',
            $contract['state_sources']['formatter']
        );
        $this->assertSame(
            'renderManualRefreshOutcomeSummaryCopyLastOutcomeAge',
            $contract['state_sources']['renderer']
        );
        $this->assertSame(
            'Less than 1 minute',
            $contract['formatting_rules']['less_than_one_minute']
        );
        $this->assertSame(
            999,
            $contract['formatting_rules']['maximum_display_value']
        );
        $this->assertSame(
            3,
            $contract['formatting_rules']['renderer_invocation_count']
        );
    }

    public function test_refresh_timestamp_metrics_copy_source_order_and_legacy_rules_are_locked(): void
    {
        $contract = $this->document()['copy_last_outcome_age_contract'];

        foreach ([
            'timer_added',
            'polling_added',
            'timeout_added',
            'automatic_refresh_added',
            'automatic_reset_added',
        ] as $key) {
            $this->assertFalse($contract['refresh_behavior'][$key], $key);
        }

        $this->assertTrue(
            $contract['refresh_behavior']['age_updates_only_on_existing_render_paths']
        );

        foreach ($contract['timestamp_preservation'] as $key => $value) {
            if (str_ends_with($key, '_count_preserved')) {
                $this->assertSame(
                    $key === 'timestamp_renderer_invocation_count_preserved'
                        ? 3
                        : 1,
                    $value,
                    $key
                );
                continue;
            }

            $this->assertTrue($value, $key);
        }

        foreach ($contract['metrics_preservation'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertTrue(
            $contract['copy_behavior']['promise_callbacks_preserved']
        );
        $this->assertFalse(
            $contract['copy_behavior']['fallback_added']
        );

        foreach ($contract['source_order_lock'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertTrue(
            $contract['legacy_contract']['must_remain_unchanged']
        );
        $this->assertSame(
            'lastManualRefreshOutcomeAt.toLocaleString();',
            $contract['legacy_contract']['phase_123b_literal_fallback_preserved']
        );
        $this->assertTrue(
            $contract['legacy_contract']['phase_135b_last_outcome_timestamp_preserved']
        );
    }

    public function test_compatibility_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['copy_last_outcome_age_contract'];

        foreach ($contract['compatibility'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        $this->assertSame(
            2,
            $contract['planned_implementation']['maximum_modified_files']
        );
        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse(
            $document['workflow']['post_commit_full_suite']
        );
        $this->assertSame(
            'Phase 136B',
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
