<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase118BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshAttemptCounterImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_manual_attempt_counter_markup_state_and_helpers_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'Manual refresh attempts:',
            'id="retention-audit-metrics-health-manual-refresh-attempts"',
            'let manualRefreshAttempts = 0;',
            'let manualRefreshRequested = false;',
            'const renderManualRefreshAttempts = () => {',
            'Math.min(manualRefreshAttempts, 999)',
            'manualRefreshAttemptCounter.textContent = String(safeValue);',
            'const recordManualRefreshAttempt = () => {',
            'manualRefreshAttempts + 1',
            'renderManualRefreshAttempts();',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_legacy_request_signature_listener_and_initial_load_are_preserved(): void
    {
        $source = $this->source();

        foreach ([
            'const loadHealth = async () => {',
            "refresh.addEventListener('click', loadHealth);",
            'loadHealth();',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        foreach ([
            'const loadHealth = async (isManualRefresh = false) => {',
            'loadHealth(true);',
            'loadHealth(false);',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $source);
        }
    }

    public function test_manual_click_flag_is_registered_before_the_locked_load_listener(): void
    {
        $source = $this->source();

        $flagListener = strpos(
            $source,
            "refresh.addEventListener('click', () => {"
        );
        $flagSet = strpos($source, 'manualRefreshRequested = true;');
        $loadListener = strpos(
            $source,
            "refresh.addEventListener('click', loadHealth);"
        );
        $initialLoad = strpos($source, 'loadHealth();');

        $this->assertNotFalse($flagListener);
        $this->assertNotFalse($flagSet);
        $this->assertNotFalse($loadListener);
        $this->assertNotFalse($initialLoad);

        $this->assertGreaterThan($flagListener, $flagSet);
        $this->assertGreaterThan($flagSet, $loadListener);
        $this->assertGreaterThan($loadListener, $initialLoad);
    }

    public function test_manual_flag_is_consumed_before_concurrency_guard_and_counts_only_accepted_attempts(): void
    {
        $source = $this->source();

        $capture = strpos(
            $source,
            'const isManualRefresh = manualRefreshRequested;'
        );
        $clear = strpos(
            $source,
            'manualRefreshRequested = false;',
            $capture + 1
        );
        $guard = strpos($source, 'if (requestInFlight)', $clear + 1);
        $manualGuard = strpos($source, 'if (isManualRefresh)');
        $increment = strpos($source, 'recordManualRefreshAttempt();');
        $requestStart = strpos($source, 'requestInFlight = true;');

        $this->assertNotFalse($capture);
        $this->assertNotFalse($clear);
        $this->assertNotFalse($guard);
        $this->assertNotFalse($manualGuard);
        $this->assertNotFalse($increment);
        $this->assertNotFalse($requestStart);

        $this->assertGreaterThan($capture, $clear);
        $this->assertGreaterThan($clear, $guard);
        $this->assertGreaterThan($guard, $manualGuard);
        $this->assertGreaterThan($manualGuard, $increment);
        $this->assertGreaterThan($increment, $requestStart);

        $this->assertSame(
            1,
            substr_count($source, 'recordManualRefreshAttempt();')
        );
    }

    public function test_all_accepted_manual_outcomes_share_one_pre_request_increment(): void
    {
        $source = $this->source();

        $increment = strpos($source, 'recordManualRefreshAttempt();');
        $try = strpos($source, 'try {');
        $catch = strpos($source, '} catch (error) {');
        $finally = strpos($source, '} finally {');

        $this->assertNotFalse($increment);
        $this->assertNotFalse($try);
        $this->assertNotFalse($catch);
        $this->assertNotFalse($finally);

        $this->assertGreaterThan($increment, $try);
        $this->assertGreaterThan($increment, $catch);
        $this->assertGreaterThan($increment, $finally);
    }

    public function test_existing_health_contracts_and_no_persistence_remain_intact(): void
    {
        $source = $this->source();

        foreach ([
            'retention-audit-metrics-health-successful-check-freshness',
            'updateSuccessfulCheckFreshness(completedAt);',
            'retention-audit-metrics-health-successful-check-age',
            'updateSuccessfulCheckAge(completedAt);',
            'retention-audit-metrics-health-last-successful-check',
            'updateLastSuccessfulCheck();',
            'retention-audit-metrics-health-consecutive-failures',
            'recordSuccessfulRequest();',
            'recordFailedRequest();',
            'retention-audit-metrics-health-response-status',
            'retention-audit-metrics-health-request-duration',
            "performance['now']()",
            'updateTimestamp();',
            'if (!isValidPayload(payload))',
            "method: 'GET'",
            "credentials: 'same-origin'",
            "Accept: 'application/json'",
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        foreach ([
            'localStorage',
            'sessionStorage',
            'indexedDB',
            'document.cookie',
            'setInterval(',
            'setTimeout(',
            'requestAnimationFrame(',
            'location.reload(',
            'DB::',
            'Cache::',
            'Log::',
            'Event::',
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
