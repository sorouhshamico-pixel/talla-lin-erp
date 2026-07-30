<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase134BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyLastOutcomeImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_copy_last_outcome_markup_lookup_and_state_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-last-outcome"',
            'Last copy outcome: <span>Not available</span>',
            'const manualRefreshOutcomeSummaryCopyLastOutcome =',
            "let lastManualRefreshOutcomeSummaryCopyOutcome = 'unavailable';",
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_outcome_renderer_and_labels_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'const renderManualRefreshOutcomeSummaryCopyLastOutcome = () => {',
            "unavailable: 'Not available'",
            "success: 'Success'",
            "failure: 'Failure'",
            'labels[lastManualRefreshOutcomeSummaryCopyOutcome]',
            '?? labels.unavailable',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_success_and_failure_recorders_update_outcome_before_render(): void
    {
        $source = $this->source();

        $this->assertSame(
            1,
            substr_count(
                $source,
                "lastManualRefreshOutcomeSummaryCopyOutcome = 'success';"
            )
        );
        $this->assertSame(
            1,
            substr_count(
                $source,
                "lastManualRefreshOutcomeSummaryCopyOutcome = 'failure';"
            )
        );
        $this->assertSame(
            3,
            substr_count(
                $source,
                'renderManualRefreshOutcomeSummaryCopyLastOutcome();'
            )
        );

        $this->assertStringContainsString(
            "lastManualRefreshOutcomeSummaryCopyOutcome = 'success';\n"
            . "            renderManualRefreshOutcomeSummaryCopySuccesses();",
            $source
        );
        $this->assertStringContainsString(
            "lastManualRefreshOutcomeSummaryCopyOutcome = 'failure';\n"
            . "            renderManualRefreshOutcomeSummaryCopyFailures();",
            $source
        );
    }

    public function test_metrics_rate_copy_and_legacy_contracts_are_preserved(): void
    {
        $source = $this->source();

        foreach ([
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-attempts"',
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-successes"',
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-failures"',
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-success-rate"',
            'recordManualRefreshOutcomeSummaryCopyAttempt();',
            'recordManualRefreshOutcomeSummaryCopySuccess();',
            'recordManualRefreshOutcomeSummaryCopyFailure();',
            'renderManualRefreshOutcomeSummaryCopySuccessRate();',
            "setManualRefreshOutcomeSummaryCopyStatus('Copied');",
            "'Copy failed'",
            "'Summary unavailable'",
            'lastManualRefreshOutcomeAt.toLocaleString();',
            "refresh.addEventListener('click', loadHealth);",
            'loadHealth();',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        $this->assertSame(
            3,
            substr_count(
                $source,
                'renderManualRefreshOutcomeSummaryCopySuccessRate();'
            )
        );

        $loadHealth = strpos($source, 'const loadHealth = async () => {');
        $firstTry = strpos($source, 'try {');
        $firstCatch = strpos($source, 'catch (error)');

        $this->assertNotFalse($loadHealth);
        $this->assertNotFalse($firstTry);
        $this->assertNotFalse($firstCatch);
        $this->assertGreaterThan($loadHealth, $firstTry);
        $this->assertGreaterThan($loadHealth, $firstCatch);
    }

    public function test_no_timer_timeout_polling_fallback_storage_or_automatic_reset_is_added(): void
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

        $this->assertSame(
            1,
            substr_count(
                $source,
                "let lastManualRefreshOutcomeSummaryCopyOutcome = 'unavailable';"
            )
        );
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
