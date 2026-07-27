<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase123BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshLastOutcomeTimestampImplementationTest extends TestCase
{
    private const PARTIAL = 'resources/views/reports/saved-views/partials/share-activity-retention-audit-metrics-health.blade.php';

    public function test_timestamp_contract_is_implemented(): void
    {
        $source = $this->source();
        foreach ([
            'Last manual refresh outcome at:',
            'id="retention-audit-metrics-health-manual-refresh-last-outcome-at"',
            'let lastManualRefreshOutcomeAt = null;',
            'const manualRefreshOutcomeTimestampFormatter =',
            "dateStyle: 'medium'",
            "timeStyle: 'medium'",
            'const renderLastManualRefreshOutcomeTimestamp = () => {',
            'const setLastManualRefreshOutcomeTimestamp = () => {',
            'lastManualRefreshOutcomeAt.toISOString();',
            'lastManualRefreshOutcomeAt.toLocaleString();',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_timestamp_updates_once_through_outcome_setter(): void
    {
        $source = $this->source();
        $this->assertSame(1, substr_count($source, 'setLastManualRefreshOutcomeTimestamp();'));
        $this->assertSame(2, substr_count($source, 'setLastManualRefreshOutcome('));

        $start = strpos($source, 'const setLastManualRefreshOutcome = (outcome) => {');
        $end = strpos($source, "\n        const ", $start + 1);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $section = substr($source, $start, $end - $start);
        $this->assertStringContainsString('renderLastManualRefreshOutcome();', $section);
        $this->assertStringContainsString('setLastManualRefreshOutcomeTimestamp();', $section);
    }

    public function test_invalid_timestamp_removes_datetime_and_shows_unavailable(): void
    {
        $source = $this->source();
        $this->assertStringContainsString("manualRefreshLastOutcomeAt.removeAttribute('datetime');", $source);
        $this->assertStringContainsString("manualRefreshLastOutcomeAt.textContent = 'Not available';", $source);
        $this->assertStringContainsString('Number.isNaN(lastManualRefreshOutcomeAt.getTime())', $source);
    }

    public function test_legacy_request_contract_remains_intact(): void
    {
        $source = $this->source();
        foreach ([
            'const loadHealth = async () => {',
            "refresh.addEventListener('click', loadHealth);",
            'loadHealth();',
            "method: 'GET'",
            "credentials: 'same-origin'",
            "Accept: 'application/json'",
            "setLastManualRefreshOutcome('failed');",
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
        foreach (['localStorage','sessionStorage','setInterval(','setTimeout(','loadHealth(true);'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $source);
        }
    }

    public function test_partial_still_compiles(): void
    {
        $compiled = Blade::compileString($this->source());
        $this->assertNotSame('', trim($compiled));
    }

    private function source(): string
    {
        $source = file_get_contents(base_path(self::PARTIAL));
        $this->assertIsString($source);
        return $source;
    }
}
