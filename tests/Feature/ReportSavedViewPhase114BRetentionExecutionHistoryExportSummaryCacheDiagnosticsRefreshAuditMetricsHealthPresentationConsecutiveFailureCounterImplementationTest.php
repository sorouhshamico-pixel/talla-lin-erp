<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase114BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationConsecutiveFailureCounterImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_counter_element_has_locked_initial_state(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            'Consecutive failures:',
            $source
        );
        $this->assertStringContainsString(
            'id="retention-audit-metrics-health-consecutive-failures"',
            $source
        );
        $this->assertStringContainsString(
            'aria-live="off"',
            $source
        );
        $this->assertStringContainsString(
            ">\n            0\n        </span>",
            $source
        );
    }

    public function test_counter_state_is_client_memory_only_and_clamped(): void
    {
        $source = $this->source();

        foreach ([
            'let consecutiveFailures = 0;',
            'Number.isInteger(consecutiveFailures)',
            'consecutiveFailures >= 0',
            'Math.min(consecutiveFailures, 999)',
            'consecutiveFailures = safeValue;',
            'consecutiveFailureCounter.textContent = String(safeValue);',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        foreach ([
            'localStorage',
            'sessionStorage',
            'indexedDB',
            'document.cookie',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source
            );
        }
    }

    public function test_success_resets_and_failure_increments_once(): void
    {
        $source = $this->source();

        foreach ([
            'const recordSuccessfulRequest = () => {',
            'consecutiveFailures = 0;',
            'const recordFailedRequest = () => {',
            'consecutiveFailures + 1',
            'renderConsecutiveFailures();',
            'let requestSucceeded = false;',
            'requestSucceeded = true;',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        $this->assertSame(
            1,
            substr_count($source, 'recordSuccessfulRequest();')
        );
        $this->assertSame(
            1,
            substr_count($source, 'recordFailedRequest();')
        );
    }

    public function test_counter_updates_once_in_finally_for_each_executed_request(): void
    {
        $source = $this->source();

        $finallyPosition = strpos($source, '} finally {');
        $successBranchPosition = strpos(
            $source,
            'if (requestSucceeded)'
        );
        $successUpdatePosition = strpos(
            $source,
            'recordSuccessfulRequest();'
        );
        $failureUpdatePosition = strpos(
            $source,
            'recordFailedRequest();'
        );
        $durationPosition = strpos(
            $source,
            "const requestCompletedAt = performance['now']();"
        );

        $this->assertNotFalse($finallyPosition);
        $this->assertNotFalse($successBranchPosition);
        $this->assertNotFalse($successUpdatePosition);
        $this->assertNotFalse($failureUpdatePosition);
        $this->assertNotFalse($durationPosition);

        $this->assertGreaterThan(
            $finallyPosition,
            $successBranchPosition
        );
        $this->assertGreaterThan(
            $successBranchPosition,
            $successUpdatePosition
        );
        $this->assertGreaterThan(
            $successBranchPosition,
            $failureUpdatePosition
        );
        $this->assertGreaterThan(
            $failureUpdatePosition,
            $durationPosition
        );
    }

    public function test_only_validated_payload_marks_request_successful(): void
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
        $this->assertNotFalse($successFlagPosition);
        $this->assertNotFalse($catchPosition);

        $this->assertGreaterThan(
            $validationPosition,
            $fieldsPosition
        );
        $this->assertGreaterThan(
            $fieldsPosition,
            $successFlagPosition
        );
        $this->assertLessThan(
            $catchPosition,
            $successFlagPosition
        );
    }

    public function test_request_start_and_ignored_concurrent_request_do_not_change_counter(): void
    {
        $source = $this->source();

        $guardPosition = strpos($source, 'if (requestInFlight)');
        $requestStatePosition = strpos(
            $source,
            'let requestSucceeded = false;'
        );

        $this->assertNotFalse($guardPosition);
        $this->assertNotFalse($requestStatePosition);
        $this->assertLessThan(
            $requestStatePosition,
            $guardPosition
        );

        $this->assertStringNotContainsString(
            "status.textContent = 'Loading health status...';\n"
            . '            consecutiveFailures',
            $source
        );
    }

    public function test_existing_response_duration_timestamp_status_visual_and_validation_remain_intact(): void
    {
        $source = $this->source();

        foreach ([
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

    public function test_counter_does_not_render_failure_reason_or_sensitive_data(): void
    {
        $source = $this->source();

        foreach ([
            'consecutiveFailureCounter.textContent = error',
            'consecutiveFailureCounter.textContent = response',
            'error.message',
            'exception.message',
            'response.text(',
            'response.headers',
            'response.url',
            'payload.failure',
            'payload.error',
            'correlation_id',
            'user_id',
            'session_id',
            'ip_address',
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
