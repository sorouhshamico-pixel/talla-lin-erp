<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase109ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationStatusSemanticsContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-109a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-status-semantics-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-109a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-status-semantics-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(file_get_contents($jsonPath), true);

        $this->assertIsArray($document);
        $this->assertSame('Phase 109A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '432a6ebe321c2caf0765a40ddded970d2aef3f0f',
            $document['baseline']['commit']
        );
        $this->assertSame(2152, $document['baseline']['tests']);
        $this->assertSame(20776, $document['baseline']['assertions']);
    }

    public function test_phase_is_documentation_and_tests_only(): void
    {
        $scope = $this->document()['scope'];

        foreach ($scope as $key => $value) {
            if ($key === 'documentation_and_tests_only') {
                $this->assertTrue($value, $key);

                continue;
            }

            $this->assertFalse($value, $key);
        }
    }

    public function test_overall_and_field_semantics_are_locked(): void
    {
        $contract = $this->document()['status_semantics_contract'];

        $this->assertSame(
            ['loading', 'healthy', 'unhealthy', 'unavailable'],
            array_keys($contract['overall_states'])
        );

        $this->assertSame(
            'payload.healthy === true',
            $contract['overall_states']['healthy']['condition']
        );
        $this->assertSame(
            'payload.healthy === false',
            $contract['overall_states']['unhealthy']['condition']
        );
        $this->assertSame(
            'request_failure_or_invalid_payload',
            $contract['overall_states']['unavailable']['condition']
        );

        $this->assertSame(
            [
                'listener_discovered',
                'listener_count',
                'channel_configured',
                'channel_driver',
                'channel_level',
                'channel_retention_days',
                'channel_path_matches',
                'healthy',
            ],
            array_keys($contract['field_semantics'])
        );

        foreach ([
            'listener_discovered',
            'channel_configured',
            'channel_path_matches',
            'healthy',
        ] as $field) {
            $this->assertSame(
                'boolean',
                $contract['field_semantics'][$field]['type']
            );
            $this->assertSame(
                'Yes',
                $contract['field_semantics'][$field]['true_text']
            );
            $this->assertSame(
                'No',
                $contract['field_semantics'][$field]['false_text']
            );
        }
    }

    public function test_validation_presentation_accessibility_and_privacy_are_locked(): void
    {
        $contract = $this->document()['status_semantics_contract'];

        foreach ([
            'payload_must_be_object',
            'arrays_rejected',
            'all_eight_keys_required',
            'extra_keys_ignored',
            'boolean_fields_require_boolean',
            'listener_count_requires_non_negative_integer',
            'channel_retention_days_allows_null_or_non_negative_integer',
            'channel_driver_allows_null_or_string',
            'channel_level_allows_null_or_string',
            'partial_rendering_for_invalid_payload_forbidden',
        ] as $key) {
            $this->assertTrue($contract['validation'][$key], $key);
        }

        $this->assertSame(
            'unavailable',
            $contract['validation']['invalid_type_behavior']
        );

        foreach ([
            'empty_string_treated_as_unavailable',
            'zero_rendered_as_zero',
            'false_rendered_as_no',
            'true_rendered_as_yes',
            'null_rendered_as_not_available',
            'raw_boolean_rendering_forbidden',
            'raw_null_rendering_forbidden',
            'raw_object_rendering_forbidden',
            'raw_array_rendering_forbidden',
            'status_depends_on_healthy_field_only',
        ] as $key) {
            $this->assertTrue(
                $contract['presentation_rules'][$key],
                $key
            );
        }

        foreach ($contract['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }
    }

    public function test_compatibility_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['status_semantics_contract'];

        foreach ($contract['compatibility'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        $implementation = $contract['planned_implementation'];

        $this->assertSame(2, $implementation['maximum_modified_files']);
        $this->assertFalse($implementation['parent_view_modified']);

        foreach ([
            'endpoint_controller_modified',
            'route_modified',
            'health_class_modified',
            'listener_modified',
            'event_modified',
            'middleware_modified',
            'logging_configuration_modified',
            'layout_modified',
            'provider_modified',
            'database_changes_expected',
            'migration_changes_expected',
            'model_changes_expected',
        ] as $key) {
            $this->assertFalse($implementation[$key], $key);
        }

        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse(
            $document['workflow']['post_commit_full_suite']
        );
        $this->assertSame(
            'Phase 109B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-109a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-status-semantics-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
