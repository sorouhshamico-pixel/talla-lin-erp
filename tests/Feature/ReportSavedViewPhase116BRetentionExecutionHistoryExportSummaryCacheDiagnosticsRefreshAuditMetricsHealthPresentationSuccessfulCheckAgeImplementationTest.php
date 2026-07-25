<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase116BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationSuccessfulCheckAgeImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_successful_check_age_element_has_locked_initial_state(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            'Successful check age:',
            $source
        );
        $this->assertStringContainsString(
            'id="retention-audit-metrics-health-successful-check-age"',
            $source
        );
        $this->assertStringContainsString(
            'aria-live="off"',
            $source
        );
        $this->assertStringContainsString(
            ">\n            Not available\n        </span>",
            $source
        );
    }

    public function test_age_state_is_page_memory_only_and_reuses_success_date(): void
    {
        $source = $this->source();

        foreach ([
            'let lastSuccessfulCheckAt = null;',
            'lastSuccessfulCheckAt = completedAt;',
            'updateSuccessfulCheckAge(completedAt);',
            'successfulCheckAge.textContent = formatSuccessfulCheckAge(',
            'lastSuccessfulCheckAt,',
            'currentTime',
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

    public function test_age_formatter_locks_ranges_rounding_and_clamping(): void
    {
        $source = $this->source();

        foreach ([
            'successfulCheckAt instanceof Date',
            'currentTime instanceof Date',
            'Number.isNaN(successfulCheckAt.getTime())',
            'Number.isNaN(currentTime.getTime())',
            "return 'Not available';",
            'Math.max(',
            'currentTime.getTime() - successfulCheckAt.getTime()',
            'Math.floor(ageMilliseconds / 60000)',
            'ageMinutes < 1',
            "return 'Less than 1 minute';",
            'ageMinutes < 60',
            'Math.min(ageMinutes, 999)',
            'ageMinutes < 1440',
            'Math.floor(ageMinutes / 60)',
            'Math.min(ageHours, 999)',
            'Math.floor(ageMinutes / 1440)',
            'Math.min(ageDays, 999)',
            '`${Math.min(ageMinutes, 999)} minutes`',
            '`${Math.min(ageHours, 999)} hours`',
            '`${Math.min(ageDays, 999)} days`',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        $this->assertStringNotContainsString(
            'Intl.RelativeTimeFormat',
            $source
        );
    }

    public function test_only_validated_healthy_request_updates_age_once(): void
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
        $lastSuccessPosition = strpos(
            $source,
            'updateLastSuccessfulCheck();'
        );
        $ageUpdatePosition = strpos(
            $source,
            'updateSuccessfulCheckAge(completedAt);'
        );

        $this->assertNotFalse($validationPosition);
        $this->assertNotFalse($fieldsPosition);
        $this->assertNotFalse($healthyGuardPosition);
        $this->assertNotFalse($lastSuccessPosition);
        $this->assertNotFalse($ageUpdatePosition);

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
            $lastSuccessPosition
        );
        $this->assertSame(
            1,
            substr_count(
                $source,
                'updateSuccessfulCheckAge(completedAt);'
            )
        );
    }

    public function test_unhealthy_failure_start_and_ignored_requests_preserve_age(): void
    {
        $source = $this->source();

        $guardPosition = strpos($source, 'if (requestInFlight)');
        $healthyUpdatePosition = strpos(
            $source,
            'updateLastSuccessfulCheck();'
        );

        $this->assertNotFalse($guardPosition);
        $this->assertNotFalse($healthyUpdatePosition);
        $this->assertLessThan(
            $healthyUpdatePosition,
            $guardPosition
        );

        foreach ([
            "status.textContent = 'Loading health status...';\n"
                . '            updateSuccessfulCheckAge(',
            "applyVisualState('unavailable');\n"
                . '            updateSuccessfulCheckAge(',
            "payload.healthy ? 'healthy' : 'unhealthy'\n"
                . '                );\n'
                . '                updateSuccessfulCheckAge(',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source
            );
        }
    }

    public function test_no_timer_polling_or_server_timestamp_is_added(): void
    {
        $source = $this->source();

        foreach ([
            'setInterval(',
            'setTimeout(',
            'requestAnimationFrame(',
            'Intl.RelativeTimeFormat',
            'payload.successful_at',
            'payload.last_successful',
            'response.headers',
            'Server-Timing',
            'Date.now(',
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

    public function test_existing_health_presentation_contracts_remain_intact(): void
    {
        $source = $this->source();

        foreach ([
            'retention-audit-metrics-health-last-successful-check',
            'updateLastSuccessfulCheck();',
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
