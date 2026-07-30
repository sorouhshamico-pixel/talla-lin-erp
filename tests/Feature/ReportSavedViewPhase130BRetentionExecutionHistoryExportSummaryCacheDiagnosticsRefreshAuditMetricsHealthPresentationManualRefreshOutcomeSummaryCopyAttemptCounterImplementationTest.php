<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase130BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyAttemptCounterImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_copy_attempt_counter_markup_lookup_and_state_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-attempts"',
            'Copy attempts: <span>0</span>',
            'const manualRefreshOutcomeSummaryCopyAttempts =',
            'let manualRefreshOutcomeSummaryCopyAttemptCount = 0;',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_renderer_and_recorder_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'const renderManualRefreshOutcomeSummaryCopyAttempts = () => {',
            'Number.isInteger(',
            'manualRefreshOutcomeSummaryCopyAttemptCount >= 0',
            'Math.min(',
            '999',
            'manualRefreshOutcomeSummaryCopyAttempts.textContent =',
            'const recordManualRefreshOutcomeSummaryCopyAttempt = () => {',
            'manualRefreshOutcomeSummaryCopyAttemptCount + 1',
            'renderManualRefreshOutcomeSummaryCopyAttempts();',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_attempt_is_recorded_once_before_clipboard_write(): void
    {
        $source = $this->source();

        $this->assertSame(
            1,
            substr_count(
                $source,
                'recordManualRefreshOutcomeSummaryCopyAttempt();'
            )
        );

        $record = strpos(
            $source,
            'recordManualRefreshOutcomeSummaryCopyAttempt();'
        );
        $clipboard = strpos(
            $source,
            'await navigator.clipboard.writeText(summaryText).then('
        );

        $this->assertNotFalse($record);
        $this->assertNotFalse($clipboard);
        $this->assertGreaterThan($record, $clipboard);
    }

    public function test_unavailable_and_unsupported_clicks_do_not_increment(): void
    {
        $source = $this->source();

        $handler = strpos(
            $source,
            'const copyManualRefreshOutcomeSummary = async () => {'
        );
        $recorder = strpos(
            $source,
            'recordManualRefreshOutcomeSummaryCopyAttempt();'
        );
        $summaryUnavailable = strpos(
            $source,
            "summaryState === 'unavailable'",
            $handler
        );
        $clipboardUnsupported = strpos(
            $source,
            "!window.isSecureContext",
            $handler
        );

        $this->assertNotFalse($handler);
        $this->assertNotFalse($recorder);
        $this->assertNotFalse($summaryUnavailable);
        $this->assertNotFalse($clipboardUnsupported);
        $this->assertGreaterThan($summaryUnavailable, $recorder);
        $this->assertGreaterThan($clipboardUnsupported, $recorder);
    }

    public function test_copy_status_availability_and_legacy_contracts_are_preserved(): void
    {
        $source = $this->source();

        foreach ([
            'resetManualRefreshOutcomeSummaryCopyStatus();',
            'const formatManualRefreshOutcomeSummaryCopyAvailability = () => {',
            'const renderManualRefreshOutcomeSummaryCopyAvailabilityFeedback = () => {',
            "state: 'unavailable'",
            "state: 'available'",
            "state: 'unsupported'",
            "setManualRefreshOutcomeSummaryCopyStatus('Copied');",
            "'Copy failed'",
            "'Summary unavailable'",
            "].join(' · ')",
            'lastManualRefreshOutcomeAt.toLocaleString();',
            "refresh.addEventListener('click', loadHealth);",
            'loadHealth();',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        $loadHealth = strpos($source, 'const loadHealth = async () => {');
        $firstTry = strpos($source, 'try {');
        $firstCatch = strpos($source, 'catch (error)');

        $this->assertNotFalse($loadHealth);
        $this->assertNotFalse($firstTry);
        $this->assertNotFalse($firstCatch);
        $this->assertGreaterThan($loadHealth, $firstTry);
        $this->assertGreaterThan($loadHealth, $firstCatch);
    }

    public function test_no_timer_timeout_polling_fallback_or_storage_is_added(): void
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
