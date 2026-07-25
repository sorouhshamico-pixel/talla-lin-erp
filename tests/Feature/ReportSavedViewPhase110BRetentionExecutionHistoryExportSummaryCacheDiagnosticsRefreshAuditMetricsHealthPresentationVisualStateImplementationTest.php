<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase110BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationVisualStateImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_panel_and_indicator_lock_initial_visual_state(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            'class="retention-audit-metrics-health-panel is-loading"',
            $source
        );
        $this->assertStringContainsString(
            'data-health-state="loading"',
            $source
        );
        $this->assertStringContainsString(
            'id="retention-audit-metrics-health-indicator"',
            $source
        );
        $this->assertStringContainsString(
            'class="retention-audit-metrics-health-indicator is-loading"',
            $source
        );
        $this->assertStringContainsString(
            'aria-hidden="true"',
            $source
        );
        $this->assertStringContainsString(
            ">\n        Loading\n    </p>",
            $source
        );
    }

    public function test_visual_state_helper_locks_exact_allowed_classes_and_labels(): void
    {
        $source = $this->source();

        foreach ([
            "'is-loading'",
            "'is-healthy'",
            "'is-unhealthy'",
            "'is-unavailable'",
            "loading: 'Loading'",
            "healthy: 'Healthy'",
            "unhealthy: 'Requires attention'",
            "unavailable: 'Unavailable'",
            'panel.dataset.healthState = state;',
            'panel.classList.remove(...stateClasses);',
            'panel.classList.add(stateClass);',
            'indicator.classList.remove(...stateClasses);',
            'indicator.classList.add(stateClass);',
            'indicator.textContent = indicatorLabels[state];',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        $this->assertSame(
            4,
            substr_count($source, "'is-")
        );
    }

    public function test_state_transitions_are_applied_at_locked_points(): void
    {
        $source = $this->source();

        $loadingPosition = strpos(
            $source,
            "applyVisualState('loading');"
        );
        $fetchPosition = strpos(
            $source,
            'const response = await fetch('
        );
        $renderPosition = strpos(
            $source,
            'setFields(payload);'
        );
        $healthyPosition = strpos(
            $source,
            "payload.healthy ? 'healthy' : 'unhealthy'"
        );
        $unavailablePosition = strpos(
            $source,
            "applyVisualState('unavailable');"
        );

        $this->assertNotFalse($loadingPosition);
        $this->assertNotFalse($fetchPosition);
        $this->assertNotFalse($renderPosition);
        $this->assertNotFalse($healthyPosition);
        $this->assertNotFalse($unavailablePosition);

        $this->assertLessThan($fetchPosition, $loadingPosition);
        $this->assertGreaterThan($renderPosition, $healthyPosition);

        $this->assertStringContainsString(
            "applyVisualState(\n"
            . "                    payload.healthy ? 'healthy' : 'unhealthy'\n"
            . "                );",
            $source
        );
    }

    public function test_accessible_textual_status_remains_primary(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            'role="status"',
            $source
        );
        $this->assertStringContainsString(
            'aria-live="polite"',
            $source
        );
        $this->assertStringContainsString(
            'aria-hidden="true"',
            $source
        );

        foreach ([
            'Loading health status...',
            'Audit metrics pipeline is healthy.',
            'Audit metrics pipeline requires attention.',
            'Audit metrics health status is unavailable.',
        ] as $message) {
            $this->assertStringContainsString($message, $source);
        }

        $this->assertStringNotContainsString(
            'status.setAttribute(\'aria-hidden\'',
            $source
        );
    }

    public function test_visual_state_does_not_leak_payload_or_use_inline_styles(): void
    {
        $source = $this->source();

        foreach ([
            '.style.',
            'setAttribute(\'style\'',
            'cssText',
            'panel.dataset.healthState = payload',
            'panel.classList.add(payload',
            'indicator.classList.add(payload',
            'indicator.textContent = payload',
            'setAttribute(\'data-health-state\', payload',
            'exception.message',
            'error.message',
            'response.text(',
            'correlation_id',
            'user_id',
            'ip_address',
            'session_id',
            'cache_key',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source
            );
        }
    }

    public function test_request_and_validation_behavior_remain_unchanged(): void
    {
        $source = $this->source();

        foreach ([
            "method: 'GET'",
            "credentials: 'same-origin'",
            "Accept: 'application/json'",
            'let requestInFlight = false;',
            'if (requestInFlight)',
            'if (!isValidPayload(payload))',
            "refresh.addEventListener('click', loadHealth);",
            'loadHealth();',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        foreach ([
            'setInterval(',
            'setTimeout(',
            'location.reload(',
            'window.location',
            'DB::',
            'Cache::',
            'Log::',
            'Event::',
            'dispatch(',
            'event(',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source
            );
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
