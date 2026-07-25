<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase117BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationSuccessfulCheckFreshnessStateImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_freshness_element_has_locked_initial_state(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            'Successful check freshness:',
            $source
        );
        $this->assertStringContainsString(
            'id="retention-audit-metrics-health-successful-check-freshness"',
            $source
        );
        $this->assertStringContainsString(
            'data-freshness-state="unavailable"',
            $source
        );
        $this->assertStringContainsString(
            'aria-live="off"',
            $source
        );
        $this->assertStringContainsString(
            ">\n            Unavailable\n        </span>",
            $source
        );
    }

    public function test_freshness_formatter_locks_allowed_states_and_threshold(): void
    {
        $source = $this->source();

        foreach ([
            'const formatSuccessfulCheckFreshness = (',
            'successfulCheckAt instanceof Date',
            'currentTime instanceof Date',
            'Number.isNaN(successfulCheckAt.getTime())',
            'Number.isNaN(currentTime.getTime())',
            "state: 'unavailable'",
            "text: 'Unavailable'",
            'Math.floor(',
            'Math.max(',
            'currentTime.getTime() - successfulCheckAt.getTime()',
            'ageMinutes <= 14',
            "state: 'fresh'",
            "text: 'Fresh'",
            "state: 'stale'",
            "text: 'Stale'",
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_renderer_updates_text_and_data_attribute(): void
    {
        $source = $this->source();

        foreach ([
            'const updateSuccessfulCheckFreshness = (currentTime) => {',
            'const freshness = formatSuccessfulCheckFreshness(',
            'lastSuccessfulCheckAt,',
            'currentTime',
            'successfulCheckFreshness.dataset.freshnessState =',
            'freshness.state;',
            'successfulCheckFreshness.textContent = freshness.text;',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_only_validated_healthy_request_updates_freshness_once(): void
    {
        $source = $this->source();

        $validationPosition = strpos(
            $source,
            'if (!isValidPayload(payload))'
        );
        $healthyGuardPosition = strpos(
            $source,
            'if (payload.healthy)'
        );
        $lastSuccessPosition = strpos(
            $source,
            'updateLastSuccessfulCheck();'
        );
        $freshnessUpdatePosition = strpos(
            $source,
            'updateSuccessfulCheckFreshness(completedAt);'
        );

        $this->assertNotFalse($validationPosition);
        $this->assertNotFalse($healthyGuardPosition);
        $this->assertNotFalse($lastSuccessPosition);
        $this->assertNotFalse($freshnessUpdatePosition);

        $this->assertGreaterThan(
            $validationPosition,
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
                'updateSuccessfulCheckFreshness(completedAt);'
            )
        );
    }

    public function test_non_healthy_paths_preserve_previous_freshness(): void
    {
        $source = $this->source();

        foreach ([
            "status.textContent = 'Loading health status...';\n"
                . '            updateSuccessfulCheckFreshness(',
            "applyVisualState('unavailable');\n"
                . '            updateSuccessfulCheckFreshness(',
            "payload.healthy ? 'healthy' : 'unhealthy'\n"
                . '                );\n'
                . '                updateSuccessfulCheckFreshness(',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source
            );
        }
    }

    public function test_no_timer_polling_or_rendered_age_parsing_is_added(): void
    {
        $source = $this->source();

        foreach ([
            'setInterval(',
            'setTimeout(',
            'requestAnimationFrame(',
            'successfulCheckAge.textContent',
            'parseInt(successfulCheckAge',
            'parseFloat(successfulCheckAge',
            'payload.freshness',
            'payload.successful_at',
            'response.headers',
            'Server-Timing',
            'location.reload(',
            'DB::',
            'Cache::',
            'Log::',
            'Event::',
        ] as $forbidden) {
            if ($forbidden === 'successfulCheckAge.textContent') {
                continue;
            }

            $this->assertStringNotContainsString(
                $forbidden,
                $source
            );
        }

        $this->assertStringNotContainsString(
            'parseInt(successfulCheckAge.textContent',
            $source
        );
        $this->assertStringNotContainsString(
            'parseFloat(successfulCheckAge.textContent',
            $source
        );
    }

    public function test_existing_health_presentation_contracts_remain_intact(): void
    {
        $source = $this->source();

        foreach ([
            'retention-audit-metrics-health-successful-check-age',
            'updateSuccessfulCheckAge(completedAt);',
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
