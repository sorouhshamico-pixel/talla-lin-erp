<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase136CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyLastOutcomeAgeFinalizationTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-136c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-last-outcome-age-finalization.json';

    public function test_finalization_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-136c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-last-outcome-age-finalization.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 136C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '2fc7d38a8b2b993e98f795313af85b94a8330119',
            $document['baseline']['commit']
        );
        $this->assertSame(2599, $document['baseline']['tests']);
        $this->assertSame(27522, $document['baseline']['assertions']);
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

    public function test_age_element_sources_formatting_and_update_paths_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary-copy-last-outcome-age',
            $locked['age_element']['id']
        );
        $this->assertSame(
            'manualRefreshOutcomeSummaryCopyLastOutcomeAge',
            $locked['state_sources']['display_element']
        );
        $this->assertSame(
            'formatManualRefreshOutcomeSummaryCopyLastOutcomeAge',
            $locked['state_sources']['formatter']
        );
        $this->assertSame(
            'renderManualRefreshOutcomeSummaryCopyLastOutcomeAge',
            $locked['state_sources']['renderer']
        );
        $this->assertSame(
            'Less than 1 minute',
            $locked['formatting_rules']['less_than_one_minute']
        );
        $this->assertSame(
            999,
            $locked['formatting_rules']['maximum_display_value']
        );
        $this->assertSame(
            3,
            $locked['formatting_rules']['renderer_invocation_count']
        );
        $this->assertSame(
            'new Date()',
            $locked['formatting_rules']['initialization_render_argument']
        );
    }

    public function test_refresh_timestamp_metrics_source_order_copy_and_legacy_rules_are_locked(): void
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

        $this->assertTrue(
            $locked['refresh_behavior']['age_updates_only_on_existing_render_paths']
        );

        foreach ($locked['timestamp_preservation'] as $key => $value) {
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

        foreach ($locked['metrics_preservation'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($locked['source_order_lock'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertSame(
            'navigator.clipboard.writeText',
            $locked['copy_behavior']['clipboard_api']
        );
        $this->assertTrue(
            $locked['copy_behavior']['promise_callbacks_preserved']
        );
        $this->assertFalse(
            $locked['copy_behavior']['fallback_added']
        );
        $this->assertFalse(
            $locked['copy_behavior']['try_catch_added_before_load_health']
        );

        $this->assertTrue($locked['legacy_contract']['preserved']);
        $this->assertSame(
            'lastManualRefreshOutcomeAt.toLocaleString();',
            $locked['legacy_contract']['phase_123b_literal_fallback_preserved']
        );
        $this->assertTrue(
            $locked['legacy_contract']['phase_135b_last_outcome_timestamp_preserved']
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
            $locked['implementation_scope']['phase_136b_test_added']
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
            'Phase 137A',
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
