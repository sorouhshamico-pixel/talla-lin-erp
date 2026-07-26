<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase121BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshSuccessRateImplementationTest extends TestCase
{
    private const PARTIAL = 'resources/views/reports/saved-views/partials/share-activity-retention-audit-metrics-health.blade.php';

    public function test_success_rate_contract_is_implemented(): void
    {
        $source = $this->source();

        foreach ([
            'Manual refresh success rate:',
            'id="retention-audit-metrics-health-manual-refresh-success-rate"',
            'const manualRefreshSuccessRate = document.getElementById(',
            'const manualRefreshRateFormatter =',
            'maximumFractionDigits: 1',
            'const renderManualRefreshSuccessRate = () => {',
            "manualRefreshSuccessRate.textContent = 'Not available';",
            '(manualRefreshSuccesses / manualRefreshAttempts) * 100',
            '`${formattedPercentage}%`',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        $this->assertSame(3, substr_count($source, 'renderManualRefreshSuccessRate();'));
    }

    public function test_rate_updates_after_attempt_success_and_failure(): void
    {
        $source = $this->source();

        foreach (['recordManualRefreshAttempt', 'recordManualRefreshSuccess', 'recordManualRefreshFailure'] as $function) {
            $start = strpos($source, "const {$function} = () => {");
            $this->assertNotFalse($start);
            $next = strpos($source, "\n        const ", $start + 1);
            $section = $next === false ? substr($source, $start) : substr($source, $start, $next - $start);
            $this->assertStringContainsString('renderManualRefreshSuccessRate();', $section);
        }
    }

    public function test_existing_counters_and_legacy_request_contract_remain_intact(): void
    {
        $source = $this->source();

        foreach ([
            'recordManualRefreshAttempt();',
            'recordManualRefreshSuccess();',
            'recordManualRefreshFailure();',
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
