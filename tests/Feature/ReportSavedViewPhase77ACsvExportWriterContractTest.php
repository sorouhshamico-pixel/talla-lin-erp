<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Support\Reports\ReportSavedViewImportExportVersionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase77ACsvExportWriterContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_77a_contract_files_exist(): void
    {
        $this->assertFileExists(
            base_path(
                'docs/'
                . 'phase-77a-saved-view-csv-export-writer-contract.json'
            )
        );
        $this->assertFileExists(
            base_path(
                'docs/'
                . 'phase-77a-saved-view-csv-export-writer-contract.md'
            )
        );
    }

    public function test_phase_77a_is_audit_contract_without_runtime_changes(): void
    {
        $contract = $this->contract();

        $this->assertSame('Phase 77A', $contract['phase']);
        $this->assertSame(
            'Saved View CSV Export Writer Contract',
            $contract['title']
        );
        $this->assertSame('Phase 76C clean', $contract['baseline']['phase']);
        $this->assertSame('05bee93', $contract['baseline']['commit']);
        $this->assertSame(
            '1546 passed / 14030 assertions',
            $contract['baseline']['previous_tests']
        );
        $this->assertSame('audit_contract', $contract['scope']['type']);
        $this->assertFalse(
            $contract['scope']['implementation_changes_expected']
        );

        foreach ([
            'app/Http/Controllers/ReportSavedViewController.php',
            'app/Support/Reports/ReportSavedViewCsvExportWriter.php',
            'app/Support/Reports/ReportSavedViewCsvImportParser.php',
            'app/Support/Reports/'
                . 'ReportSavedViewImportExportVersionRegistry.php',
            'app/Support/Reports/ReportSavedViewRegistry.php',
            'app/Services/ReportSavedViewImportApplyService.php',
            'app/Services/ReportSavedViewService.php',
            'app/Models/ReportSavedView.php',
            'routes/web.php',
            'resources/views/reports/saved-views/index.blade.php',
            'resources/views/reports/saved-views/edit.blade.php',
        ] as $excludedFile) {
            $this->assertContains(
                $excludedFile,
                $contract['scope']['excluded_implementation_files']
            );
        }
    }

    public function test_phase_77a_historical_contract_records_inline_writer_baseline(): void
    {
        $currentState = $this->contract()['current_state'];

        foreach ([
            'export_request_validation_in_controller',
            'user_scoped_query_in_service',
            'report_and_filter_formatting_in_controller',
            'filename_in_controller',
            'streamed_response_in_controller',
            'php_output_open_in_controller_closure',
            'utf8_bom_write_in_controller_closure',
            'registry_header_write_in_controller_closure',
            'current_version_write_in_controller_closure',
            'filters_summary_build_in_controller_closure',
            'filters_payload_json_build_in_controller_closure',
            'csv_row_serialization_in_controller_closure',
            'stream_close_in_controller_closure',
            'dedicated_export_writer_absent',
        ] as $key) {
            $this->assertTrue($currentState[$key], $key);
        }
    }

    public function test_writer_identity_api_and_boundaries_are_locked(): void
    {
        $writer = $this->contract()['writer_contract'];

        $this->assertSame(
            'App\\Support\\Reports\\ReportSavedViewCsvExportWriter',
            $writer['class']
        );
        $this->assertSame(
            'app/Support/Reports/ReportSavedViewCsvExportWriter.php',
            $writer['file']
        );
        $this->assertSame(
            'final_stateless_output_writer',
            $writer['type']
        );
        $this->assertSame([], $writer['constructor_dependencies']);
        $this->assertSame(
            'public function write(iterable $formattedSavedViews): void',
            $writer['public_api']['write']['signature']
        );
        $this->assertSame(
            'write_only_php_output_stream',
            $writer['allowed_runtime_access'][0]
        );

        foreach ([
            'database',
            'eloquent_models',
            'user_model',
            'request',
            'response',
            'session',
            'authentication',
            'redirects',
            'views',
            'routes',
            'saved_view_query_service',
            'csv_import_parser',
            'import_apply_service',
        ] as $forbiddenAccess) {
            $this->assertContains(
                $forbiddenAccess,
                $writer['must_not_access']
            );
        }
    }

    public function test_formatted_input_contract_separates_display_and_machine_data(): void
    {
        $input = $this->contract()['formatted_input_contract'];

        $this->assertSame(
            'ReportSavedViewController::formatSavedView',
            $input['producer']
        );
        $this->assertSame([
            'name',
            'report_label',
            'report_key',
            'is_default',
            'filters',
            'updated_at',
        ], array_keys($input['row_shape']));
        $this->assertSame([
            'key',
            'label',
            'value',
            'display_value',
        ], array_keys($input['filter_item_shape']));
        $this->assertTrue($input['writer_must_not_resolve_labels']);
        $this->assertTrue($input['writer_must_not_query_entities']);
        $this->assertTrue(
            $input['writer_must_not_reinterpret_filter_values']
        );
    }

    public function test_stream_and_row_contracts_are_exact(): void
    {
        $contract = $this->contract();
        $stream = $contract['stream_contract'];
        $row = $contract['row_contract'];

        $this->assertSame('php://output', $stream['stream_uri']);
        $this->assertSame('w', $stream['mode']);
        $this->assertSame('EF BB BF', $stream['utf8_bom_hex']);
        $this->assertSame(
            'ReportSavedViewImportExportVersionRegistry::exportHeader()',
            $stream['header_source']
        );
        $this->assertTrue($stream['header_written_once']);
        $this->assertTrue($stream['header_written_for_empty_result_set']);
        $this->assertTrue($stream['stream_closed_after_write']);

        $this->assertSame(
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            $row['column_order']
        );
        $this->assertSame(
            'ReportSavedViewImportExportVersionRegistry::currentVersion()',
            $row['format_version_source']
        );
        $this->assertSame([
            'true' => 'yes',
            'false' => 'no',
        ], $row['is_default_values']);
        $this->assertTrue($row['input_order_preserved']);
        $this->assertTrue($row['writer_must_not_sort']);
        $this->assertTrue($row['one_csv_row_per_formatted_saved_view']);
    }

    public function test_filter_summary_and_payload_contracts_are_exact(): void
    {
        $contract = $this->contract();
        $summary = $contract['filters_summary_contract'];
        $payload = $contract['filters_payload_contract'];

        $this->assertSame('human_readable_only', $summary['type']);
        $this->assertSame('; ', $summary['separator']);
        $this->assertSame(
            'label: display_value',
            $summary['display_only_rule']
        );
        $this->assertSame(
            'label: display_value (raw_value)',
            $summary['display_and_raw_rule']
        );
        $this->assertSame('', $summary['empty_filters_value']);
        $this->assertTrue($summary['never_used_as_machine_source']);

        $this->assertSame('machine_readable_only', $payload['type']);
        $this->assertSame(
            'formatted filter key and original value pairs',
            $payload['source']
        );
        $this->assertSame('object', $payload['top_level_json_type']);
        $this->assertSame([
            'JSON_UNESCAPED_UNICODE',
            'JSON_UNESCAPED_SLASHES',
        ], $payload['json_flags']);
        $this->assertSame('{}', $payload['empty_filters_value']);
        $this->assertSame(
            '{}',
            $payload['json_encode_failure_fallback']
        );
        $this->assertTrue($payload['preserve_scalar_types']);
        $this->assertTrue($payload['preserve_nested_values']);
        $this->assertTrue($payload['display_values_excluded']);
        $this->assertTrue($payload['labels_excluded']);
    }

    public function test_current_export_bytes_header_summary_payload_and_order_are_locked(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'Zulu Export Contract',
            'filters' => [],
            'is_default' => false,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'Alpha Export Contract',
            'filters' => ['payment_status' => 'paid'],
            'is_default' => true,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'Beta Export Contract',
            'filters' => ['payment_status' => 'unpaid'],
            'is_default' => false,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $otherUser->id,
            'report_key' => 'profit-loss',
            'name' => 'Other User Export Contract',
            'filters' => ['payment_status' => 'partial'],
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.saved-views.export', [
                'report_key' => 'profit-loss',
            ]))
            ->assertOk()
            ->assertHeader(
                'content-type',
                'text/csv; charset=UTF-8'
            );

        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);

        $rows = $this->parseCsv($csv);

        $this->assertSame(
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            $rows[0]
        );
        $this->assertSame([
            'Alpha Export Contract',
            'Beta Export Contract',
            'Zulu Export Contract',
        ], array_column(array_slice($rows, 1), 1));
        $this->assertSame('1', $rows[1][0]);
        $this->assertSame(
            'تقرير الأرباح والخسائر',
            $rows[1][2]
        );
        $this->assertSame('profit-loss', $rows[1][3]);
        $this->assertSame('yes', $rows[1][4]);
        $this->assertSame('1', $rows[1][5]);
        $this->assertSame(
            'حالة الدفع: مدفوعة بالكامل (paid)',
            $rows[1][6]
        );
        $this->assertSame(
            ['payment_status' => 'paid'],
            json_decode($rows[1][7], true)
        );
        $this->assertNotSame('', $rows[1][8]);
        $this->assertSame('{}', $rows[3][7]);
        $this->assertStringNotContainsString(
            'Other User Export Contract',
            $csv
        );
    }

    public function test_current_empty_export_keeps_bom_and_header_only(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('reports.saved-views.export'))
            ->assertOk();

        $csv = $response->streamedContent();
        $rows = $this->parseCsv($csv);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertCount(1, $rows);
        $this->assertSame(
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            $rows[0]
        );
    }

    public function test_phase_77b_migration_keeps_http_query_and_formatting_in_controller(): void
    {
        $migration =
            $this->contract()['phase_77b_migration_contract'];

        $this->assertSame(
            'use App\\Support\\Reports\\'
                . 'ReportSavedViewCsvExportWriter;',
            $migration['controller_import']
        );
        $this->assertStringContainsString(
            'ReportSavedViewCsvExportWriter',
            $migration['constructor_change']
        );
        $this->assertStringContainsString(
            '$formattedSavedViews = $savedViews->map(',
            $migration['formatted_collection']
        );
        $this->assertSame(
            '$this->csvExportWriter->write($formattedSavedViews)',
            $migration['writer_call']
        );

        foreach ([
            'routes_changed',
            'views_changed',
            'query_service_changed',
            'models_changed',
            'database_schema_changed',
            'import_parser_changed',
            'import_apply_service_changed',
            'runtime_behavior_change_allowed',
        ] as $key) {
            $this->assertFalse($migration[$key], $key);
        }
    }

    public function test_phase_69_through_76_export_and_import_behavior_remains_locked(): void
    {
        $preserved = $this->contract()['preserved_behavior'];

        foreach ([
            'authentication_required',
            'user_scope_preserved',
            'search_filter_preserved',
            'report_key_filter_preserved',
            'invalid_report_key_falls_back_to_search_only',
            'export_not_page_scoped',
            'service_order_preserved',
            'filename_pattern_preserved',
            'content_type_preserved',
            'utf8_bom_preserved',
            'version_registry_header_preserved',
            'current_version_preserved',
            'human_filters_summary_preserved',
            'machine_filters_payload_preserved',
            'empty_export_header_preserved',
            'exact_csv_column_order_preserved',
            'import_round_trip_preserved',
            'preview_import_apply_bulk_selection_pagination_preserved',
            'phase_69_through_76_contracts_preserved',
        ] as $key) {
            $this->assertTrue($preserved[$key], $key);
        }
    }

    public function test_phase_77b_recommendation_is_export_writer_extraction(): void
    {
        $recommendation =
            $this->contract()['phase_77b_recommendation'];

        $this->assertSame('Phase 77B', $recommendation['phase']);
        $this->assertSame(
            'Implement Saved View CSV Export Writer',
            $recommendation['title']
        );
        $this->assertSame('medium', $recommendation['risk']);
        $this->assertNotEmpty($recommendation['risk_reason']);
        $this->assertCount(
            12,
            $recommendation['implementation_targets']
        );
    }

    public function test_guardrails_forbid_runtime_changes_in_phase_77a(): void
    {
        $guardrails = $this->contract()['guardrails'];

        foreach ([
            'Do not implement the writer in Phase 77A.',
            'The future writer must be final and stateless.',
            'The future writer must accept formatted objects, not models or '
                . 'an HTTP request.',
            'The future writer must not query the database or resolve labels.',
            'UTF-8 BOM must remain the first three output bytes.',
            'The registry remains the sole source of header and format version.',
            'filters_summary must remain human-readable and display-only.',
            'filters_payload must remain machine-readable and derived from '
                . 'filter keys and original values.',
            'The controller retains filename, streamed response, and content type.',
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
                base_path(
                    'docs/'
                    . 'phase-77a-saved-view-csv-export-writer-'
                    . 'contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($contract);

        return $contract;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseCsv(string $csv): array
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, substr($csv, 3));
        rewind($handle);

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }
}
