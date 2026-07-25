<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase115BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationLastSuccessfulCheckImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_last_successful_check_element_has_locked_initial_state(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            'Last successful check:',
            $source
        );
        $this->assertStringContainsString(
            'id="retention-audit-metrics-health-last-successful-check"',
            $source
        );
        $this->assertStringContainsString(
            'aria-live="off"',
            $source
        );
        $this->assertStringContainsString(
            ">\n            No successful check yet\n        </time>",
            $source
        );
        $this->assertStringNotContainsString(
            'id="retention-audit-metrics-health-last-successful-check" datetime=',
            $source
        );
    }

    public function test_update_helper_uses_client_completion_time_and_existing_formatter(): void
    {
        $source = $this->source();

        foreach ([
            'const updateLastSuccessfulCheck = () => {',
            'const completedAt = new Date();',
            'Number.isNaN(completedAt.getTime())',
            "lastSuccessfulCheck.removeAttribute('datetime');",
            "lastSuccessfulCheck.textContent =\n"
                . "                    'No successful check yet';",
            'lastSuccessfulCheck.dateTime = completedAt.toISOString();',
            'lastSuccessfulCheck.textContent = timestampFormatter',
            'timestampFormatter.format(completedAt)',
            'completedAt.toLocaleString()',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_only_validated_healthy_payload_updates_last_successful_check(): void
    {
        $source = $this->source();

        $validationPosition = strpos(
            $source,
            'if (!isValidPayload(payload))'
        );
        $fieldsPosition = strpos(
            $source,
            'setFields(payload);'
        );
        $healthyGuardPosition = strpos(
            $source,
            'if (payload.healthy)'
        );
        $updatePosition = strpos(
            $source,
            'updateLastSuccessfulCheck();'
        );
        $successFlagPosition = strpos(
            $source,
            'requestSucceeded = true;'
        );
        $catchPosition = strpos(
            $source,
            '} catch (error) {'
        );

        $this->assertNotFalse($validationPosition);
        $this->assertNotFalse($fieldsPosition);
        $this->assertNotFalse($healthyGuardPosition);
        $this->assertNotFalse($updatePosition);
        $this->assertNotFalse($successFlagPosition);
        $this->assertNotFalse($catchPosition);

        $this->assertGreaterThan(
            $validationPosition,
            $fieldsPosition
        );
        $this->assertGreaterThan(
            $fieldsPosition,
            $healthyGuardPosition
        );
        $this->assertGreaterThan(
            $healthyGuardPosition,
            $updatePosition
        );
        $this->assertGreaterThan(
            $updatePosition,
            $successFlagPosition
        );
        $this->assertLessThan(
            $catchPosition,
            $updatePosition
        );

        $this->assertSame(
            1,
            substr_count($source, 'updateLastSuccessfulCheck();')
        );
    }

    public function test_unhealthy_and_failure_paths_preserve_previous_value(): void
    {
        $source = $this->source();

        $this->assertStringNotContainsString(
            "applyVisualState('unavailable');\n"
            . '            updateLastSuccessfulCheck();',
            $source
        );
        $this->assertStringNotContainsString(
            "payload.healthy ? 'healthy' : 'unhealthy'\n"
            . '                );\n'
            . '                updateLastSuccessfulCheck();',
            $source
        );
        $this->assertStringNotContainsString(
            "status.textContent = 'Loading health status...';\n"
            . '            updateLastSuccessfulCheck();',
            $source
        );
    }

    public function test_ignored_concurrent_request_preserves_previous_value(): void
    {
        $source = $this->source();

        $guardPosition = strpos($source, 'if (requestInFlight)');
        $updatePosition = strpos(
            $source,
            'updateLastSuccessfulCheck();'
        );

        $this->assertNotFalse($guardPosition);
        $this->assertNotFalse($updatePosition);
        $this->assertLessThan($updatePosition, $guardPosition);
    }

    public function test_existing_counter_response_duration_timestamp_status_visual_and_validation_remain_intact(): void
    {
        $source = $this->source();

        foreach ([
            'retention-audit-metrics-health-consecutive-failures',
            'recordSuccessfulRequest();',
            'recordFailedRequest();',
            'retention-audit-metrics-health-response-status',
            'formatResponseStatus(response)',
            "responseStatus.textContent = 'Network error';",
            'retention-audit-metrics-health-request-duration',
            "performance['now']()",
            'formatRequestDuration(',
            'retention-audit-metrics-health-updated-at',
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

    public function test_last_successful_check_does_not_use_server_or_persistent_data(): void
    {
        $source = $this->source();

        foreach ([
            'payload.last_successful',
            'payload.successful_at',
            'response.headers',
            'Server-Timing',
            'Date.now(',
            'localStorage',
            'sessionStorage',
            'indexedDB',
            'document.cookie',
            'error.message',
            'correlation_id',
            'user_id',
            'session_id',
            'DB::',
            'Cache::',
            'Log::',
            'Event::',
            'setInterval(',
            'setTimeout(',
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
