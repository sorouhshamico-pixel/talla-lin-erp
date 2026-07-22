<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase97CRetentionExecutionHistoryExportSummaryCacheDiagnosticsAdministrationFinalizationTest
    extends TestCase
{
    public function test_finalization_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-97c-retention-execution-history-export-summary-cache-diagnostics-administration-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-97c-retention-execution-history-export-summary-cache-diagnostics-administration-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 97C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame(
            '905123572094b99fb48a01718389e2fa52764f47',
            $document['baseline']['commit']
        );
        $this->assertSame(1968, $document['baseline']['tests']);
        $this->assertSame(18016, $document['baseline']['assertions']);
    }

    public function test_phase_is_documentation_and_tests_only(): void
    {
        $scope = $this->document()['scope'];

        foreach ([
            'runtime_changes_expected',
            'database_changes_expected',
            'migration_changes_expected',
            'model_changes_expected',
            'service_changes_expected',
            'controller_changes_expected',
            'route_changes_expected',
            'view_changes_expected',
        ] as $key) {
            $this->assertFalse($scope[$key], $key);
        }

        $this->assertTrue($scope['documentation_and_tests_only']);
    }

    public function test_request_behavior_and_display_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'exportSummaryCacheDiagnostics',
            $locked['view_variable']
        );

        foreach ($locked['request_behavior'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ([
            'placement_after_export_summary',
            'read_only',
            'fallback_warning_present',
            'default_generation_information_present',
            'cache_generation_health_present',
            'availability_labels_present',
            'generation_labels_present',
            'observability_labels_present',
            'technical_prefixes_visible',
        ] as $key) {
            $this->assertTrue($locked['display'][$key], $key);
        }

        foreach ([
            'actions_present',
            'raw_generation_token_visible',
            'raw_cache_key_visible',
        ] as $key) {
            $this->assertFalse($locked['display'][$key], $key);
        }
    }

    public function test_security_performance_and_scope_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame(
            'manage_saved_view_share_activity_retention',
            $locked['security']['existing_permission_reused']
        );

        foreach ([
            'new_permission_added',
            'new_policy_added',
            'diagnostics_exposed_to_json_status',
            'diagnostics_exposed_to_exports',
            'sensitive_values_rendered',
        ] as $key) {
            $this->assertFalse($locked['security'][$key], $key);
        }

        $this->assertSame(
            1,
            $locked['performance']['maximum_additional_html_cache_reads']
        );
        $this->assertSame(
            0,
            $locked['performance']
                ['maximum_additional_html_database_queries']
        );
        $this->assertSame(
            0,
            $locked['performance']['maximum_additional_model_hydration']
        );
        $this->assertSame(
            0,
            $locked['performance']['json_additional_cache_reads']
        );
        $this->assertSame(
            0,
            $locked['performance']['json_additional_database_queries']
        );

        $this->assertTrue(
            $locked['implementation_scope']['controller_modified']
        );
        $this->assertTrue(
            $locked['implementation_scope']['view_modified']
        );
        $this->assertTrue(
            $locked['implementation_scope']['phase_97b_test_added']
        );

        foreach ([
            'service_changed',
            'route_changed',
            'database_changed',
            'migration_changed',
            'model_changed',
        ] as $key) {
            $this->assertFalse(
                $locked['implementation_scope'][$key],
                $key
            );
        }
    }

    public function test_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

        foreach ($locked['compatibility'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse(
            $document['workflow']['post_commit_full_suite']
        );
        $this->assertTrue(
            $document['workflow']['successful_phase_pushed_immediately']
        );
        $this->assertSame(
            'Phase 98A',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-97c-retention-execution-history-export-summary-cache-diagnostics-administration-finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
