<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase112BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationRequestDurationImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_duration_element_has_locked_initial_state(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            'Last request duration:',
            $source
        );
        $this->assertStringContainsString(
            'id="retention-audit-metrics-health-request-duration"',
            $source
        );
        $this->assertStringContainsString(
            'aria-live="off"',
            $source
        );
        $this->assertStringContainsString(
            ">\n            Not measured yet\n        </span>",
            $source
        );
    }

    public function test_measurement_uses_performance_clock_at_locked_points(): void
    {
        $source = $this->source();

        $this->assertSame(
            2,
            substr_count($source, "performance['now']()")
        );

        $startPosition = strpos(
            $source,
            "const requestStartedAt = performance['now']();"
        );
        $fetchPosition = strpos(
            $source,
            'const response = await fetch('
        );
        $finallyPosition = strpos($source, '} finally {');
        $endPosition = strpos(
            $source,
            "const requestCompletedAt = performance['now']();"
        );

        $this->assertNotFalse($startPosition);
        $this->assertNotFalse($fetchPosition);
        $this->assertNotFalse($finallyPosition);
        $this->assertNotFalse($endPosition);

        $this->assertLessThan($fetchPosition, $startPosition);
        $this->assertGreaterThan($finallyPosition, $endPosition);
        $this->assertStringContainsString(
            'requestCompletedAt - requestStartedAt',
            $source
        );
        $this->assertStringContainsString(
            'Math.max(',
            $source
        );
    }

    public function test_duration_formatting_contract_is_locked(): void
    {
        $source = $this->source();

        foreach ([
            "typeof Intl.NumberFormat === 'function'",
            'new Intl.NumberFormat(undefined, {',
            'maximumFractionDigits: 0',
            'maximumFractionDigits: 2',
            '!Number.isFinite(durationMilliseconds)',
            'durationMilliseconds < 0',
            'durationMilliseconds < 1000',
            'durationMilliseconds.toFixed(0)',
            'durationMilliseconds / 1000',
            'seconds.toFixed(2)',
            'return `${milliseconds} ms`;',
            'return `${formattedSeconds} s`;',
            "return 'Not measured yet';",
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_duration_updates_once_per_completed_request_only(): void
    {
        $source = $this->source();

        $this->assertSame(
            1,
            substr_count(
                $source,
                'requestDuration.textContent = formatRequestDuration('
            )
        );

        $finallyPosition = strpos($source, '} finally {');
        $updatePosition = strpos(
            $source,
            'requestDuration.textContent = formatRequestDuration('
        );
        $timestampPosition = strpos(
            $source,
            'updateTimestamp();'
        );

        $this->assertNotFalse($finallyPosition);
        $this->assertNotFalse($updatePosition);
        $this->assertNotFalse($timestampPosition);

        $this->assertGreaterThan($finallyPosition, $updatePosition);
        $this->assertGreaterThan($updatePosition, $timestampPosition);

        $this->assertStringNotContainsString(
            "status.textContent = 'Loading health status...';\n"
            . '            requestDuration.textContent',
            $source
        );
    }

    public function test_ignored_concurrent_requests_do_not_measure_duration(): void
    {
        $source = $this->source();

        $guardPosition = strpos(
            $source,
            'if (requestInFlight)'
        );
        $startPosition = strpos(
            $source,
            "const requestStartedAt = performance['now']();"
        );

        $this->assertNotFalse($guardPosition);
        $this->assertNotFalse($startPosition);
        $this->assertLessThan($startPosition, $guardPosition);
    }

    public function test_existing_timestamp_status_visual_and_validation_contracts_remain_intact(): void
    {
        $source = $this->source();

        foreach ([
            'retention-audit-metrics-health-updated-at',
            'const completedAt = new Date();',
            'updatedAt.dateTime = completedAt.toISOString();',
            'updateTimestamp();',
            'Audit metrics pipeline is healthy.',
            'Audit metrics pipeline requires attention.',
            'Audit metrics health status is unavailable.',
            'data-health-state="loading"',
            "applyVisualState('loading');",
            "payload.healthy ? 'healthy' : 'unhealthy'",
            "applyVisualState('unavailable');",
            'if (!isValidPayload(payload))',
            "method: 'GET'",
            "credentials: 'same-origin'",
            "Accept: 'application/json'",
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_no_server_timing_polling_retry_or_sensitive_data_is_added(): void
    {
        $source = $this->source();

        foreach ([
            'Server-Timing',
            'serverTiming',
            'response.headers',
            'payload.duration',
            'payload.request_duration',
            'Date.now(',
            'new Date().getTime(',
            'setInterval(',
            'setTimeout(',
            'requestAnimationFrame(',
            'correlation_id',
            'user_id',
            'session_id',
            'ip_address',
            'DB::',
            'Cache::',
            'Log::',
            'Event::',
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
