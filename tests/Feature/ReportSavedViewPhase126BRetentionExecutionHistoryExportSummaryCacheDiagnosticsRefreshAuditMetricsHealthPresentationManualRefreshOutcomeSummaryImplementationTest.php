<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase126BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryImplementationTest extends TestCase
{
    private const PARTIAL = 'resources/views/reports/saved-views/partials/share-activity-retention-audit-metrics-health.blade.php';

    public function test_summary_contract_and_reused_formatters_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'Manual refresh outcome summary:',
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary"',
            'data-summary-state="unavailable"',
            'const formatLastManualRefreshOutcomeTimestamp = (outcomeAt) => {',
            'const formatManualRefreshOutcomeSummary = (',
            'formatLastManualRefreshOutcomeAge(',
            'formatLastManualRefreshOutcomeFreshness(',
            "manualRefreshOutcomeLabels[outcome]",
            "].join(' · ')",
            'const renderManualRefreshOutcomeSummary = (currentTime) => {',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_summary_updates_once_with_the_same_completed_at_value(): void
    {
        $source = $this->source();

        foreach ([
            'renderLastManualRefreshOutcomeTimestamp();',
            'renderLastManualRefreshOutcomeAge(completedAt);',
            'renderLastManualRefreshOutcomeFreshness(completedAt);',
            'renderManualRefreshOutcomeSummary(completedAt);',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        $this->assertSame(1, substr_count(
            $source,
            'renderManualRefreshOutcomeSummary(completedAt);'
        ));
    }

    public function test_legacy_contract_and_no_background_recalculation_are_locked(): void
    {
        $source = $this->source();

        foreach ([
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
            'setInterval(', 'setTimeout(', 'localStorage',
            'sessionStorage', 'indexedDB', 'loadHealth(true);',
            'loadHealth(false);',
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
