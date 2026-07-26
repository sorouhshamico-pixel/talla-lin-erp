<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase121CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshSuccessRateFinalizationTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-121c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-success-rate-finalization.json';

    public function test_finalization_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-121c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-success-rate-finalization.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 121C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '01cb1782b2a8dda89fd9fb46fa73fa76224c768f',
            $document['baseline']['commit']
        );
        $this->assertSame(2364, $document['baseline']['tests']);
        $this->assertSame(24095, $document['baseline']['assertions']);
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

    public function test_element_sources_calculation_and_rendering_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-success-rate',
            $locked['element']['id']
        );
        $this->assertSame(
            'manualRefreshAttempts',
            $locked['source_counters']['attempts_variable']
        );
        $this->assertSame(
            'manualRefreshSuccesses',
            $locked['source_counters']['successes_variable']
        );
        $this->assertTrue(
            $locked['source_counters']['failures_are_not_directly_used']
        );

        $this->assertSame(
            '(manualRefreshSuccesses / manualRefreshAttempts) * 100',
            $locked['calculation']['formula']
        );
        $this->assertSame(
            'Not available',
            $locked['calculation']['zero_attempts_text']
        );
        $this->assertSame(0, $locked['calculation']['minimum_percentage']);
        $this->assertSame(100, $locked['calculation']['maximum_percentage']);
        $this->assertSame(
            1,
            $locked['calculation']['maximum_fraction_digits']
        );

        $this->assertSame(
            'manualRefreshRateFormatter',
            $locked['rendering']['formatter']
        );
        $this->assertSame(
            'renderManualRefreshSuccessRate',
            $locked['rendering']['renderer']
        );
        $this->assertTrue($locked['rendering']['after_attempt_increment']);
        $this->assertTrue($locked['rendering']['after_success_increment']);
        $this->assertTrue($locked['rendering']['after_failure_increment']);
        $this->assertSame(3, $locked['rendering']['render_call_count']);
    }

    public function test_legacy_contract_compatibility_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

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

        foreach ($locked['compatibility'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        $this->assertTrue(
            $locked['implementation_scope']['partial_modified']
        );
        $this->assertTrue(
            $locked['implementation_scope']['phase_121b_test_added']
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
            'Phase 122A',
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
