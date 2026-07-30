<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase132BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyFailureCounterImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_copy_failure_counter_markup_lookup_and_state_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-failures"',
            'Copy failures: <span>0</span>',
            'const manualRefreshOutcomeSummaryCopyFailures =',
            'let manualRefreshOutcomeSummaryCopyFailureCount = 0;',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_failure_renderer_and_recorder_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'const renderManualRefreshOutcomeSummaryCopyFailures = () => {',
            'Number.isInteger(',
            'manualRefreshOutcomeSummaryCopyFailureCount >= 0',
            'Math.min(',
            '999',
            'manualRefreshOutcomeSummaryCopyFailures.textContent =',
            'const recordManualRefreshOutcomeSummaryCopyFailure = () => {',
            'manualRefreshOutcomeSummaryCopyFailureCount + 1',
            'renderManualRefreshOutcomeSummaryCopyFailures();',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_failure_is_recorded_once_in_failure_callback_before_status(): void
    {
        $source = $this->source();

        $this->assertSame(
            1,
            substr_count(
                $source,
                'recordManualRefreshOutcomeSummaryCopyFailure();'
            )
        );

        $clipboard = strpos(
            $source,
            'await navigator.clipboard.writeText(summaryText).then('
        );
        $record = strpos(
            $source,
            'recordManualRefreshOutcomeSummaryCopyFailure();'
        );
        $status = strpos(
            $source,
            "setManualRefreshOutcomeSummaryCopyStatus(\n"
            . "                        'Copy failed'"
        );

        $this->assertNotFalse($clipboard);
        $this->assertNotFalse($record);
        $this->assertNotFalse($status);
        $this->assertGreaterThan($clipboard, $record);
        $this->assertGreaterThan($record, $status);

        $this->assertStringContainsString(
            "() => {\n                    "
            . "recordManualRefreshOutcomeSummaryCopyFailure();\n"
            . "                    "
            . "setManualRefreshOutcomeSummaryCopyStatus(\n"
            . "                        'Copy failed'",
            $source
        );
    }

    public function test_resolved_writes_do_not_record_failure(): void
    {
        $source = $this->source();

        $successRecord = strpos(
            $source,
            'recordManualRefreshOutcomeSummaryCopySuccess();'
        );
        $failureRecord = strpos(
            $source,
            'recordManualRefreshOutcomeSummaryCopyFailure();'
        );

        $this->assertNotFalse($successRecord);
        $this->assertNotFalse($failureRecord);
        $this->assertGreaterThan($successRecord, $failureRecord);

        $successSegment = substr(
            $source,
            $successRecord,
            $failureRecord - $successRecord
        );

        $this->assertStringNotContainsString(
            'recordManualRefreshOutcomeSummaryCopyFailure();',
            $successSegment
        );
    }

    public function test_attempt_success_copy_status_availability_and_legacy_contracts_are_preserved(): void
    {
        $source = $this->source();

        foreach ([
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-attempts"',
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-successes"',
            'let manualRefreshOutcomeSummaryCopyAttemptCount = 0;',
            'let manualRefreshOutcomeSummaryCopySuccessCount = 0;',
            'const recordManualRefreshOutcomeSummaryCopyAttempt = () => {',
            'const recordManualRefreshOutcomeSummaryCopySuccess = () => {',
            'recordManualRefreshOutcomeSummaryCopyAttempt();',
            'recordManualRefreshOutcomeSummaryCopySuccess();',
            'resetManualRefreshOutcomeSummaryCopyStatus();',
            'const formatManualRefreshOutcomeSummaryCopyAvailability = () => {',
            'const renderManualRefreshOutcomeSummaryCopyAvailabilityFeedback = () => {',
            "state: 'unavailable'",
            "state: 'available'",
            "state: 'unsupported'",
            "setManualRefreshOutcomeSummaryCopyStatus('Copied');",
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
        $successRecord = strpos(
            $source,
            'recordManualRefreshOutcomeSummaryCopySuccess();'
        );
        $failureRecord = strpos(
            $source,
            'recordManualRefreshOutcomeSummaryCopyFailure();'
        );

        $this->assertNotFalse($attemptRecord);
        $this->assertNotFalse($clipboard);
        $this->assertNotFalse($successRecord);
        $this->assertNotFalse($failureRecord);
        $this->assertGreaterThan($attemptRecord, $clipboard);
        $this->assertGreaterThan($clipboard, $successRecord);
        $this->assertGreaterThan($successRecord, $failureRecord);

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
