<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase133BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopySuccessRateImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_copy_success_rate_markup_and_lookup_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-success-rate"',
            'Copy success rate: <span>Not available</span>',
            'const manualRefreshOutcomeSummaryCopySuccessRate =',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_success_rate_formula_and_format_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'const renderManualRefreshOutcomeSummaryCopySuccessRate = () => {',
            'const completedWrites =',
            'manualRefreshOutcomeSummaryCopySuccessCount',
            'manualRefreshOutcomeSummaryCopyFailureCount',
            'if (completedWrites === 0) {',
            "'Not available'",
            'Number.isFinite(percentage)',
            'percentage.toFixed(1)',
            'Math.max(',
            'Math.min(',
            '100',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        $this->assertStringContainsString(
            'manualRefreshOutcomeSummaryCopySuccessCount'
            . "\n                + manualRefreshOutcomeSummaryCopyFailureCount",
            $source
        );
    }

    public function test_renderer_runs_after_success_failure_and_initialization(): void
    {
        $source = $this->source();

        $this->assertSame(
            3,
            substr_count(
                $source,
                'renderManualRefreshOutcomeSummaryCopySuccessRate();'
            )
        );

        $successRecorder = strpos(
            $source,
            'const recordManualRefreshOutcomeSummaryCopySuccess = () => {'
        );
        $failureRecorder = strpos(
            $source,
            'const recordManualRefreshOutcomeSummaryCopyFailure = () => {'
        );
        $initialization = strrpos(
            $source,
            'renderManualRefreshOutcomeSummaryCopySuccessRate();'
        );

        $this->assertNotFalse($successRecorder);
        $this->assertNotFalse($failureRecorder);
        $this->assertNotFalse($initialization);
        $this->assertStringContainsString(
            "renderManualRefreshOutcomeSummaryCopySuccesses();\n"
            . "            renderManualRefreshOutcomeSummaryCopySuccessRate();",
            $source
        );
        $this->assertStringContainsString(
            "renderManualRefreshOutcomeSummaryCopyFailures();\n"
            . "            renderManualRefreshOutcomeSummaryCopySuccessRate();",
            $source
        );
        $this->assertGreaterThan($failureRecorder, $initialization);
    }

    public function test_attempt_success_failure_copy_and_legacy_contracts_are_preserved(): void
    {
        $source = $this->source();

        foreach ([
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-attempts"',
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-successes"',
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-failures"',
            'let manualRefreshOutcomeSummaryCopyAttemptCount = 0;',
            'let manualRefreshOutcomeSummaryCopySuccessCount = 0;',
            'let manualRefreshOutcomeSummaryCopyFailureCount = 0;',
            'recordManualRefreshOutcomeSummaryCopyAttempt();',
            'recordManualRefreshOutcomeSummaryCopySuccess();',
            'recordManualRefreshOutcomeSummaryCopyFailure();',
            "setManualRefreshOutcomeSummaryCopyStatus('Copied');",
            "'Copy failed'",
            "'Summary unavailable'",
            'lastManualRefreshOutcomeAt.toLocaleString();',
            "refresh.addEventListener('click', loadHealth);",
            'loadHealth();',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        $loadHealth = strpos($source, 'const loadHealth = async () => {');
        $firstTry = strpos($source, 'try {');
        $firstCatch = strpos($source, 'catch (error)');

        $this->assertNotFalse($loadHealth);
        $this->assertNotFalse($firstTry);
        $this->assertNotFalse($firstCatch);
        $this->assertGreaterThan($loadHealth, $firstTry);
        $this->assertGreaterThan($loadHealth, $firstCatch);
    }

    public function test_no_timer_timeout_polling_fallback_or_storage_is_added(): void
    {
        $source = $this->source();

        foreach ([
            'document.execCommand',
            "createElement('textarea')",
            'createElement("textarea")',
            'setInterval(',
            'setTimeout(',
            'localStorage',
            'sessionStorage',
            'indexedDB',
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
