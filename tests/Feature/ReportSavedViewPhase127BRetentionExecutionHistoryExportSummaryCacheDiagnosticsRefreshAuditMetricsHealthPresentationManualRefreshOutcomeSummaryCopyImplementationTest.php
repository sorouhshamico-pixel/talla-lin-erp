<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase127BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_copy_button_status_and_lookups_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy"',
            'Copy summary',
            'disabled',
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-status"',
            'const manualRefreshOutcomeSummaryCopy =',
            'const manualRefreshOutcomeSummaryCopyStatus =',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_copy_availability_contract_is_locked(): void
    {
        $source = $this->source();

        foreach ([
            'const renderManualRefreshOutcomeSummaryCopyAvailability = () => {',
            "summaryState !== 'unavailable'",
            "summaryText !== 'Not available'",
            'manualRefreshOutcomeSummaryCopy.disabled =',
            "'Summary unavailable'",
            'renderManualRefreshOutcomeSummaryCopyAvailability();',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_clipboard_write_requires_secure_context_and_explicit_click(): void
    {
        $source = $this->source();

        foreach ([
            'const copyManualRefreshOutcomeSummary = async () => {',
            'window.isSecureContext',
            'navigator.clipboard',
            "typeof navigator.clipboard.writeText !== 'function'",
            'await navigator.clipboard.writeText(summaryText).then(',
            "setManualRefreshOutcomeSummaryCopyStatus('Copied');",
            "'Copy failed'",
            "manualRefreshOutcomeSummaryCopy.addEventListener(",
            "'click',",
            'copyManualRefreshOutcomeSummary',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        $this->assertSame(
            1,
            substr_count(
                $source,
                'await navigator.clipboard.writeText(summaryText).then('
            )
        );
    }

    public function test_no_legacy_clipboard_fallback_timer_or_storage_is_added(): void
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
    }

    public function test_summary_and_previous_request_contracts_remain_intact(): void
    {
        $source = $this->source();

        foreach ([
            'formatManualRefreshOutcomeSummary(',
            "].join(' · ')",
            'renderManualRefreshOutcomeSummary(completedAt);',
            'lastManualRefreshOutcomeAt.toLocaleString();',
            'const loadHealth = async () => {',
            "refresh.addEventListener('click', loadHealth);",
            'loadHealth();',
            "method: 'GET'",
            "credentials: 'same-origin'",
            "Accept: 'application/json'",
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
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
