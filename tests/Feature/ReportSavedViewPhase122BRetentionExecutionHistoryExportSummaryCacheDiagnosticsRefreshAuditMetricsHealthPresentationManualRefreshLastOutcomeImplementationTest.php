<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase122BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshLastOutcomeImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_last_outcome_markup_state_and_helpers_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'Last manual refresh outcome:',
            'id="retention-audit-metrics-health-manual-refresh-last-outcome"',
            'data-outcome-state="unavailable"',
            'aria-live="polite"',
            "let lastManualRefreshOutcome = 'unavailable';",
            'const manualRefreshOutcomeLabels = {',
            "unavailable: 'Not available'",
            "healthy: 'Healthy'",
            "unhealthy: 'Requires attention'",
            "failed: 'Failed'",
            'const renderLastManualRefreshOutcome = () => {',
            'const setLastManualRefreshOutcome = (outcome) => {',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_validated_manual_outcome_updates_after_locked_visual_transition(): void
    {
        $source = $this->source();

        $setFields = strpos($source, 'setFields(payload);');
        $visual = strpos(
            $source,
            "payload.healthy ? 'healthy' : 'unhealthy'",
            $setFields
        );
        $manualGuard = strpos($source, 'if (isManualRefresh)', $visual);
        $setter = strpos(
            $source,
            'setLastManualRefreshOutcome(',
            $manualGuard
        );

        $this->assertNotFalse($setFields);
        $this->assertNotFalse($visual);
        $this->assertNotFalse($manualGuard);
        $this->assertNotFalse($setter);

        $this->assertGreaterThan($setFields, $visual);
        $this->assertGreaterThan($visual, $manualGuard);
        $this->assertGreaterThan($manualGuard, $setter);
    }

    public function test_failed_manual_request_updates_failed_inside_catch(): void
    {
        $source = $this->source();

        $catch = strpos($source, '} catch (error) {');
        $manualGuard = strpos($source, 'if (isManualRefresh)', $catch);
        $failureCounter = strpos(
            $source,
            'recordManualRefreshFailure();',
            $manualGuard
        );
        $failedOutcome = strpos(
            $source,
            "setLastManualRefreshOutcome('failed');",
            $failureCounter
        );
        $finally = strpos($source, '} finally {', $catch);

        $this->assertNotFalse($catch);
        $this->assertNotFalse($manualGuard);
        $this->assertNotFalse($failureCounter);
        $this->assertNotFalse($failedOutcome);
        $this->assertNotFalse($finally);

        $this->assertGreaterThan($catch, $manualGuard);
        $this->assertGreaterThan($manualGuard, $failureCounter);
        $this->assertGreaterThan($failureCounter, $failedOutcome);
        $this->assertGreaterThan($failedOutcome, $finally);
    }

    public function test_automatic_and_ignored_concurrent_requests_do_not_update_outcome(): void
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

        $this->assertSame(
            2,
            substr_count($source, 'setLastManualRefreshOutcome(')
        );
    }

    public function test_previous_metrics_and_legacy_request_contract_remain_intact(): void
    {
        $source = $this->source();

        foreach ([
            'recordManualRefreshAttempt();',
            'recordManualRefreshSuccess();',
            'recordManualRefreshFailure();',
            'renderManualRefreshSuccessRate();',
            'const loadHealth = async () => {',
            "refresh.addEventListener('click', loadHealth);",
            'loadHealth();',
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
