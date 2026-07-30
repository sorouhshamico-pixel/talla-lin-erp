<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase131BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopySuccessCounterImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_copy_success_counter_markup_lookup_and_state_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-successes"',
            'Copy successes: <span>0</span>',
            'const manualRefreshOutcomeSummaryCopySuccesses =',
            'let manualRefreshOutcomeSummaryCopySuccessCount = 0;',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_success_renderer_and_recorder_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'const renderManualRefreshOutcomeSummaryCopySuccesses = () => {',
            'Number.isInteger(',
            'manualRefreshOutcomeSummaryCopySuccessCount >= 0',
            'Math.min(',
            '999',
            'manualRefreshOutcomeSummaryCopySuccesses.textContent =',
            'const recordManualRefreshOutcomeSummaryCopySuccess = () => {',
            'manualRefreshOutcomeSummaryCopySuccessCount + 1',
            'renderManualRefreshOutcomeSummaryCopySuccesses();',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_success_is_recorded_once_in_success_callback_before_status(): void
    {
        $source = $this->source();

        $this->assertSame(
            1,
            substr_count(
                $source,
                'recordManualRefreshOutcomeSummaryCopySuccess();'
            )
        );

        $clipboard = strpos(
            $source,
            'await navigator.clipboard.writeText(summaryText).then('
        );
        $record = strpos(
            $source,
            'recordManualRefreshOutcomeSummaryCopySuccess();'
        );
        $status = strpos(
            $source,
            "setManualRefreshOutcomeSummaryCopyStatus('Copied');"
        );

        $this->assertNotFalse($clipboard);
        $this->assertNotFalse($record);
        $this->assertNotFalse($status);
        $this->assertGreaterThan($clipboard, $record);
        $this->assertGreaterThan($record, $status);

        $this->assertStringContainsString(
            "() => {\n                    "
            . "recordManualRefreshOutcomeSummaryCopySuccess();\n"
            . "                    "
            . "setManualRefreshOutcomeSummaryCopyStatus('Copied');",
            $source
        );
    }

    public function test_rejected_writes_do_not_record_success(): void
    {
        $source = $this->source();

        $record = strpos(
            $source,
            'recordManualRefreshOutcomeSummaryCopySuccess();'
        );
        $failureLabel = strpos(
            $source,
            "'Copy failed'",
            $record
        );

        $this->assertNotFalse($record);
        $this->assertNotFalse($failureLabel);
        $this->assertGreaterThan($record, $failureLabel);

        $failureTail = substr($source, $failureLabel);

        $this->assertStringNotContainsString(
            'recordManualRefreshOutcomeSummaryCopySuccess();',
            $failureTail
        );
    }

    public function test_attempt_counter_copy_status_availability_and_legacy_contracts_are_preserved(): void
    {
        $source = $this->source();

        foreach ([
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-attempts"',
            'let manualRefreshOutcomeSummaryCopyAttemptCount = 0;',
            'const renderManualRefreshOutcomeSummaryCopyAttempts = () => {',
            'const recordManualRefreshOutcomeSummaryCopyAttempt = () => {',
            'recordManualRefreshOutcomeSummaryCopyAttempt();',
            'resetManualRefreshOutcomeSummaryCopyStatus();',
            'const formatManualRefreshOutcomeSummaryCopyAvailability = () => {',
            'const renderManualRefreshOutcomeSummaryCopyAvailabilityFeedback = () => {',
            "state: 'unavailable'",
            "state: 'available'",
            "state: 'unsupported'",
            "'Summary unavailable'",
            "].join(' · ')",
            'lastManualRefreshOutcomeAt.toLocaleString();',
            "refresh.addEventListener('click', loadHealth);",
            'loadHealth();',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        $attemptRecord = strpos(
            $source,
            'recordManualRefreshOutcomeSummaryCopyAttempt();'
        );
        $clipboard = strpos(
            $source,
            'await navigator.clipboard.writeText(summaryText).then('
        );

        $this->assertNotFalse($attemptRecord);
        $this->assertNotFalse($clipboard);
        $this->assertGreaterThan($attemptRecord, $clipboard);

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
