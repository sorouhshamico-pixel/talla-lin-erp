<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase119BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshSuccessCounterImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_manual_success_counter_markup_state_and_helpers_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'Manual refresh successes:',
            'id="retention-audit-metrics-health-manual-refresh-successes"',
            'let manualRefreshSuccesses = 0;',
            'const renderManualRefreshSuccesses = () => {',
            'Number.isInteger(manualRefreshSuccesses)',
            'Math.min(manualRefreshSuccesses, 999)',
            'manualRefreshSuccessCounter.textContent = String(safeValue);',
            'const recordManualRefreshSuccess = () => {',
            'manualRefreshSuccesses + 1',
            'renderManualRefreshSuccesses();',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_success_increment_occurs_after_payload_validation_and_before_request_success_assignment(): void
    {
        $source = $this->source();

        $validation = strpos($source, 'if (!isValidPayload(payload))');
        $manualGuard = strpos(
            $source,
            'if (isManualRefresh)',
            $validation
        );
        $increment = strpos(
            $source,
            'recordManualRefreshSuccess();'
        );
        $fields = strpos($source, 'setFields(payload);');
        $requestSucceeded = strpos(
            $source,
            'requestSucceeded = true;'
        );

        $this->assertNotFalse($validation);
        $this->assertNotFalse($manualGuard);
        $this->assertNotFalse($increment);
        $this->assertNotFalse($fields);
        $this->assertNotFalse($requestSucceeded);

        $this->assertGreaterThan($validation, $manualGuard);
        $this->assertGreaterThan($manualGuard, $increment);
        $this->assertGreaterThan($increment, $fields);
        $this->assertGreaterThan($fields, $requestSucceeded);

        $this->assertSame(
            1,
            substr_count($source, 'recordManualRefreshSuccess();')
        );
    }

    public function test_validated_healthy_and_unhealthy_share_the_same_manual_success_path(): void
    {
        $source = $this->source();

        $increment = strpos(
            $source,
            'recordManualRefreshSuccess();'
        );
        $statusAssignment = strpos(
            $source,
            'status.textContent = payload.healthy'
        );
        $healthyBranch = strpos(
            $source,
            'if (payload.healthy)'
        );

        $this->assertNotFalse($increment);
        $this->assertNotFalse($statusAssignment);
        $this->assertNotFalse($healthyBranch);

        $this->assertGreaterThan($increment, $statusAssignment);
        $this->assertGreaterThan($increment, $healthyBranch);
    }

    public function test_failure_paths_do_not_increment_manual_success_counter(): void
    {
        $source = $this->source();

        $increment = strpos(
            $source,
            'recordManualRefreshSuccess();'
        );
        $catch = strpos($source, '} catch (error) {');
        $finally = strpos($source, '} finally {');

        $this->assertNotFalse($increment);
        $this->assertNotFalse($catch);
        $this->assertNotFalse($finally);

        $this->assertGreaterThan($increment, $catch);
        $this->assertGreaterThan($increment, $finally);

        $catchSection = substr(
            $source,
            $catch,
            $finally - $catch
        );

        $this->assertStringNotContainsString(
            'recordManualRefreshSuccess();',
            $catchSection
        );
    }

    public function test_initial_automatic_request_and_ignored_concurrent_request_do_not_count(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            'const isManualRefresh = manualRefreshRequested;',
            $source
        );
        $this->assertStringContainsString(
            'manualRefreshRequested = false;',
            $source
        );
        $this->assertStringContainsString(
            'if (requestInFlight)',
            $source
        );
        $this->assertStringContainsString('loadHealth();', $source);

        $this->assertStringNotContainsString(
            "loadHealth();\n            recordManualRefreshSuccess();",
            $source
        );
    }

    public function test_legacy_request_and_phase_118_contracts_remain_intact(): void
    {
        $source = $this->source();

        foreach ([
            'const loadHealth = async () => {',
            "refresh.addEventListener('click', loadHealth);",
            'loadHealth();',
            'retention-audit-metrics-health-manual-refresh-attempts',
            'recordManualRefreshAttempt();',
            'retention-audit-metrics-health-successful-check-freshness',
            'updateSuccessfulCheckFreshness(completedAt);',
            'retention-audit-metrics-health-successful-check-age',
            'updateSuccessfulCheckAge(completedAt);',
            'retention-audit-metrics-health-last-successful-check',
            'updateLastSuccessfulCheck();',
            'retention-audit-metrics-health-consecutive-failures',
            'recordSuccessfulRequest();',
            'recordFailedRequest();',
            "method: 'GET'",
            "credentials: 'same-origin'",
            "Accept: 'application/json'",
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        foreach ([
            'const loadHealth = async (isManualRefresh = false) => {',
            'loadHealth(true);',
            'loadHealth(false);',
            'localStorage',
            'sessionStorage',
            'indexedDB',
            'setInterval(',
            'setTimeout(',
            'requestAnimationFrame(',
            'location.reload(',
            'DB::',
            'Cache::',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $source);
        }
    }

    public function test_partial_still_compiles(): void
    {
        $compiled = Blade::compileString($this->source());

        $this->assertIsString($compiled);
        $this->assertNotSame('', trim($compiled));
    }

    private function source(): string
    {
        $source = file_get_contents(base_path(self::PARTIAL));

        $this->assertIsString($source);

        return $source;
    }
}
