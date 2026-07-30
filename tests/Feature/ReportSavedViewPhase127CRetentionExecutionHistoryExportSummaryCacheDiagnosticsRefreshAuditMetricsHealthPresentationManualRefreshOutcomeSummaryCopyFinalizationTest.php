<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase127CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyFinalizationTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-127c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-finalization.json';

    public function test_finalization_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-127c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-outcome-summary-copy-finalization.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 127C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            'cadea67f9dcd049da3440e1d8f957f305964cb8c',
            $document['baseline']['commit']
        );
        $this->assertSame(2452, $document['baseline']['tests']);
        $this->assertSame(25365, $document['baseline']['assertions']);
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

    public function test_copy_control_clipboard_and_availability_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-outcome-summary-copy',
            $locked['button']['id']
        );
        $this->assertTrue($locked['button']['disabled_initially']);
        $this->assertSame(
            'navigator.clipboard.writeText',
            $locked['clipboard']['api']
        );
        $this->assertTrue(
            $locked['clipboard']['secure_context_required']
        );
        $this->assertTrue(
            $locked['clipboard']['explicit_click_required']
        );
        $this->assertTrue(
            $locked['clipboard']['promise_success_failure_callbacks']
        );
        $this->assertFalse(
            $locked['clipboard']['try_catch_added_before_load_health']
        );
        $this->assertFalse($locked['clipboard']['fallback_added']);
        $this->assertFalse($locked['clipboard']['exec_command_used']);
        $this->assertFalse(
            $locked['clipboard']['textarea_fallback_used']
        );

        $this->assertSame(
            'renderManualRefreshOutcomeSummaryCopyAvailability',
            $locked['availability']['renderer']
        );
        $this->assertTrue(
            $locked['availability']['disabled_for_unavailable']
        );
        $this->assertTrue(
            $locked['availability']['enabled_for_healthy']
        );
        $this->assertTrue(
            $locked['availability']['enabled_for_unhealthy']
        );
        $this->assertTrue(
            $locked['availability']['enabled_for_failed']
        );
    }

    public function test_interaction_literals_and_legacy_contract_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'copyManualRefreshOutcomeSummary',
            $locked['interaction']['handler']
        );
        $this->assertSame(
            'setManualRefreshOutcomeSummaryCopyStatus',
            $locked['interaction']['status_setter']
        );
        $this->assertSame(
            "setManualRefreshOutcomeSummaryCopyStatus('Copied');",
            $locked['interaction']['copied_literal']
        );

        foreach ([
            'copy_on_initial_load',
            'copy_on_manual_refresh_completion',
            'copy_on_automatic_request',
            'timer_added',
            'polling_added',
            'persistent_storage_used',
        ] as $key) {
            $this->assertFalse($locked['interaction'][$key], $key);
        }

        $this->assertTrue($locked['legacy_contract']['preserved']);
        $this->assertTrue(
            $locked['legacy_contract']['phase_114b_through_120b_try_catch_ordering_preserved']
        );
        $this->assertSame(
            'lastManualRefreshOutcomeAt.toLocaleString();',
            $locked['legacy_contract']['phase_123b_literal_fallback_preserved']
        );
        $this->assertTrue(
            $locked['legacy_contract']['phase_126b_summary_format_preserved']
        );
        $this->assertTrue(
            $locked['legacy_contract']['phase_126b_summary_renderer_preserved']
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
            $locked['implementation_scope']['phase_127b_test_added']
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
            'Phase 128A',
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
