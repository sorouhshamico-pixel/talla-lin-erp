<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase137BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyLastOutcomeFreshnessImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_freshness_markup_lookup_and_initial_state_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-last-outcome-freshness"',
            'data-freshness-state="unavailable"',
            'Last copy outcome freshness: <span>Unavailable</span>',
            'const manualRefreshOutcomeSummaryCopyLastOutcomeFreshness =',
            'const manualRefreshOutcomeSummaryCopyLastOutcomeFreshnessValue =',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_freshness_formatter_states_threshold_and_clamp_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'const formatManualRefreshOutcomeSummaryCopyLastOutcomeFreshness = (',
            'outcomeAt instanceof Date',
            'currentTime instanceof Date',
            'Number.isNaN(outcomeAt.getTime())',
            'Number.isNaN(currentTime.getTime())',
            'Math.max(',
            'currentTime.getTime() - outcomeAt.getTime()',
            'ageMinutes <= 14',
            "state: 'unavailable'",
            "text: 'Unavailable'",
            "state: 'fresh'",
            "text: 'Fresh'",
            "state: 'stale'",
            "text: 'Stale'",
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_freshness_renderer_runs_after_success_failure_and_initialization(): void
    {
        $source = $this->source();

        $this->assertSame(
            3,
            substr_count(
                $source,
                'renderManualRefreshOutcomeSummaryCopyLastOutcomeFreshness('
            )
        );

        $this->assertSame(
            2,
            substr_count(
                $source,
                "renderManualRefreshOutcomeSummaryCopyLastOutcomeFreshness(\n"
                . "                lastManualRefreshOutcomeSummaryCopyOutcomeAt\n"
                . "            );"
            )
        );

        $this->assertStringContainsString(
            'renderManualRefreshOutcomeSummaryCopyLastOutcomeFreshness(new Date());',
            $source
        );
        $this->assertStringContainsString(
            '.dataset.freshnessState =',
            $source
        );
    }

    public function test_age_timestamp_last_outcome_metrics_and_source_order_are_preserved(): void
    {
        $source = $this->source();

        foreach ([
            'renderManualRefreshOutcomeSummaryCopyLastOutcomeAge(',
            'renderManualRefreshOutcomeSummaryCopyLastOutcomeAt();',
            'renderManualRefreshOutcomeSummaryCopyLastOutcome();',
            'renderManualRefreshOutcomeSummaryCopySuccessRate();',
            'recordManualRefreshOutcomeSummaryCopyAttempt();',
            'recordManualRefreshOutcomeSummaryCopySuccess();',
            'recordManualRefreshOutcomeSummaryCopyFailure();',
            "setManualRefreshOutcomeSummaryCopyStatus('Copied');",
            "'Copy failed'",
            "'Summary unavailable'",
            'lastManualRefreshOutcomeAt.toLocaleString();',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        $this->assertSame(
            3,
            substr_count(
                $source,
                'renderManualRefreshOutcomeSummaryCopyLastOutcomeAge('
            )
        );
        $this->assertSame(
            3,
            substr_count(
                $source,
                'renderManualRefreshOutcomeSummaryCopyLastOutcomeAt();'
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
        $this->assertStringContainsString(
            "renderManualRefreshOutcomeSummaryCopyLastOutcomeAge(\n"
            . "                lastManualRefreshOutcomeSummaryCopyOutcomeAt\n"
            . "            );\n"
            . "            renderManualRefreshOutcomeSummaryCopyLastOutcomeFreshness(",
            $source
        );
    }

    public function test_legacy_try_catch_and_no_automatic_mechanisms_are_preserved(): void
    {
        $source = $this->source();

        $loadHealth = strpos($source, 'const loadHealth = async () => {');
        $firstTry = strpos($source, 'try {');
        $firstCatch = strpos($source, 'catch (error)');

        $this->assertNotFalse($loadHealth);
        $this->assertNotFalse($firstTry);
        $this->assertNotFalse($firstCatch);
        $this->assertGreaterThan($loadHealth, $firstTry);
        $this->assertGreaterThan($loadHealth, $firstCatch);

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
