<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase138BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyLastOutcomeFreshnessReasonImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_reason_markup_lookup_and_initial_text_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-last-outcome-freshness-reason"',
            'Freshness reason: <span>No completed copy outcome yet.</span>',
            'const manualRefreshOutcomeSummaryCopyLastOutcomeFreshnessReason =',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_reason_formatter_states_and_exact_text_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'const formatManualRefreshOutcomeSummaryCopyLastOutcomeFreshnessReason = (',
            'formatManualRefreshOutcomeSummaryCopyLastOutcomeFreshness(',
            "freshness.state === 'fresh'",
            "freshness.state === 'stale'",
            "return 'The latest copy outcome is within the 14-minute freshness window.';",
            "return 'The latest copy outcome is older than the 14-minute freshness window.';",
            "return 'No completed copy outcome yet.';",
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_reason_renderer_runs_after_success_failure_and_initialization(): void
    {
        $source = $this->source();

        $this->assertSame(
            3,
            substr_count(
                $source,
                'renderManualRefreshOutcomeSummaryCopyLastOutcomeFreshnessReason('
            )
        );

        $this->assertSame(
            2,
            substr_count(
                $source,
                "renderManualRefreshOutcomeSummaryCopyLastOutcomeFreshnessReason(\n"
                . "                lastManualRefreshOutcomeSummaryCopyOutcomeAt\n"
                . "            );"
            )
        );

        $this->assertStringContainsString(
            "renderManualRefreshOutcomeSummaryCopyLastOutcomeFreshnessReason(\n"
            . "            new Date()\n"
            . "        );",
            $source
        );
    }

    public function test_freshness_age_timestamp_metrics_and_source_order_are_preserved(): void
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

        foreach ([
            'recordManualRefreshOutcomeSummaryCopyAttempt();',
            'recordManualRefreshOutcomeSummaryCopySuccess();',
            'recordManualRefreshOutcomeSummaryCopyFailure();',
            'renderManualRefreshOutcomeSummaryCopySuccessRate();',
            "setManualRefreshOutcomeSummaryCopyStatus('Copied');",
            "'Copy failed'",
            "'Summary unavailable'",
            'lastManualRefreshOutcomeAt.toLocaleString();',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        $this->assertStringContainsString(
            "renderManualRefreshOutcomeSummaryCopyLastOutcomeFreshness(\n"
            . "                lastManualRefreshOutcomeSummaryCopyOutcomeAt\n"
            . "            );\n"
            . "            renderManualRefreshOutcomeSummaryCopyLastOutcomeFreshnessReason(",
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
