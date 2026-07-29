<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase125BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshLastOutcomeFreshnessImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_freshness_markup_lookup_and_helpers_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'Last manual refresh outcome freshness:',
            'id="retention-audit-metrics-health-manual-refresh-last-outcome-freshness"',
            'data-freshness-state="unavailable"',
            'const manualRefreshLastOutcomeFreshness =',
            'const formatLastManualRefreshOutcomeFreshness = (',
            'const renderLastManualRefreshOutcomeFreshness = (',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_freshness_states_and_threshold_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            "state: 'unavailable'",
            "text: 'Unavailable'",
            'return ageMinutes <= 14',
            "state: 'fresh'",
            "text: 'Fresh'",
            "state: 'stale'",
            "text: 'Stale'",
            'Math.max(',
            ') / 60000',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_freshness_uses_same_completed_at_value_as_timestamp_and_age(): void
    {
        $source = $this->source();

        $setterStart = strpos(
            $source,
            'const setLastManualRefreshOutcomeTimestamp = () => {'
        );
        $nextFunction = strpos(
            $source,
            "\n        const ",
            $setterStart + 1
        );

        $this->assertNotFalse($setterStart);
        $this->assertNotFalse($nextFunction);

        $section = substr(
            $source,
            $setterStart,
            $nextFunction - $setterStart
        );

        foreach ([
            'const completedAt = new Date();',
            'lastManualRefreshOutcomeAt = completedAt;',
            'renderLastManualRefreshOutcomeTimestamp();',
            'renderLastManualRefreshOutcomeAge(completedAt);',
            'renderLastManualRefreshOutcomeFreshness(completedAt);',
        ] as $needle) {
            $this->assertStringContainsString($needle, $section);
        }

        $this->assertSame(
            1,
            substr_count(
                $source,
                'renderLastManualRefreshOutcomeFreshness(completedAt);'
            )
        );
    }

    public function test_no_timer_polling_or_persistent_storage_is_added(): void
    {
        $source = $this->source();

        foreach ([
            'setInterval(',
            'setTimeout(',
            'localStorage',
            'sessionStorage',
            'indexedDB',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $source);
        }
    }

    public function test_previous_outcome_timestamp_age_and_request_contracts_remain_intact(): void
    {
        $source = $this->source();

        foreach ([
            'setLastManualRefreshOutcomeTimestamp();',
            'renderLastManualRefreshOutcomeAge(completedAt);',
            'setLastManualRefreshOutcome(',
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
