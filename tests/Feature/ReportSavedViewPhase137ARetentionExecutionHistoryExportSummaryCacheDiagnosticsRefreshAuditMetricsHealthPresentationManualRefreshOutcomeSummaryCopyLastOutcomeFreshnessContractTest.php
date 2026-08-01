<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase137ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyLastOutcomeFreshnessContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-137a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-last-outcome-freshness-contract.json';

    public function test_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-137a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-last-outcome-freshness-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 137A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '431d17f1a14fe2561a8cd48a95a170bc4fee5e16',
            $document['baseline']['commit']
        );
        $this->assertSame(2604, $document['baseline']['tests']);
        $this->assertSame(27623, $document['baseline']['assertions']);
    }

    public function test_scope_is_documentation_and_tests_only(): void
    {
        $scope = $this->document()['scope'];

        foreach ($scope as $key => $value) {
            if ($key === 'documentation_and_tests_only') {
                $this->assertTrue($value, $key);
            } else {
                $this->assertFalse($value, $key);
            }
        }
    }

    public function test_freshness_element_sources_and_rules_are_locked(): void
    {
        $contract = $this->document()['freshness_contract'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary-copy-last-outcome-freshness',
            $contract['element']['id']
        );
        $this->assertSame('freshnessState', $contract['element']['dataset_key']);
        $this->assertSame(
            'formatManualRefreshOutcomeSummaryCopyLastOutcomeFreshness',
            $contract['state_sources']['formatter']
        );
        $this->assertSame(
            'renderManualRefreshOutcomeSummaryCopyLastOutcomeFreshness',
            $contract['state_sources']['renderer']
        );
        $this->assertSame(14, $contract['rules']['fresh_threshold_minutes_inclusive']);
        $this->assertSame('Fresh', $contract['rules']['fresh_text']);
        $this->assertSame('Stale', $contract['rules']['stale_text']);
        $this->assertSame(3, $contract['rules']['renderer_invocation_count']);
    }

    public function test_restrictions_preservation_source_order_and_legacy_are_locked(): void
    {
        $contract = $this->document()['freshness_contract'];

        foreach ([
            'timer_added',
            'polling_added',
            'timeout_added',
            'automatic_refresh_added',
            'automatic_reset_added',
        ] as $key) {
            $this->assertFalse($contract['restrictions'][$key], $key);
        }

        $this->assertTrue($contract['restrictions']['existing_render_paths_only']);

        foreach ($contract['preservation'] as $key => $value) {
            if (str_ends_with($key, '_count')) {
                $this->assertSame(
                    str_contains($key, 'renderer') ? 3 : 1,
                    $value,
                    $key
                );
            } else {
                $this->assertTrue($value, $key);
            }
        }

        foreach ($contract['source_order'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertTrue($contract['legacy']['must_remain_unchanged']);
        $this->assertTrue($contract['legacy']['promise_callbacks_preserved']);
        $this->assertSame(
            'lastManualRefreshOutcomeAt.toLocaleString();',
            $contract['legacy']['phase_123b_literal']
        );
    }

    public function test_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();

        $this->assertSame(
            2,
            $document['freshness_contract']['planned_implementation']['maximum_modified_files']
        );
        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse($document['workflow']['post_commit_full_suite']);
        $this->assertSame(
            'Phase 137B',
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
