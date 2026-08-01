<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase137CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyLastOutcomeFreshnessFinalizationTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-137c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-last-outcome-freshness-finalization.json';

    public function test_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-137c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-last-outcome-freshness-finalization.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 137C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '69e7bb7e59801eae188ed19fad15e1d99941428d',
            $document['baseline']['commit']
        );
        $this->assertSame(2615, $document['baseline']['tests']);
        $this->assertSame(27748, $document['baseline']['assertions']);
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
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary-copy-last-outcome-freshness',
            $locked['freshness_element']['id']
        );
        $this->assertSame(
            'freshnessState',
            $locked['freshness_element']['dataset_key']
        );
        $this->assertSame(
            'formatManualRefreshOutcomeSummaryCopyLastOutcomeFreshness',
            $locked['state_sources']['formatter']
        );
        $this->assertSame(
            'renderManualRefreshOutcomeSummaryCopyLastOutcomeFreshness',
            $locked['state_sources']['renderer']
        );
        $this->assertSame(
            14,
            $locked['formatting_rules']['fresh_threshold_minutes_inclusive']
        );
        $this->assertSame('Fresh', $locked['formatting_rules']['fresh_text']);
        $this->assertSame('Stale', $locked['formatting_rules']['stale_text']);
        $this->assertSame(
            3,
            $locked['formatting_rules']['renderer_invocation_count']
        );
    }

    public function test_restrictions_preservation_source_order_copy_and_legacy_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        foreach ([
            'timer_added',
            'polling_added',
            'timeout_added',
            'automatic_refresh_added',
            'automatic_reset_added',
        ] as $key) {
            $this->assertFalse($locked['refresh_behavior'][$key], $key);
        }

        $this->assertTrue($locked['refresh_behavior']['existing_render_paths_only']);

        foreach ($locked['preservation'] as $key => $value) {
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

        foreach ($locked['source_order'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertSame(
            'navigator.clipboard.writeText',
            $locked['copy_behavior']['clipboard_api']
        );
        $this->assertTrue($locked['copy_behavior']['promise_callbacks_preserved']);
        $this->assertFalse($locked['copy_behavior']['fallback_added']);
        $this->assertFalse(
            $locked['copy_behavior']['try_catch_added_before_load_health']
        );

        $this->assertTrue($locked['legacy']['must_remain_unchanged']);
        $this->assertSame(
            'lastManualRefreshOutcomeAt.toLocaleString();',
            $locked['legacy']['phase_123b_literal']
        );
    }

    public function test_implementation_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

        $this->assertTrue($locked['implementation_scope']['partial_modified']);
        $this->assertTrue($locked['implementation_scope']['phase_137b_test_added']);
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
            $this->assertFalse($locked['implementation_scope'][$key], $key);
        }

        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse($document['workflow']['post_commit_full_suite']);
        $this->assertSame(
            'Phase 138A',
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
