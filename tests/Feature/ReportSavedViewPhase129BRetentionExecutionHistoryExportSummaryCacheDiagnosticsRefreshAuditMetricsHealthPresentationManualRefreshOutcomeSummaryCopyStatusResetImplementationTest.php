<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase129BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshOutcomeSummaryCopyStatusResetImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_status_resetter_and_sources_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'const resetManualRefreshOutcomeSummaryCopyStatus = () => {',
            'manualRefreshOutcomeSummaryCopyAvailability',
            '.dataset.copyAvailability;',
            "availabilityState === 'available'",
            "setManualRefreshOutcomeSummaryCopyStatus(",
            "'Summary unavailable'",
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_resetter_is_called_from_availability_render_and_before_copy(): void
    {
        $source = $this->source();

        $this->assertSame(
            2,
            substr_count(
                $source,
                'resetManualRefreshOutcomeSummaryCopyStatus();'
            )
        );

        $availabilityRenderer = strpos(
            $source,
            'const renderManualRefreshOutcomeSummaryCopyAvailability = () => {'
        );
        $copyHandler = strpos(
            $source,
            'const copyManualRefreshOutcomeSummary = async () => {'
        );
        $firstReset = strpos(
            $source,
            'resetManualRefreshOutcomeSummaryCopyStatus();'
        );
        $secondReset = strpos(
            $source,
            'resetManualRefreshOutcomeSummaryCopyStatus();',
            $firstReset + 1
        );

        $this->assertNotFalse($availabilityRenderer);
        $this->assertNotFalse($copyHandler);
        $this->assertNotFalse($firstReset);
        $this->assertNotFalse($secondReset);
        $this->assertGreaterThan($availabilityRenderer, $firstReset);
        $this->assertGreaterThan($copyHandler, $secondReset);
    }

    public function test_copy_and_availability_feedback_behavior_are_preserved(): void
    {
        $source = $this->source();

        foreach ([
            'const formatManualRefreshOutcomeSummaryCopyAvailability = () => {',
            'const renderManualRefreshOutcomeSummaryCopyAvailabilityFeedback = () => {',
            "state: 'unavailable'",
            "state: 'available'",
            "state: 'unsupported'",
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
