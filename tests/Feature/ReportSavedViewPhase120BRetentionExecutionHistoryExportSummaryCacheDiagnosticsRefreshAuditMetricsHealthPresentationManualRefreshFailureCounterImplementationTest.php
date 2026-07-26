<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase120BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshFailureCounterImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_failure_counter_markup_state_and_helpers_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'Manual refresh failures:',
            'id="retention-audit-metrics-health-manual-refresh-failures"',
            'let manualRefreshFailures = 0;',
            'const renderManualRefreshFailures = () => {',
            'Number.isInteger(manualRefreshFailures)',
            'Math.min(manualRefreshFailures, 999)',
            'manualRefreshFailureCounter.textContent = String(safeValue);',
            'const recordManualRefreshFailure = () => {',
            'manualRefreshFailures + 1',
            'renderManualRefreshFailures();',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_failure_increment_occurs_once_inside_catch_for_manual_requests(): void
    {
        $source = $this->source();

        $catch = strpos($source, '} catch (error) {');
        $manualGuard = strpos($source, 'if (isManualRefresh)', $catch);
        $increment = strpos($source, 'recordManualRefreshFailure();');
        $network = strpos(
            $source,
            'if (!responseReceived)',
            $catch
        );
        $finally = strpos($source, '} finally {');

        $this->assertNotFalse($catch);
        $this->assertNotFalse($manualGuard);
        $this->assertNotFalse($increment);
        $this->assertNotFalse($network);
        $this->assertNotFalse($finally);

        $this->assertGreaterThan($catch, $manualGuard);
        $this->assertGreaterThan($manualGuard, $increment);
        $this->assertGreaterThan($increment, $network);
        $this->assertGreaterThan($network, $finally);

        $this->assertSame(
            1,
            substr_count($source, 'recordManualRefreshFailure();')
        );
    }

    public function test_validated_healthy_and_unhealthy_do_not_count_as_failures(): void
    {
        $source = $this->source();

        $validation = strpos($source, 'if (!isValidPayload(payload))');
        $successIncrement = strpos(
            $source,
            'recordManualRefreshSuccess();'
        );
        $failureIncrement = strpos(
            $source,
            'recordManualRefreshFailure();'
        );
        $catch = strpos($source, '} catch (error) {');

        $this->assertNotFalse($validation);
        $this->assertNotFalse($successIncrement);
        $this->assertNotFalse($failureIncrement);
        $this->assertNotFalse($catch);

        $this->assertGreaterThan($validation, $successIncrement);
        $this->assertGreaterThan($successIncrement, $catch);
        $this->assertGreaterThan($catch, $failureIncrement);
    }

    public function test_initial_and_ignored_concurrent_requests_do_not_count(): void
    {
        $source = $this->source();

        foreach ([
            'const isManualRefresh = manualRefreshRequested;',
            'manualRefreshRequested = false;',
            'if (requestInFlight)',
            'loadHealth();',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        $this->assertStringNotContainsString(
            "loadHealth();\n            recordManualRefreshFailure();",
            $source
        );
    }

    public function test_attempt_success_and_legacy_request_contracts_remain_intact(): void
    {
        $source = $this->source();

        foreach ([
            'retention-audit-metrics-health-manual-refresh-attempts',
            'recordManualRefreshAttempt();',
            'retention-audit-metrics-health-manual-refresh-successes',
            'recordManualRefreshSuccess();',
            'const loadHealth = async () => {',
            "refresh.addEventListener('click', loadHealth);",
            'loadHealth();',
            'retention-audit-metrics-health-successful-check-freshness',
            'updateSuccessfulCheckFreshness(completedAt);',
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
