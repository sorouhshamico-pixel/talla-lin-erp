<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase121ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshSuccessRateContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-121a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-success-rate-contract.json';

    public function test_contract_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-121a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-success-rate-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 121A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '60384c9ebc1a827a353e1ccf3e764a85a6bec1fd',
            $document['baseline']['commit']
        );
        $this->assertSame(2355, $document['baseline']['tests']);
        $this->assertSame(23981, $document['baseline']['assertions']);
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

    public function test_element_sources_and_calculation_are_locked(): void
    {
        $contract = $this->document()['manual_refresh_success_rate_contract'];

        $this->assertSame(
            'retention-audit-metrics-health-manual-refresh-success-rate',
            $contract['element']['id']
        );
        $this->assertSame(
            'Manual refresh success rate:',
            $contract['element']['prefix']
        );
        $this->assertSame(
            'Not available',
            $contract['element']['initial_text']
        );

        $this->assertSame(
            'manualRefreshAttempts',
            $contract['source_counters']['attempts_variable']
        );
        $this->assertSame(
            'manualRefreshSuccesses',
            $contract['source_counters']['successes_variable']
        );
        $this->assertTrue(
            $contract['source_counters']['attempts_are_denominator']
        );
        $this->assertTrue(
            $contract['source_counters']['successes_are_numerator']
        );
        $this->assertTrue(
            $contract['source_counters']['failures_are_not_directly_used']
        );

        $this->assertSame(
            '(manualRefreshSuccesses / manualRefreshAttempts) * 100',
            $contract['calculation']['formula']
        );
        $this->assertSame(
            'Not available',
            $contract['calculation']['zero_attempts_text']
        );
        $this->assertSame(0, $contract['calculation']['minimum_percentage']);
        $this->assertSame(100, $contract['calculation']['maximum_percentage']);
        $this->assertSame(
            1,
            $contract['calculation']['maximum_fraction_digits']
        );
        $this->assertSame('%', $contract['calculation']['suffix']);
    }

    public function test_update_rules_examples_and_legacy_contract_are_locked(): void
    {
        $contract = $this->document()['manual_refresh_success_rate_contract'];

        foreach ([
            'render_after_manual_attempt_increment',
            'render_after_manual_success_increment',
            'render_after_manual_failure_increment',
            'initial_automatic_request_does_not_change_rate',
            'ignored_concurrent_manual_request_does_not_change_rate',
            'page_memory_only',
        ] as $key) {
            $this->assertTrue($contract['update_rules'][$key], $key);
        }

        $this->assertFalse(
            $contract['update_rules']['persistent_storage_used']
        );

        $this->assertSame(
            [
                ['attempts' => 0, 'successes' => 0, 'text' => 'Not available'],
                ['attempts' => 1, 'successes' => 1, 'text' => '100%'],
                ['attempts' => 1, 'successes' => 0, 'text' => '0%'],
                ['attempts' => 3, 'successes' => 2, 'text' => '66.7%'],
            ],
            $contract['examples']
        );

        $this->assertTrue(
            $contract['legacy_contract']['must_remain_unchanged']
        );
        $this->assertSame(
            'const loadHealth = async () => {',
            $contract['legacy_contract']['load_health_signature']
        );
        $this->assertSame(
            "refresh.addEventListener('click', loadHealth);",
            $contract['legacy_contract']['refresh_listener']
        );
        $this->assertSame(
            'loadHealth();',
            $contract['legacy_contract']['initial_load']
        );
    }

    public function test_compatibility_planned_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['manual_refresh_success_rate_contract'];

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
            'Phase 121B',
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
