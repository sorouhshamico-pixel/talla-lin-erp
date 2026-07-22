<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase97ARetentionExecutionHistoryExportSummaryCacheDiagnosticsAdministrationContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-97a-retention-execution-history-export-summary-cache-diagnostics-administration-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-97a-retention-execution-history-export-summary-cache-diagnostics-administration-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 97A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            'b664ff68e9425b23621e238d96f6a4c3bce61eb6',
            $document['baseline']['commit']
        );
        $this->assertSame(1958, $document['baseline']['tests']);
        $this->assertSame(17919, $document['baseline']['assertions']);
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

    public function test_request_display_and_presentation_are_locked(): void
    {
        $contract = $this->document()['administration_contract'];

        $this->assertSame(
            'summaryCacheDiagnostics',
            $contract['service_method']
        );
        $this->assertSame(
            'exportSummaryCacheDiagnostics',
            $contract['view_variable']
        );

        foreach ($contract['request_behavior'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertTrue($contract['display']['read_only']);
        $this->assertFalse($contract['display']['actions_present']);
        $this->assertFalse($contract['display']['raw_token_visible']);
        $this->assertFalse($contract['display']['raw_cache_key_visible']);

        $this->assertContains(
            'generation_source',
            $contract['display']['status_fields']
        );
        $this->assertContains(
            'cache_key_prefix',
            $contract['display']['technical_fields']
        );

        $this->assertTrue(
            $contract['presentation']['fallback_warning_required']
        );
    }

    public function test_security_performance_and_scope_are_locked(): void
    {
        $contract = $this->document()['administration_contract'];

        $this->assertSame(
            'manage_saved_view_share_activity_retention',
            $contract['security']['existing_permission_reused']
        );
        $this->assertFalse(
            $contract['security']['new_permission_required']
        );
        $this->assertTrue(
            $contract['security']['diagnostics_not_exposed_to_json_status']
        );

        $this->assertSame(
            1,
            $contract['performance']
                ['maximum_additional_cache_reads_for_html']
        );
        $this->assertSame(
            0,
            $contract['performance']
                ['maximum_additional_database_queries_for_html']
        );
        $this->assertSame(
            0,
            $contract['performance']['json_additional_cache_reads']
        );

        foreach ([
            'export_service_changes_expected',
            'history_service_changes_expected',
            'route_changes_expected',
            'database_changes_expected',
            'migration_changes_expected',
        ] as $key) {
            $this->assertFalse(
                $contract['planned_implementation'][$key],
                $key
            );
        }
    }

    public function test_compatibility_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['administration_contract'];

        foreach ($contract['compatibility'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse(
            $document['workflow']['post_commit_full_suite']
        );
        $this->assertSame(
            'Phase 97B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-97a-retention-execution-history-export-summary-cache-diagnostics-administration-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
