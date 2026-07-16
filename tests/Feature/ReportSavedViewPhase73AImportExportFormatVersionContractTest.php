<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase73AImportExportFormatVersionContractTest extends TestCase
{
    public function test_phase_73a_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-73a-saved-view-import-export-format-version-contract.json'));
        $this->assertFileExists(base_path('docs/phase-73a-saved-view-import-export-format-version-contract.md'));
    }

    public function test_phase_73a_is_an_audit_contract_without_implementation_changes(): void
    {
        $contract = $this->contract();

        $this->assertSame('Phase 73A', $contract['phase']);
        $this->assertSame('Saved View Import Export Format Version Contract', $contract['title']);
        $this->assertSame('Phase 72C clean', $contract['baseline']['phase']);
        $this->assertSame('5941826', $contract['baseline']['commit']);
        $this->assertSame('1423 passed / 12717 assertions', $contract['baseline']['previous_tests']);
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

    public function test_current_saved_view_csv_is_unversioned_but_payload_capable(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));

        foreach ([
            "'filters_summary'",
            "'filters_payload'",
            '$filtersPayload = json_encode((object) ($savedView->filters ?? []',
            "array_key_exists('filters_payload', \$indexes)",
            'decodeImportFiltersPayload($data[\'filters_payload\'], $errors)',
        ] as $currentMarker) {
            $this->assertStringContainsString($currentMarker, $controller);
        }

        $this->assertStringNotContainsString("'format_version'", $controller);
        $this->assertStringNotContainsString('"format_version"', $controller);
        $this->assertStringNotContainsString('$formatVersion', $controller);
    }

    public function test_contract_defines_one_explicit_version_column_without_inference(): void
    {
        $version = $this->contract()['version_contract'];

        $this->assertSame('format_version', $version['column']);
        $this->assertSame('first', $version['column_position']);
        $this->assertSame('1', $version['first_supported_version']);
        $this->assertSame('1', $version['export_version']);
        $this->assertSame(['1'], $version['supported_versions']);
        $this->assertSame('legacy_unversioned', $version['absent_column_mode']);
        $this->assertSame(
            'use_format_version_header_and_exact_row_value_only',
            $version['version_detection_policy']
        );
        $this->assertTrue($version['do_not_infer_version_from_other_columns']);
        $this->assertSame('reject_before_writes', $version['unknown_version_policy']);
        $this->assertSame('reject_before_writes', $version['invalid_version_policy']);
        $this->assertSame('reject_before_writes', $version['mixed_versions_in_one_file_policy']);
    }

    public function test_explicit_version_one_schema_requires_filters_payload(): void
    {
        $schema = $this->contract()['explicit_v1_schema'];

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
        ], $schema['required_columns']);

        $this->assertSame('1', $schema['format_version_value']);
        $this->assertTrue($schema['filters_payload_required']);
        $this->assertSame('JSON object', $schema['filters_payload_format']);
        $this->assertSame('human_readable_only_not_parsed', $schema['filters_summary_policy']);
    }

    public function test_compatibility_matrix_preserves_legacy_files_and_locks_explicit_v1(): void
    {
        $matrix = $this->contract()['compatibility_matrix'];

        $this->assertTrue($matrix['legacy_unversioned_without_filters_payload']['accepted']);
        $this->assertSame(
            'empty_filters',
            $matrix['legacy_unversioned_without_filters_payload']['filters_result']
        );

        $this->assertTrue($matrix['legacy_unversioned_with_filters_payload']['accepted']);
        $this->assertSame(
            'validated_payload_filters',
            $matrix['legacy_unversioned_with_filters_payload']['filters_result']
        );

        $this->assertTrue($matrix['explicit_v1_with_filters_payload']['accepted']);
        $this->assertSame(
            'validated_payload_filters',
            $matrix['explicit_v1_with_filters_payload']['filters_result']
        );

        $this->assertFalse($matrix['explicit_v1_without_filters_payload']['accepted']);
        $this->assertSame(
            'missing_required_filters_payload_column',
            $matrix['explicit_v1_without_filters_payload']['reason']
        );
    }

    public function test_unknown_invalid_and_mixed_versions_are_rejected_before_writes(): void
    {
        $matrix = $this->contract()['compatibility_matrix'];

        foreach ([
            'explicit_unknown_version' => 'unsupported_format_version',
            'explicit_invalid_or_empty_version' => 'invalid_format_version',
            'mixed_explicit_versions_in_one_file' => 'mixed_format_versions',
        ] as $case => $reason) {
            $this->assertFalse($matrix[$case]['accepted'], $case);
            $this->assertSame($reason, $matrix[$case]['reason'], $case);
        }
    }

    public function test_phase_72_filters_and_import_safety_behavior_remains_locked(): void
    {
        $preserved = $this->contract()['preserved_behavior'];

        foreach ([
            'filters_summary_never_parsed',
            'filters_payload_only_machine_filters_source',
            'invalid_json_blocks_all_writes',
            'json_list_blocks_all_writes',
            'import_apply_revalidates_before_writes',
            'import_apply_uses_database_transaction',
            'authenticated_user_scope_only',
            'duplicates_skipped_without_overwrite',
            'default_normalization_per_user_and_report',
            'preview_export_bulk_selection_and_pagination_preserved',
        ] as $key) {
            $this->assertTrue($preserved[$key], $key);
        }
    }

    public function test_phase_73b_recommendation_is_implementation_of_the_locked_contract(): void
    {
        $recommendation = $this->contract()['phase_73b_recommendation'];

        $this->assertSame('Phase 73B', $recommendation['phase']);
        $this->assertSame(
            'Implement Saved View Import Export Format Version',
            $recommendation['title']
        );
        $this->assertSame('medium', $recommendation['risk']);
        $this->assertNotEmpty($recommendation['risk_reason']);
        $this->assertCount(8, $recommendation['implementation_targets']);
    }

    public function test_guardrails_forbid_phase_73a_runtime_changes(): void
    {
        $guardrails = $this->contract()['guardrails'];

        foreach ([
            'Do not implement format_version in Phase 73A.',
            'Do not modify CSV export, import preview, or import apply in Phase 73A.',
            'Do not infer format versions from column presence other than the explicit format_version header.',
            'Unknown, invalid, empty, or mixed explicit versions must block all writes.',
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
            file_get_contents(base_path('docs/phase-73a-saved-view-import-export-format-version-contract.json')),
            true
        );

        $this->assertIsArray($contract);

        return $contract;
    }
}
