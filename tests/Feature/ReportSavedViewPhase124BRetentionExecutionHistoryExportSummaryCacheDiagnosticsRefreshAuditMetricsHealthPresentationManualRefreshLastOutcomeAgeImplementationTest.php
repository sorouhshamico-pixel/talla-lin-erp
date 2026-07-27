<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase124BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshLastOutcomeAgeImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_age_markup_lookup_and_helpers_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'Last manual refresh outcome age:',
            'id="retention-audit-metrics-health-manual-refresh-last-outcome-age"',
            'const manualRefreshLastOutcomeAge = document.getElementById(',
            'const formatLastManualRefreshOutcomeAge = (',
            'const renderLastManualRefreshOutcomeAge = (currentTime) => {',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_age_formatting_contract_is_locked(): void
    {
        $source = $this->source();

        foreach ([
            "return 'Not available';",
            "return 'Less than 1 minute';",
            'Math.max(',
            'ageMilliseconds / 60000',
            'ageMinutes < 60',
            'ageMinutes < 1440',
            'Math.min(ageMinutes, 999)',
            'Math.min(ageHours, 999)',
            'Math.min(ageDays, 999)',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_age_uses_same_completed_at_value_as_timestamp(): void
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

        $this->assertStringContainsString(
            'const completedAt = new Date();',
            $section
        );
        $this->assertStringContainsString(
            'lastManualRefreshOutcomeAt = completedAt;',
            $section
        );
        $this->assertStringContainsString(
            'renderLastManualRefreshOutcomeTimestamp();',
            $section
        );
        $this->assertStringContainsString(
            'renderLastManualRefreshOutcomeAge(completedAt);',
            $section
        );
        $this->assertSame(
            1,
            substr_count(
                $source,
                'renderLastManualRefreshOutcomeAge(completedAt);'
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

    public function test_previous_timestamp_outcome_and_request_contracts_remain_intact(): void
    {
        $source = $this->source();

        foreach ([
            'setLastManualRefreshOutcomeTimestamp();',
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
