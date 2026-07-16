<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase74AImportExportVersionRegistryContractTest extends TestCase
{
    public function test_phase_74a_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-74a-saved-view-import-export-version-registry-contract.json'));
        $this->assertFileExists(base_path('docs/phase-74a-saved-view-import-export-version-registry-contract.md'));
    }

    public function test_phase_74a_is_audit_contract_without_implementation_changes(): void
    {
        $contract = $this->contract();

        $this->assertSame('Phase 74A', $contract['phase']);
        $this->assertSame('Saved View Import Export Version Registry Contract', $contract['title']);
        $this->assertSame('Phase 73C clean', $contract['baseline']['phase']);
        $this->assertSame('6a38b8c', $contract['baseline']['commit']);
        $this->assertSame('1454 passed / 12981 assertions', $contract['baseline']['previous_tests']);
        $this->assertSame('audit_contract', $contract['scope']['type']);
        $this->assertFalse($contract['scope']['implementation_changes_expected']);

        foreach ([
            'app/Http/Controllers/ReportSavedViewController.php',
            'routes/web.php',
            'app/Services/ReportSavedViewService.php',
            'resources/views/reports/saved-views/index.blade.php',
            'resources/views/reports/saved-views/edit.blade.php',
            'app/Models/ReportSavedView.php',
            'app/Support/Reports/ReportSavedViewRegistry.php',
        ] as $excludedFile) {
            $this->assertContains($excludedFile, $contract['scope']['excluded_implementation_files']);
        }
    }

    public function test_current_format_metadata_is_inline_and_dedicated_registry_is_absent(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));

        foreach ([
            'private const IMPORT_PREVIEW_REQUIRED_COLUMNS = [',
            "private const IMPORT_EXPORT_FORMAT_VERSION = '1';",
            'private const SUPPORTED_IMPORT_EXPORT_FORMAT_VERSIONS = [',
            'private const IMPORT_PREVIEW_V1_REQUIRED_COLUMNS = [',
        ] as $marker) {
            $this->assertStringContainsString($marker, $controller);
        }

        $this->assertFileDoesNotExist(
            app_path('Support/Reports/ReportSavedViewImportExportVersionRegistry.php')
        );
        $this->assertStringNotContainsString(
            'use App\Support\Reports\ReportSavedViewImportExportVersionRegistry;',
            $controller
        );
    }

    public function test_registry_identity_type_and_dependency_contract_are_locked(): void
    {
        $registry = $this->contract()['registry_contract'];

        $this->assertSame(
            'App\\Support\\Reports\\ReportSavedViewImportExportVersionRegistry',
            $registry['class']
        );
        $this->assertSame(
            'app/Support/Reports/ReportSavedViewImportExportVersionRegistry.php',
            $registry['file']
        );
        $this->assertSame('final_static_immutable_metadata_registry', $registry['type']);
        $this->assertSame([], $registry['dependencies']);

        foreach ([
            'database',
            'request',
            'response',
            'session',
            'filesystem',
            'authentication',
        ] as $forbiddenDependency) {
            $this->assertContains($forbiddenDependency, $registry['must_not_access']);
        }
    }

    public function test_registry_constants_and_public_api_are_explicit(): void
    {
        $registry = $this->contract()['registry_contract'];

        $this->assertSame('format_version', $registry['constants']['FORMAT_VERSION_COLUMN']);
        $this->assertSame('1', $registry['constants']['CURRENT_VERSION']);
        $this->assertSame('legacy_unversioned', $registry['constants']['LEGACY_MODE']);

        foreach ([
            'formatVersionColumn',
            'currentVersion',
            'supportedVersions',
            'supports',
            'legacyRequiredColumns',
            'requiredColumns',
            'exportHeader',
            'requiresFiltersPayload',
        ] as $method) {
            $this->assertArrayHasKey($method, $registry['public_api']);
        }

        $this->assertSame(
            'array<int, string>',
            $registry['public_api']['supportedVersions']['return_type']
        );
        $this->assertSame(
            ['1'],
            $registry['public_api']['supportedVersions']['returns']
        );
        $this->assertSame(
            'empty_array',
            $registry['public_api']['requiredColumns']['unsupported_version_policy']
        );
        $this->assertFalse(
            $registry['public_api']['requiresFiltersPayload']['unsupported_version_returns']
        );
    }

    public function test_registry_data_preserves_legacy_and_version_one_schemas(): void
    {
        $data = $this->contract()['registry_data'];

        $this->assertSame([
            'name',
            'report_label',
            'report_key',
            'is_default',
            'filter_count',
            'filters_summary',
            'updated_at',
        ], $data['legacy_required_columns']);

        $this->assertArrayHasKey('1', $data['versions']);
        $this->assertSame([
            'format_version',
            'name',
            'report_label',
            'report_key',
            'is_default',
            'filter_count',
            'filters_summary',
            'filters_payload',
            'updated_at',
        ], $data['versions']['1']['required_columns']);
        $this->assertTrue($data['versions']['1']['requires_filters_payload']);
        $this->assertSame('JSON object', $data['versions']['1']['filters_payload_format']);
        $this->assertSame(
            'human_readable_only_not_parsed',
            $data['versions']['1']['filters_summary_policy']
        );
    }

    public function test_phase_74b_controller_migration_is_behavior_preserving(): void
    {
        $migration = $this->contract()['phase_74b_migration_contract'];

        $this->assertSame(
            'use App\\Support\\Reports\\ReportSavedViewImportExportVersionRegistry;',
            $migration['controller_import']
        );

        $this->assertSame([
            'IMPORT_PREVIEW_REQUIRED_COLUMNS',
            'IMPORT_EXPORT_FORMAT_VERSION',
            'SUPPORTED_IMPORT_EXPORT_FORMAT_VERSIONS',
            'IMPORT_PREVIEW_V1_REQUIRED_COLUMNS',
        ], $migration['remove_controller_constants']);

        foreach ([
            'export_header',
            'export_version_value',
            'format_version_column',
            'legacy_required_columns',
            'version_required_columns',
            'supported_version_check',
            'filters_payload_requirement',
        ] as $replacement) {
            $this->assertArrayHasKey($replacement, $migration['replace_with_registry_calls']);
        }

        $this->assertFalse($migration['behavior_change_allowed']);
    }

    public function test_phase_73_behavior_remains_locked_during_registry_extraction(): void
    {
        $preserved = $this->contract()['preserved_behavior'];

        foreach ([
            'format_version_first_export_column',
            'new_exports_use_version_one',
            'explicit_v1_requires_non_empty_filters_payload',
            'invalid_or_list_payload_rejected',
            'empty_unsupported_mixed_versions_rejected',
            'legacy_unversioned_without_payload_supported',
            'legacy_unversioned_with_payload_supported',
            'version_not_inferred_from_other_columns',
            'filters_summary_human_readable_only',
            'filters_payload_only_machine_source',
            'import_revalidates_before_writes',
            'transaction_boundary_preserved',
            'authenticated_user_scope_preserved',
            'duplicate_skip_without_overwrite_preserved',
            'default_normalization_preserved',
            'preview_export_bulk_selection_pagination_preserved',
        ] as $key) {
            $this->assertTrue($preserved[$key], $key);
        }
    }

    public function test_phase_74b_recommendation_is_registry_implementation_only(): void
    {
        $recommendation = $this->contract()['phase_74b_recommendation'];

        $this->assertSame('Phase 74B', $recommendation['phase']);
        $this->assertSame(
            'Implement Saved View Import Export Version Registry',
            $recommendation['title']
        );
        $this->assertSame('medium-low', $recommendation['risk']);
        $this->assertNotEmpty($recommendation['risk_reason']);
        $this->assertCount(7, $recommendation['implementation_targets']);
    }

    public function test_guardrails_forbid_runtime_changes_in_phase_74a(): void
    {
        $guardrails = $this->contract()['guardrails'];

        foreach ([
            'Do not implement the version registry in Phase 74A.',
            'Do not modify controller, routes, services, views, models, or existing registries in Phase 74A.',
            'The future registry must contain immutable deterministic metadata only.',
            'Phase 74B must be behavior-preserving metadata extraction only.',
            'format_version must remain the first exported CSV column.',
            'Legacy CSV compatibility must remain unchanged.',
            'filters_summary must remain human-readable only and must never be parsed.',
            'Import apply must remain transaction-protected and authenticated-user scoped.',
            'Duplicates must remain skipped without overwrite.',
        ] as $guardrail) {
            $this->assertContains($guardrail, $guardrails);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function contract(): array
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-74a-saved-view-import-export-version-registry-contract.json')),
            true
        );

        $this->assertIsArray($contract);

        return $contract;
    }
}
