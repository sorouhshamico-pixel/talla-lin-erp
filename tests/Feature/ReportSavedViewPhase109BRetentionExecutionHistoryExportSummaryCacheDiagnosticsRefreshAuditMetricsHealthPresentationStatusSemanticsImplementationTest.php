<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ReportSavedViewPhase109BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationStatusSemanticsImplementationTest
    extends TestCase
{
    private const PARTIAL =
        'resources/views/reports/saved-views/partials/'
        . 'share-activity-retention-audit-metrics-health.blade.php';

    public function test_partial_locks_exact_status_messages(): void
    {
        $source = $this->source();

        foreach ([
            'Loading health status...',
            'Audit metrics pipeline is healthy.',
            'Audit metrics pipeline requires attention.',
            'Audit metrics health status is unavailable.',
        ] as $message) {
            $this->assertStringContainsString($message, $source);
        }

        $this->assertStringContainsString(
            'status.textContent = payload.healthy',
            $source
        );
    }

    public function test_partial_requires_exact_field_types(): void
    {
        $source = $this->source();

        foreach ([
            "const booleanFields = [",
            "'listener_discovered'",
            "'channel_configured'",
            "'channel_path_matches'",
            "'healthy'",
            "typeof payload[key] === 'boolean'",
            'Number.isInteger(value) && value >= 0',
            'payload.channel_retention_days !== null',
            "value === null || typeof value === 'string'",
            'isValidPayload(payload)',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        $this->assertStringContainsString(
            'Array.isArray(payload)',
            $source
        );
        $this->assertStringContainsString(
            'Object.prototype.hasOwnProperty.call(',
            $source
        );
    }

    public function test_partial_locks_human_readable_rendering(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            "return value ? 'Yes' : 'No';",
            $source
        );
        $this->assertStringContainsString(
            "return 'Not available';",
            $source
        );
        $this->assertStringContainsString(
            'return String(value);',
            $source
        );

        foreach ([
            'Boolean(value)',
            'JSON.stringify(',
            'innerHTML',
            'element.textContent = payload',
            'Object.values(payload)',
            'Object.entries(payload)',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source
            );
        }
    }

    public function test_invalid_payload_moves_complete_panel_to_unavailable(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            "throw new Error('Unexpected health payload');",
            $source
        );
        $this->assertStringContainsString(
            'setUnavailable();',
            $source
        );
        $this->assertStringContainsString(
            "element.textContent = 'Unavailable';",
            $source
        );
        $this->assertStringContainsString(
            "status.textContent =\n"
            . "                'Audit metrics health status is unavailable.';",
            $source
        );

        $validationPosition = strpos(
            $source,
            'if (!isValidPayload(payload))'
        );
        $renderPosition = strpos($source, 'setFields(payload);');

        $this->assertNotFalse($validationPosition);
        $this->assertNotFalse($renderPosition);
        $this->assertLessThan(
            $renderPosition,
            $validationPosition
        );
    }

    public function test_extra_keys_are_not_rendered_and_sensitive_values_remain_private(): void
    {
        $source = $this->source();

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
            'exception.message',
            'error.message',
            'response.text(',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $source
            );
        }

        $this->assertStringContainsString(
            'Object.entries(fields).forEach',
            $source
        );
        $this->assertStringNotContainsString(
            'Object.entries(payload).forEach',
            $source
        );
    }

    public function test_request_frequency_and_backend_contracts_remain_unchanged(): void
    {
        $source = $this->source();

        foreach ([
            "method: 'GET'",
            "credentials: 'same-origin'",
            "Accept: 'application/json'",
            'let requestInFlight = false;',
            'if (requestInFlight)',
            "refresh.addEventListener('click', loadHealth);",
            'loadHealth();',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        foreach ([
            'setInterval(',
            'setTimeout(',
            'location.reload(',
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
