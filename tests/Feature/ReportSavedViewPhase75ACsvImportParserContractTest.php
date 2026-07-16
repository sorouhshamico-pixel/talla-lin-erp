<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase75ACsvImportParserContractTest extends TestCase
{
    public function test_phase_75a_contract_files_exist(): void
    {
        $this->assertFileExists(
            base_path('docs/phase-75a-saved-view-csv-import-parser-contract.json')
        );
        $this->assertFileExists(
            base_path('docs/phase-75a-saved-view-csv-import-parser-contract.md')
        );
    }

    public function test_phase_75a_is_audit_contract_without_implementation_changes(): void
    {
        $contract = $this->contract();

        $this->assertSame('Phase 75A', $contract['phase']);
        $this->assertSame('Saved View CSV Import Parser Contract', $contract['title']);
        $this->assertSame('Phase 74C clean', $contract['baseline']['phase']);
        $this->assertSame('ce63519', $contract['baseline']['commit']);
        $this->assertSame(
            '1483 passed / 13297 assertions',
            $contract['baseline']['previous_tests']
        );
        $this->assertSame('audit_contract', $contract['scope']['type']);
        $this->assertFalse($contract['scope']['implementation_changes_expected']);

        foreach ([
            'app/Http/Controllers/ReportSavedViewController.php',
            'app/Support/Reports/ReportSavedViewImportExportVersionRegistry.php',
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

    public function test_current_csv_parser_logic_is_inline_and_dedicated_parser_is_absent(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/ReportSavedViewController.php')
        );

        foreach ([
            'private function previewSavedViewImport(string $path): array',
            "fopen(\$path, 'r')",
            'fgetcsv($handle)',
            'str_replace("\\xEF\\xBB\\xBF", \'\', (string) $header)',
            'ReportSavedViewImportExportVersionRegistry::formatVersionColumn()',
            'ReportSavedViewImportExportVersionRegistry::legacyRequiredColumns()',
            'ReportSavedViewImportExportVersionRegistry::requiredColumns(',
            'ReportSavedViewRegistry::has($reportKey)',
            'ReportSavedViewRegistry::find($reportKey)',
            'private function decodeImportFiltersPayload(string $filtersPayload, array &$errors): array',
            'private function cleanImportedFilters(array $filters): array',
            'private function isEmptyCsvRow(array $row): bool',
        ] as $marker) {
            $this->assertStringContainsString($marker, $controller);
        }

        $this->assertFileDoesNotExist(
            app_path('Support/Reports/ReportSavedViewCsvImportParser.php')
        );
        $this->assertStringNotContainsString(
            'use App\\Support\\Reports\\ReportSavedViewCsvImportParser;',
            $controller
        );
    }

    public function test_parser_identity_dependencies_and_access_boundaries_are_locked(): void
    {
        $parser = $this->contract()['parser_contract'];

        $this->assertSame(
            'App\\Support\\Reports\\ReportSavedViewCsvImportParser',
            $parser['class']
        );
        $this->assertSame(
            'app/Support/Reports/ReportSavedViewCsvImportParser.php',
            $parser['file']
        );
        $this->assertSame('final_stateless_csv_parser', $parser['type']);
        $this->assertSame([], $parser['constructor_dependencies']);
        $this->assertSame([
            'App\\Support\\Reports\\ReportSavedViewImportExportVersionRegistry',
            'App\\Support\\Reports\\ReportSavedViewRegistry',
        ], $parser['allowed_collaborators']);
        $this->assertSame(
            ['read_only_csv_filesystem_access'],
            $parser['allowed_runtime_access']
        );

        foreach ([
            'database',
            'eloquent_models',
            'request',
            'response',
            'session',
            'authentication',
            'redirects',
            'views',
            'routes',
        ] as $forbiddenAccess) {
            $this->assertContains($forbiddenAccess, $parser['must_not_access']);
        }
    }

    public function test_parser_public_api_and_result_shapes_are_explicit(): void
    {
        $contract = $this->contract();
        $api = $contract['parser_contract']['public_api']['parse'];

        $this->assertSame(
            'public function parse(string $path): array',
            $api['signature']
        );
        $this->assertSame([
            'headers' => 'array<int, string>',
            'header_errors' => 'array<int, string>',
            'rows' => 'array<int, array<string, mixed>>',
            'total_rows' => 'int',
            'valid_rows' => 'int',
            'invalid_rows' => 'int',
        ], $api['return_shape']);

        $this->assertSame([
            'row_number',
            'format_version',
            'name',
            'report_label',
            'report_key',
            'is_default',
            'filter_count',
            'filters_summary',
            'filters_payload',
            'filters',
            'status',
            'errors',
        ], $contract['parse_result_contract']['row_shape']);

        $this->assertSame(
            ['valid', 'invalid'],
            $contract['parse_result_contract']['status_values']
        );
        $this->assertSame(
            'explicit string value or null for legacy rows',
            $contract['parse_result_contract']['format_version_policy']
        );
    }

    public function test_file_header_and_version_resolution_contract_is_locked(): void
    {
        $header = $this->contract()['file_and_header_contract'];

        $this->assertSame(
            ['تعذر قراءة ملف CSV.'],
            $header['unreadable_file_result']['header_errors']
        );
        $this->assertSame(
            ['ملف CSV فارغ أو غير صالح.'],
            $header['empty_or_invalid_file_result']['header_errors']
        );
        $this->assertSame(
            'remove UTF-8 BOM from header cells before comparison',
            $header['bom_policy']
        );
        $this->assertSame(
            'presence of the registry format-version column only',
            $header['format_mode_detection']
        );
        $this->assertStringContainsString(
            'legacyRequiredColumns',
            $header['legacy_required_columns_source']
        );
        $this->assertStringContainsString(
            'requiredColumns',
            $header['explicit_required_columns_source']
        );
        $this->assertSame(
            'الأعمدة المطلوبة غير موجودة: ',
            $header['missing_columns_error_prefix']
        );
        $this->assertTrue($header['missing_columns_blocks_row_parsing']);
    }

    public function test_row_validation_and_exact_messages_are_locked(): void
    {
        $validation = $this->contract()['row_validation_contract'];

        $this->assertTrue($validation['empty_rows_skipped']);
        $this->assertSame('اسم العرض مطلوب.', $validation['name']['required_error']);
        $this->assertSame(120, $validation['name']['maximum_length']);
        $this->assertSame(
            'اسم العرض يتجاوز 120 حرفًا.',
            $validation['name']['maximum_length_error']
        );
        $this->assertSame(
            'مفتاح التقرير مطلوب.',
            $validation['report_key']['required_error']
        );
        $this->assertSame(
            'مفتاح التقرير غير معروف.',
            $validation['report_key']['unknown_error']
        );
        $this->assertSame(
            'قيمة الافتراضي غير صالحة.',
            $validation['is_default']['invalid_error']
        );
        $this->assertSame(
            'عدد الفلاتر يجب أن يكون رقمًا صحيحًا.',
            $validation['filter_count']['invalid_error']
        );
        $this->assertSame(
            'قيمة format_version مطلوبة.',
            $validation['format_version']['empty_error']
        );
        $this->assertSame(
            'إصدار تنسيق ملف الاستيراد غير مدعوم.',
            $validation['format_version']['unsupported_error']
        );
        $this->assertSame(
            'يحتوي الملف على أكثر من إصدار format_version.',
            $validation['format_version']['mixed_versions_header_error']
        );
        $this->assertSame(
            'filters_payload مطلوب في الإصدار 1.',
            $validation['filters_payload']['required_error']
        );
        $this->assertSame(
            'filters_payload يجب أن يكون JSON object صالحًا.',
            $validation['filters_payload']['invalid_object_error']
        );
        $this->assertTrue($validation['filters_payload']['json_arrays_rejected']);
        $this->assertTrue($validation['filters_payload']['filters_summary_never_parsed']);
    }

    public function test_recursive_filter_cleaning_contract_is_locked(): void
    {
        $cleaning = $this->contract()['filters_cleaning_contract'];

        foreach ([
            'empty_or_non_string_keys_removed',
            'null_values_removed',
            'empty_string_values_removed',
            'nested_arrays_cleaned_recursively',
            'empty_nested_arrays_removed',
            'non_empty_scalar_values_preserved',
            'non_empty_nested_values_preserved',
        ] as $key) {
            $this->assertTrue($cleaning[$key], $key);
        }
    }

    public function test_phase_75b_controller_migration_keeps_database_apply_outside_parser(): void
    {
        $migration = $this->contract()['phase_75b_migration_contract'];

        $this->assertSame(
            'use App\\Support\\Reports\\ReportSavedViewCsvImportParser;',
            $migration['controller_import']
        );
        $this->assertStringContainsString(
            'private readonly ReportSavedViewCsvImportParser $csvImportParser',
            $migration['controller_constructor']
        );

        $this->assertSame([
            'previewSavedViewImport',
            'decodeImportFiltersPayload',
            'cleanImportedFilters',
            'isEmptyCsvRow',
        ], $migration['remove_controller_methods']);

        $this->assertSame([
            'previewImport',
            'applyImport',
            'applySavedViewImportRows',
        ], $migration['preserve_controller_methods']);

        $this->assertFalse($migration['routes_changed']);
        $this->assertFalse($migration['views_changed']);
        $this->assertFalse($migration['service_changed']);
        $this->assertFalse($migration['model_changed']);
        $this->assertFalse($migration['database_behavior_changed']);
        $this->assertFalse($migration['behavior_change_allowed']);
    }

    public function test_phase_70_through_74_behavior_remains_locked(): void
    {
        $preserved = $this->contract()['preserved_behavior'];

        foreach ([
            'preview_action_request_validation_preserved',
            'preview_action_base64_payload_preserved',
            'apply_action_base64_validation_preserved',
            'apply_action_temp_file_flow_preserved',
            'apply_action_reparses_before_writes',
            'header_and_row_error_messages_preserved',
            'preview_result_shape_preserved',
            'row_result_shape_preserved',
            'legacy_compatibility_preserved',
            'version_registry_integration_preserved',
            'filters_payload_policy_preserved',
            'filters_summary_human_readable_only_preserved',
            'transaction_boundary_preserved',
            'authenticated_user_scope_preserved',
            'duplicate_skip_without_overwrite_preserved',
            'default_normalization_preserved',
            'export_bulk_selection_pagination_preserved',
        ] as $key) {
            $this->assertTrue($preserved[$key], $key);
        }
    }

    public function test_phase_75b_recommendation_is_parser_extraction_only(): void
    {
        $recommendation = $this->contract()['phase_75b_recommendation'];

        $this->assertSame('Phase 75B', $recommendation['phase']);
        $this->assertSame(
            'Implement Saved View CSV Import Parser',
            $recommendation['title']
        );
        $this->assertSame('medium', $recommendation['risk']);
        $this->assertNotEmpty($recommendation['risk_reason']);
        $this->assertCount(8, $recommendation['implementation_targets']);
    }

    public function test_guardrails_forbid_runtime_changes_in_phase_75a(): void
    {
        $guardrails = $this->contract()['guardrails'];

        foreach ([
            'Do not implement the CSV parser in Phase 75A.',
            'Do not modify controller, registry, routes, services, views, models, or database behavior in Phase 75A.',
            'The future parser must be final, stateless, and deterministic for the same file contents.',
            'The version registry remains the sole source of format-version and schema metadata.',
            'ReportSavedViewRegistry remains the sole source of valid report keys and canonical labels.',
            'The parser output and all exact Arabic validation messages must remain unchanged.',
            'The parser must not parse filters_summary.',
            'Preview and apply must continue to parse through the same parser path.',
            'Database transaction, authenticated-user scope, duplicate skipping, and default normalization remain outside the parser.',
            'No route, view, service, model, migration, or database schema change is allowed in Phase 75B.',
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
            file_get_contents(
                base_path('docs/phase-75a-saved-view-csv-import-parser-contract.json')
            ),
            true
        );

        $this->assertIsArray($contract);

        return $contract;
    }
}
