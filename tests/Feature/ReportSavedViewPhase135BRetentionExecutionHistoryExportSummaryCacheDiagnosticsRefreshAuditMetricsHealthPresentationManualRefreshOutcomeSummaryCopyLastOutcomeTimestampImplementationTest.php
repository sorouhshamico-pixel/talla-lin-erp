<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase135BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyLastOutcomeTimestampImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_copy_last_outcome_timestamp_markup_lookup_and_state_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-last-outcome-at"',
            'Last copy outcome at: <span>Not available</span>',
            'const manualRefreshOutcomeSummaryCopyLastOutcomeAt =',
            'let lastManualRefreshOutcomeSummaryCopyOutcomeAt = null;',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_timestamp_renderer_validation_and_format_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'const renderManualRefreshOutcomeSummaryCopyLastOutcomeAt = () => {',
            'lastManualRefreshOutcomeSummaryCopyOutcomeAt instanceof Date',
            'Number.isNaN(',
            'lastManualRefreshOutcomeSummaryCopyOutcomeAt.getTime()',
            'lastManualRefreshOutcomeSummaryCopyOutcomeAt.toLocaleString()',
            "'Not available'",
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_success_and_failure_recorders_assign_timestamp_once_each(): void
    {
        $source = $this->source();

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

        $this->assertStringContainsString(
            "renderManualRefreshOutcomeSummaryCopyLastOutcome();\n"
            . "            lastManualRefreshOutcomeSummaryCopyOutcomeAt = new Date();\n"
            . "            renderManualRefreshOutcomeSummaryCopyLastOutcomeAt();",
            $source
        );
        $this->assertStringContainsString(
            "lastManualRefreshOutcomeSummaryCopyOutcome = 'failure';\n"
            . "            renderManualRefreshOutcomeSummaryCopyFailures();",
            $source
        );
    }

    public function test_metrics_last_outcome_copy_and_legacy_contracts_are_preserved(): void
    {
        $source = $this->source();

        foreach ([
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-attempts"',
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-successes"',
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-failures"',
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-success-rate"',
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-last-outcome"',
            'renderManualRefreshOutcomeSummaryCopySuccessRate();',
            'renderManualRefreshOutcomeSummaryCopyLastOutcome();',
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
        $this->assertSame(
            3,
            substr_count(
                $source,
                'renderManualRefreshOutcomeSummaryCopyLastOutcome();'
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
                'let lastManualRefreshOutcomeSummaryCopyOutcomeAt = null;'
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
