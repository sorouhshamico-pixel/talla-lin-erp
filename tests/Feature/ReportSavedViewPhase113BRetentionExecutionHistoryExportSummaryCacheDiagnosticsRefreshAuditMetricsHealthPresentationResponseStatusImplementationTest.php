<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase113BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationResponseStatusImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_response_status_element_has_locked_initial_state(): void
    {
        $source = $this->source();

        $this->assertStringContainsString('Last response:', $source);
        $this->assertStringContainsString(
            'id="retention-audit-metrics-health-response-status"',
            $source
        );
        $this->assertStringContainsString(
            'aria-live="off"',
            $source
        );
        $this->assertStringContainsString(
            ">\n            Not received yet\n        </span>",
            $source
        );
    }

    public function test_response_status_formatter_locks_safe_numeric_range_and_text(): void
    {
        $source = $this->source();

        foreach ([
            'const formatResponseStatus = (response) => {',
            '!Number.isInteger(response.status)',
            'response.status < 100',
            'response.status > 599',
            "return 'Not received yet';",
            "typeof response.statusText === 'string'",
            'response.statusText.trim()',
            "statusText === ''",
            'String(response.status)',
            '`${response.status} ${statusText}`',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_http_status_updates_immediately_after_response_before_parsing(): void
    {
        $source = $this->source();

        $fetchPosition = strpos(
            $source,
            'const response = await fetch('
        );
        $receivedPosition = strpos(
            $source,
            'responseReceived = true;'
        );
        $statusPosition = strpos(
            $source,
            'responseStatus.textContent = formatResponseStatus(response);'
        );
        $okPosition = strpos(
            $source,
            'if (!response.ok)'
        );
        $jsonPosition = strpos(
            $source,
            'const payload = await response.json();'
        );

        $this->assertNotFalse($fetchPosition);
        $this->assertNotFalse($receivedPosition);
        $this->assertNotFalse($statusPosition);
        $this->assertNotFalse($okPosition);
        $this->assertNotFalse($jsonPosition);

        $this->assertGreaterThan($fetchPosition, $receivedPosition);
        $this->assertGreaterThan($receivedPosition, $statusPosition);
        $this->assertGreaterThan($statusPosition, $okPosition);
        $this->assertGreaterThan($okPosition, $jsonPosition);

        $this->assertSame(
            1,
            substr_count(
                $source,
                'responseStatus.textContent = formatResponseStatus(response);'
            )
        );
    }

    public function test_network_failure_updates_without_overwriting_received_http_status(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            'let responseReceived = false;',
            $source
        );
        $this->assertStringContainsString(
            'if (!responseReceived)',
            $source
        );
        $this->assertStringContainsString(
            "responseStatus.textContent = 'Network error';",
            $source
        );
        $this->assertSame(
            1,
            substr_count(
                $source,
                "responseStatus.textContent = 'Network error';"
            )
        );

        $catchPosition = strpos($source, '} catch (error) {');
        $networkPosition = strpos(
            $source,
            "responseStatus.textContent = 'Network error';"
        );

        $this->assertNotFalse($catchPosition);
        $this->assertNotFalse($networkPosition);
        $this->assertGreaterThan($catchPosition, $networkPosition);
    }

    public function test_request_start_and_ignored_concurrent_request_preserve_previous_status(): void
    {
        $source = $this->source();

        $guardPosition = strpos($source, 'if (requestInFlight)');
        $responseFlagPosition = strpos(
            $source,
            'let responseReceived = false;'
        );

        $this->assertNotFalse($guardPosition);
        $this->assertNotFalse($responseFlagPosition);
        $this->assertLessThan($responseFlagPosition, $guardPosition);

        $this->assertStringNotContainsString(
            "status.textContent = 'Loading health status...';\n"
            . "            responseStatus.textContent = 'Not received yet';",
            $source
        );
    }

    public function test_existing_duration_timestamp_status_visual_and_validation_remain_intact(): void
    {
        $source = $this->source();

        foreach ([
            'retention-audit-metrics-health-request-duration',
            "performance['now']()",
            'formatRequestDuration(',
            'retention-audit-metrics-health-updated-at',
            'const completedAt = new Date();',
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

    public function test_response_status_does_not_render_body_headers_urls_or_errors(): void
    {
        $source = $this->source();

        foreach ([
            'response.text(',
            'response.headers',
            'response.url',
            'response.redirected',
            'error.message',
            'exception.message',
            'stack',
            'payload.status',
            'payload.statusText',
            'correlation_id',
            'user_id',
            'session_id',
            'ip_address',
            'setInterval(',
            'setTimeout(',
            'location.reload(',
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
