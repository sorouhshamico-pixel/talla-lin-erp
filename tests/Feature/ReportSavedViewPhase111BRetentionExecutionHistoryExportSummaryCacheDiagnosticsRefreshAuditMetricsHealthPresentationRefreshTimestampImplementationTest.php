<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase111BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationRefreshTimestampImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_semantic_timestamp_element_has_locked_initial_state(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            'Last checked:',
            $source
        );
        $this->assertStringContainsString(
            '<time',
            $source
        );
        $this->assertStringContainsString(
            'id="retention-audit-metrics-health-updated-at"',
            $source
        );
        $this->assertStringContainsString(
            'aria-live="off"',
            $source
        );
        $this->assertStringContainsString(
            ">\n            Not updated yet\n        </time>",
            $source
        );

        $openingTime = strstr($source, '<time');
        $timeOpeningTag = strstr($openingTime, '>', true);

        $this->assertIsString($timeOpeningTag);
        $this->assertStringNotContainsString(
            'datetime=',
            $timeOpeningTag
        );
    }

    public function test_client_clock_and_local_formatting_are_locked(): void
    {
        $source = $this->source();

        foreach ([
            'const completedAt = new Date();',
            "typeof Intl !== 'undefined'",
            "typeof Intl.DateTimeFormat === 'function'",
            'new Intl.DateTimeFormat(undefined, {',
            "dateStyle: 'medium'",
            "timeStyle: 'medium'",
            'timestampFormatter.format(completedAt)',
            'completedAt.toLocaleString()',
            'completedAt.toISOString()',
            'updatedAt.dateTime =',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        $this->assertStringContainsString(
            'Number.isNaN(completedAt.getTime())',
            $source
        );
        $this->assertStringContainsString(
            "updatedAt.textContent = 'Not updated yet';",
            $source
        );
    }

    public function test_timestamp_updates_once_per_completed_request_only(): void
    {
        $source = $this->source();

        $this->assertSame(
            1,
            substr_count($source, 'updateTimestamp();')
        );

        $finallyPosition = strpos($source, '} finally {');
        $timestampPosition = strpos($source, 'updateTimestamp();');
        $inFlightResetPosition = strpos(
            $source,
            'requestInFlight = false;',
            $finallyPosition
        );

        $this->assertNotFalse($finallyPosition);
        $this->assertNotFalse($timestampPosition);
        $this->assertNotFalse($inFlightResetPosition);
        $this->assertGreaterThan(
            $finallyPosition,
            $timestampPosition
        );
        $this->assertLessThan(
            $inFlightResetPosition,
            $timestampPosition
        );

        $guardPosition = strpos(
            $source,
            'if (requestInFlight)'
        );
        $requestStartPosition = strpos(
            $source,
            'requestInFlight = true;'
        );

        $this->assertNotFalse($guardPosition);
        $this->assertNotFalse($requestStartPosition);
        $this->assertLessThan(
            $requestStartPosition,
            $guardPosition
        );
        $this->assertStringNotContainsString(
            "status.textContent = 'Loading health status...';\n"
            . '            updateTimestamp();',
            $source
        );
    }

    public function test_timestamp_is_outside_status_region_and_not_announced(): void
    {
        $source = $this->source();

        $statusEnd = strpos(
            $source,
            '</p>',
            strpos(
                $source,
                'id="retention-audit-metrics-health-status"'
            )
        );
        $timestampPosition = strpos(
            $source,
            'id="retention-audit-metrics-health-updated-at"'
        );

        $this->assertNotFalse($statusEnd);
        $this->assertNotFalse($timestampPosition);
        $this->assertGreaterThan($statusEnd, $timestampPosition);

        $this->assertStringNotContainsString(
            'id="retention-audit-metrics-health-updated-at"'
            . "\n            aria-live=\"polite\"",
            $source
        );
    }

    public function test_existing_status_visual_and_validation_contracts_remain_intact(): void
    {
        $source = $this->source();

        foreach ([
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

    public function test_no_server_time_polling_timer_or_sensitive_data_is_added(): void
    {
        $source = $this->source();

        foreach ([
            'server_time',
            'response.headers',
            'Date.parse(payload',
            'payload.updated_at',
            'payload.timestamp',
            'performance.now(',
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
