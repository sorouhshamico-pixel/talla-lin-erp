<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase108BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    private const PARENT =
        'resources/views/reports/saved-views/'
        . 'share-activity-retention.blade.php';

    public function test_partial_and_parent_integration_are_present_once(): void
    {
        $partial = file_get_contents(base_path(self::PARTIAL));
        $parent = file_get_contents(base_path(self::PARENT));

        $this->assertIsString($partial);
        $this->assertIsString($parent);

        $this->assertSame(
            1,
            substr_count(
                $parent,
                'share-activity-retention-audit-metrics-health'
            )
        );

        $includePosition = strpos(
            $parent,
            'share-activity-retention-audit-metrics-health'
        );
        $privacyPosition = strpos(
            $parent,
            'Privacy notice: context and updated_at are excluded from exports.'
        );
        $diagnosticsPosition = strpos(
            $parent,
            'retention-summary-cache-diagnostics-heading'
        );

        $this->assertNotFalse($includePosition);
        $this->assertNotFalse($privacyPosition);
        $this->assertNotFalse($diagnosticsPosition);
        $this->assertGreaterThan(
            $diagnosticsPosition,
            $includePosition
        );
        $this->assertLessThan(
            $privacyPosition,
            $includePosition
        );
    }

    public function test_partial_locks_accessible_panel_and_exact_fields(): void
    {
        $source = file_get_contents(base_path(self::PARTIAL));

        $this->assertStringContainsString(
            'id="retention-audit-metrics-health"',
            $source
        );
        $this->assertStringContainsString(
            'role="status"',
            $source
        );
        $this->assertStringContainsString(
            'aria-live="polite"',
            $source
        );
        $this->assertStringContainsString(
            'type="button"',
            $source
        );
        $this->assertStringContainsString(
            '<th scope="col">',
            $source
        );
        $this->assertStringContainsString(
            '<th scope="row">',
            $source
        );

        foreach ([
            'listener_discovered',
            'listener_count',
            'channel_configured',
            'channel_driver',
            'channel_level',
            'channel_retention_days',
            'channel_path_matches',
            'healthy',
        ] as $field) {
            $this->assertStringContainsString(
                $field,
                $source,
                $field
            );
        }

        $this->assertSame(
            8,
            substr_count($source, '<th scope="row">')
        );
    }

    public function test_partial_locks_request_and_state_behavior(): void
    {
        $source = file_get_contents(base_path(self::PARTIAL));

        foreach ([
            "method: 'GET'",
            "credentials: 'same-origin'",
            "Accept: 'application/json'",
            'let requestInFlight = false;',
            'if (requestInFlight)',
            'refresh.disabled = true;',
            'refresh.disabled = false;',
            "refresh.addEventListener('click', loadHealth);",
            'loadHealth();',
            "document.readyState === 'loading'",
            "'DOMContentLoaded'",
            "{ once: true }",
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        foreach ([
            'Loading health status...',
            'Audit metrics pipeline is healthy.',
            'Audit metrics pipeline requires attention.',
            'Audit metrics health status is unavailable.',
        ] as $state) {
            $this->assertStringContainsString($state, $source);
        }

        foreach ([
            'setInterval(',
            'setTimeout(',
            'location.reload(',
            'window.location',
            'console.',
            'innerHTML',
            'eval(',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source
            );
        }
    }

    public function test_partial_uses_health_route_and_does_not_expose_sensitive_data(): void
    {
        $source = file_get_contents(base_path(self::PARTIAL));

        $this->assertStringContainsString(
            "'reports.saved-view-share-activity-retention.'",
            $source
        );
        $this->assertStringContainsString(
            ". 'summary-cache-diagnostics.audit-metrics-health'",
            $source
        );

        foreach ([
            'correlation_id',
            'user_id',
            'ip_address',
            'session_id',
            'request_headers',
            'cookies',
            'retry_after',
            'sampling_bucket',
            'diagnostics_payload',
            'cache_key',
            'exception_details',
            'stack_trace',
            'raw_json',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source
            );
        }
    }

    public function test_partial_compiles_and_parent_view_contract_remains_intact(): void
    {
        $partial = file_get_contents(base_path(self::PARTIAL));
        $parent = file_get_contents(base_path(self::PARENT));

        $compiled = Blade::compileString($partial);

        $this->assertIsString($compiled);
        $this->assertNotSame('', trim($compiled));

        foreach ([
            'Retention execution history export',
            'Current export summary',
            'Summary cache diagnostics',
            'retention-summary-cache-diagnostics-refresh',
            'Privacy notice: context and updated_at are excluded from exports.',
        ] as $existingContent) {
            $this->assertStringContainsString(
                $existingContent,
                $parent
            );
        }

        foreach ([
            '@extends(\'layouts.app\')',
            '@section(\'content\')',
            '@endsection',
        ] as $bladeStructure) {
            $this->assertStringContainsString(
                $bladeStructure,
                $parent
            );
        }
    }

    public function test_implementation_scope_does_not_modify_backend_contracts(): void
    {
        $partial = file_get_contents(base_path(self::PARTIAL));
        $parent = file_get_contents(base_path(self::PARENT));
        $combined = $partial."\n".$parent;

        foreach ([
            'DB::',
            'Cache::',
            'Log::',
            'Event::',
            'dispatch(',
            'event(',
            'fetch('
                . "route('reports.saved-view-share-activity-retention."
                . "summary-cache-diagnostics')",
            'throttle:',
            'audit.saved-view-retention',
            'can:manage_saved_view_share_activity_retention',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $combined
            );
        }
    }
}
