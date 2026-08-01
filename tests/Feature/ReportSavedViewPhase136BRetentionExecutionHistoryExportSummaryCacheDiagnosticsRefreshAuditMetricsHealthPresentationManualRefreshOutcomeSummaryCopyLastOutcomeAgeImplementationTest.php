<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase136BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyLastOutcomeAgeImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_copy_last_outcome_age_markup_and_lookup_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-last-outcome-age"',
            'Last copy outcome age: <span>Not available</span>',
            'const manualRefreshOutcomeSummaryCopyLastOutcomeAge =',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_age_formatter_validation_ranges_and_cap_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'const formatManualRefreshOutcomeSummaryCopyLastOutcomeAge = (',
            'outcomeAt instanceof Date',
            'currentTime instanceof Date',
            'Number.isNaN(outcomeAt.getTime())',
            'Number.isNaN(currentTime.getTime())',
            'Math.max(',
            'currentTime.getTime() - outcomeAt.getTime()',
            'Math.floor(ageMilliseconds / 60000)',
            "return 'Less than 1 minute';",
            'Math.min(ageMinutes, 999)',
            'Math.min(ageHours, 999)',
            'Math.min(ageDays, 999)',
            "'Not available'",
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_age_renderer_runs_after_success_failure_and_initialization(): void
    {
        $source = $this->source();

        $this->assertSame(
            3,
            substr_count(
                $source,
                'renderManualRefreshOutcomeSummaryCopyLastOutcomeAge('
            )
        );

        $this->assertSame(
            2,
            substr_count(
                $source,
                "renderManualRefreshOutcomeSummaryCopyLastOutcomeAge(\n"
                . "                lastManualRefreshOutcomeSummaryCopyOutcomeAt\n"
                . "            );"
            )
        );

        $this->assertStringContainsString(
            'renderManualRefreshOutcomeSummaryCopyLastOutcomeAge(new Date());',
            $source
        );
    }

    public function test_timestamp_last_outcome_metrics_copy_and_source_order_are_preserved(): void
    {
        $source = $this->source();

        foreach ([
            'let lastManualRefreshOutcomeSummaryCopyOutcomeAt = null;',
            'lastManualRefreshOutcomeSummaryCopyOutcomeAt = new Date();',
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
            "renderManualRefreshOutcomeSummaryCopyLastOutcome();\n"
            . "            lastManualRefreshOutcomeSummaryCopyOutcomeAt = new Date();\n"
            . "            renderManualRefreshOutcomeSummaryCopyLastOutcomeAt();",
            $source
        );

        $this->assertSame(
            2,
            substr_count(
                $source,
                'lastManualRefreshOutcomeSummaryCopyOutcomeAt = new Date();'
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
