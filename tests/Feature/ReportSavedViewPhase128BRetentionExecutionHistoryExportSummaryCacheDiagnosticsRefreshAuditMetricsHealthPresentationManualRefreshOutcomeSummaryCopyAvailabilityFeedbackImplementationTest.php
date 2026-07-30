<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase128BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyAvailabilityFeedbackImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_feedback_markup_and_lookup_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-availability"',
            'data-copy-availability="unavailable"',
            'Copy unavailable until a manual refresh completes.',
            'const manualRefreshOutcomeSummaryCopyAvailability =',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_formatter_and_renderer_states_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'const formatManualRefreshOutcomeSummaryCopyAvailability = () => {',
            "state: 'unavailable'",
            'Copy unavailable until a manual refresh completes.',
            "state: 'unsupported'",
            'Clipboard access is unavailable in this browser context.',
            "state: 'available'",
            'Summary ready to copy.',
            'const renderManualRefreshOutcomeSummaryCopyAvailabilityFeedback = () => {',
            'availability.state',
            'availability.text',
            'availability.disabled',
            'renderManualRefreshOutcomeSummaryCopyAvailabilityFeedback();',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_clipboard_support_sources_and_existing_copy_behavior_are_preserved(): void
    {
        $source = $this->source();

        foreach ([
            'window.isSecureContext',
            'navigator.clipboard',
            "typeof navigator.clipboard.writeText === 'function'",
            'const copyManualRefreshOutcomeSummary = async () => {',
            'await navigator.clipboard.writeText(summaryText).then(',
            "setManualRefreshOutcomeSummaryCopyStatus('Copied');",
            "'Copy failed'",
            "'Summary unavailable'",
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_legacy_ordering_and_summary_contract_are_preserved(): void
    {
        $source = $this->source();

        $loadHealth = strpos($source, 'const loadHealth = async () => {');
        $firstTry = strpos($source, 'try {');
        $firstCatch = strpos($source, 'catch (error)');

        $this->assertNotFalse($loadHealth);
        $this->assertNotFalse($firstTry);
        $this->assertNotFalse($firstCatch);
        $this->assertGreaterThan($loadHealth, $firstTry);
        $this->assertGreaterThan($loadHealth, $firstCatch);

        foreach ([
            "].join(' · ')",
            'renderManualRefreshOutcomeSummary(completedAt);',
            'lastManualRefreshOutcomeAt.toLocaleString();',
            "refresh.addEventListener('click', loadHealth);",
            'loadHealth();',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_no_timer_polling_fallback_or_storage_is_added(): void
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
