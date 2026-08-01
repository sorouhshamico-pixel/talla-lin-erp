<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase138ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyLastOutcomeFreshnessReasonContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-138a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-last-outcome-freshness-reason-contract.json';

    public function test_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-138a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-last-outcome-freshness-reason-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 138A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            'f1805e0b0f725363e03a2830e5697fb5360983ec',
            $document['baseline']['commit']
        );
        $this->assertSame(2620, $document['baseline']['tests']);
        $this->assertSame(27825, $document['baseline']['assertions']);
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

    public function test_reason_element_sources_and_rules_are_locked(): void
    {
        $contract = $this->document()['freshness_reason_contract'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary-copy-last-outcome-freshness-reason',
            $contract['element']['id']
        );
        $this->assertSame(
            'formatManualRefreshOutcomeSummaryCopyLastOutcomeFreshnessReason',
            $contract['state_sources']['reason_formatter']
        );
        $this->assertSame(
            'renderManualRefreshOutcomeSummaryCopyLastOutcomeFreshnessReason',
            $contract['state_sources']['reason_renderer']
        );
        $this->assertSame(
            'No completed copy outcome yet.',
            $contract['rules']['unavailable_reason']
        );
        $this->assertSame(
            'The latest copy outcome is within the 14-minute freshness window.',
            $contract['rules']['fresh_reason']
        );
        $this->assertSame(
            'The latest copy outcome is older than the 14-minute freshness window.',
            $contract['rules']['stale_reason']
        );
        $this->assertSame(3, $contract['rules']['renderer_invocation_count']);
    }

    public function test_restrictions_preservation_source_order_and_legacy_are_locked(): void
    {
        $contract = $this->document()['freshness_reason_contract'];

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

    public function test_workflow_scope_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['freshness_reason_contract'];

        $this->assertSame(
            2,
            $contract['planned_implementation']['maximum_modified_files']
        );
        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse($document['workflow']['post_commit_full_suite']);
        $this->assertSame(
            'Phase 138B',
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
