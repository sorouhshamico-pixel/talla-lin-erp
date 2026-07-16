<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Support\Reports\ReportSavedViewCsvImportParser;
use App\Support\Reports\ReportSavedViewImportExportVersionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class ReportSavedViewPhase75CCsvImportParserFinalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_75c_finalization_files_exist(): void
    {
        $this->assertFileExists(
            base_path(
                'docs/'
                . 'phase-75c-saved-view-csv-import-parser-finalization.json'
            )
        );
        $this->assertFileExists(
            base_path(
                'docs/'
                . 'phase-75c-saved-view-csv-import-parser-finalization.md'
            )
        );
    }

    public function test_phase_75c_is_finalization_without_runtime_changes(): void
    {
        $contract = $this->contract();

        $this->assertSame('Phase 75C', $contract['phase']);
        $this->assertSame(
            'Saved View CSV Import Parser Finalization',
            $contract['title']
        );
        $this->assertSame('Phase 75B clean', $contract['baseline']['phase']);
        $this->assertSame('c09f1d9', $contract['baseline']['commit']);
        $this->assertSame(
            '1504 passed / 13560 assertions',
            $contract['baseline']['previous_tests']
        );
        $this->assertSame('finalization', $contract['scope']['type']);
        $this->assertFalse(
            $contract['scope']['implementation_changes_expected']
        );

        foreach ([
            'app/Support/Reports/ReportSavedViewCsvImportParser.php',
            'app/Http/Controllers/ReportSavedViewController.php',
            'app/Support/Reports/'
                . 'ReportSavedViewImportExportVersionRegistry.php',
            'app/Support/Reports/ReportSavedViewRegistry.php',
            'app/Services/ReportSavedViewService.php',
            'app/Models/ReportSavedView.php',
            'routes/web.php',
            'resources/views/reports/saved-views/index.blade.php',
            'resources/views/reports/saved-views/edit.blade.php',
        ] as $lockedFile) {
            $this->assertContains(
                $lockedFile,
                $contract['scope']['locked_implementation_files']
            );
        }
    }

    public function test_final_parser_identity_and_dependency_boundary_are_locked(): void
    {
        $reflection = new ReflectionClass(
            ReportSavedViewCsvImportParser::class
        );
        $source = file_get_contents($reflection->getFileName());

        $this->assertTrue($reflection->isFinal());
        $this->assertNull($reflection->getConstructor());
        $this->assertTrue($reflection->hasMethod('parse'));
        $this->assertTrue($reflection->getMethod('parse')->isPublic());

        foreach ([
            'ReportSavedViewImportExportVersionRegistry::',
            'ReportSavedViewRegistry::',
            "fopen(\$path, 'r')",
            'fgetcsv($handle)',
        ] as $requiredMarker) {
            $this->assertStringContainsString(
                $requiredMarker,
                $source
            );
        }

        foreach ([
            'Illuminate\\',
            'App\\Models\\',
            'DB::',
            'request(',
            'response(',
            'session(',
            'auth(',
            'redirect(',
            'view(',
            'route(',
        ] as $forbiddenMarker) {
            $this->assertStringNotContainsString(
                $forbiddenMarker,
                $source
            );
        }
    }

    public function test_final_parser_returns_exact_version_one_result_shape(): void
    {
        $result = $this->parseCsv(
            $this->csv([
                ReportSavedViewImportExportVersionRegistry::exportHeader(),
                [
                    '1',
                    'Final Parser Version One',
                    'ignored label',
                    'profit-loss',
                    'yes',
                    '2',
                    'human summary only',
                    '{"payment_status":"paid","nested":{"keep":"yes","drop":""}}',
                    '2026-07-16 14:00:00',
                ],
            ])
        );

        $this->assertSame(
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            $result['headers']
        );
        $this->assertSame([], $result['header_errors']);
        $this->assertSame(1, $result['total_rows']);
        $this->assertSame(1, $result['valid_rows']);
        $this->assertSame(0, $result['invalid_rows']);
        $this->assertCount(1, $result['rows']);

        $row = $result['rows'][0];

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
        ], array_keys($row));
        $this->assertSame(2, $row['row_number']);
        $this->assertSame('1', $row['format_version']);
        $this->assertSame('Final Parser Version One', $row['name']);
        $this->assertSame('profit-loss', $row['report_key']);
        $this->assertSame('نعم', $row['is_default']);
        $this->assertSame(2, $row['filter_count']);
        $this->assertSame('human summary only', $row['filters_summary']);
        $this->assertSame([
            'payment_status' => 'paid',
            'nested' => ['keep' => 'yes'],
        ], $row['filters']);
        $this->assertSame('valid', $row['status']);
        $this->assertSame([], $row['errors']);
    }

    public function test_final_parser_preserves_legacy_modes_and_empty_row_skipping(): void
    {
        $legacyHeader =
            ReportSavedViewImportExportVersionRegistry::legacyRequiredColumns();

        $withoutPayload = $this->parseCsv(
            $this->csv([
                $legacyHeader,
                ['', '', '', '', '', '', ''],
                [
                    'Final Parser Legacy Empty',
                    'تقرير الأرباح والخسائر',
                    'profit-loss',
                    'no',
                    '1',
                    'summary is not parsed',
                    '2026-07-16 14:00:00',
                ],
            ])
        );

        $this->assertSame(1, $withoutPayload['total_rows']);
        $this->assertSame(1, $withoutPayload['valid_rows']);
        $this->assertNull($withoutPayload['rows'][0]['format_version']);
        $this->assertSame([], $withoutPayload['rows'][0]['filters']);

        $withPayloadHeader = $legacyHeader;
        array_splice($withPayloadHeader, 6, 0, ['filters_payload']);

        $withPayload = $this->parseCsv(
            $this->csv([
                $withPayloadHeader,
                [
                    'Final Parser Legacy Payload',
                    'تقرير الأرباح والخسائر',
                    'profit-loss',
                    'no',
                    '1',
                    'ignored summary',
                    '{"payment_status":"unpaid"}',
                    '2026-07-16 14:05:00',
                ],
            ])
        );

        $this->assertSame(1, $withPayload['valid_rows']);
        $this->assertSame([
            'payment_status' => 'unpaid',
        ], $withPayload['rows'][0]['filters']);
    }

    public function test_final_parser_preserves_exact_validation_messages(): void
    {
        $result = $this->parseCsv(
            $this->csv([
                ReportSavedViewImportExportVersionRegistry::exportHeader(),
                [
                    '',
                    '',
                    'Unknown',
                    'not-a-report',
                    'maybe',
                    'abc',
                    'ignored summary',
                    '["paid"]',
                    '2026-07-16 14:00:00',
                ],
            ])
        );

        $this->assertSame(1, $result['invalid_rows']);
        $this->assertSame('invalid', $result['rows'][0]['status']);

        foreach ([
            'قيمة format_version مطلوبة.',
            'اسم العرض مطلوب.',
            'مفتاح التقرير غير معروف.',
            'قيمة الافتراضي غير صالحة.',
            'عدد الفلاتر يجب أن يكون رقمًا صحيحًا.',
            'filters_payload يجب أن يكون JSON object صالحًا.',
        ] as $error) {
            $this->assertContains($error, $result['rows'][0]['errors']);
        }
    }

    public function test_final_parser_preserves_mixed_version_header_error(): void
    {
        $result = $this->parseCsv(
            $this->csv([
                ReportSavedViewImportExportVersionRegistry::exportHeader(),
                [
                    '1',
                    'Final Parser Supported',
                    'تقرير الأرباح والخسائر',
                    'profit-loss',
                    'no',
                    '0',
                    '',
                    '{}',
                    '2026-07-16 14:00:00',
                ],
                [
                    '2',
                    'Final Parser Unsupported',
                    'تقرير أعمار ذمم فواتير المبيعات',
                    'sales-invoice-aging',
                    'no',
                    '0',
                    '',
                    '{}',
                    '2026-07-16 14:05:00',
                ],
            ])
        );

        $this->assertSame([
            'يحتوي الملف على أكثر من إصدار format_version.',
        ], $result['header_errors']);
        $this->assertSame(2, $result['total_rows']);
        $this->assertSame(1, $result['valid_rows']);
        $this->assertSame(1, $result['invalid_rows']);
        $this->assertContains(
            'إصدار تنسيق ملف الاستيراد غير مدعوم.',
            $result['rows'][1]['errors']
        );
    }

    public function test_final_controller_boundary_uses_parser_and_retains_apply_transaction(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/ReportSavedViewController.php')
        );

        foreach ([
            'use App\\Support\\Reports\\ReportSavedViewCsvImportParser;',
            'private readonly ReportSavedViewCsvImportParser $csvImportParser',
            '$this->csvImportParser->parse($csvPath)',
            '$this->csvImportParser->parse($tempPath)',
            'private readonly ReportSavedViewImportApplyService $importApplyService',
            '$this->importApplyService->apply(',
            '$this->importApplyService->apply(',
            '$this->importApplyService->apply(',
        ] as $marker) {
            $this->assertStringContainsString($marker, $controller);
        }

        foreach ([
            'previewSavedViewImport',
            'decodeImportFiltersPayload',
            'cleanImportedFilters',
            'isEmptyCsvRow',
        ] as $removedMethod) {
            $this->assertStringNotContainsString(
                $removedMethod,
                $controller
            );
        }
    }

    public function test_final_apply_reparses_and_blocks_invalid_files_before_writes(): void
    {
        $user = User::factory()->create();

        $csv = $this->csv([
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            [
                '1',
                'Final Parser Valid Row',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '0',
                '',
                '{}',
                '2026-07-16 14:00:00',
            ],
            [
                '2',
                'Final Parser Invalid Row',
                'تقرير أعمار ذمم فواتير المبيعات',
                'sales-invoice-aging',
                'no',
                '0',
                '',
                '{}',
                '2026-07-16 14:05:00',
            ],
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-views.import-apply'), [
                'csv_payload' => base64_encode($csv),
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas(
                'status',
                'لم يتم تطبيق الاستيراد بسبب وجود أخطاء في الملف.'
            );

        $this->assertSame(0, ReportSavedView::query()->count());
    }

    public function test_final_apply_preserves_user_scope_duplicates_defaults_and_filters(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'Existing Final Parser View',
            'filters' => ['payment_status' => 'unpaid'],
            'is_default' => true,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $otherUser->id,
            'report_key' => 'profit-loss',
            'name' => 'Other User Final Parser View',
            'filters' => ['payment_status' => 'overdue'],
            'is_default' => true,
        ]);

        $csv = $this->csv([
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            [
                '1',
                'Existing Final Parser View',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '1',
                'ignored summary',
                '{"payment_status":"paid"}',
                '2026-07-16 14:00:00',
            ],
            [
                '1',
                'Imported Final Parser View',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'yes',
                '1',
                'ignored summary',
                '{"payment_status":"partial"}',
                '2026-07-16 14:05:00',
            ],
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-views.import-apply'), [
                'csv_payload' => base64_encode($csv),
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas(
                'status',
                'تم تطبيق الاستيراد: تم إنشاء 1 عرض محفوظ، وتم تخطي 1 مكرر.'
            );

        $existing = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('name', 'Existing Final Parser View')
            ->firstOrFail();

        $this->assertSame(
            ['payment_status' => 'unpaid'],
            $existing->filters
        );
        $this->assertFalse($existing->is_default);

        $imported = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('name', 'Imported Final Parser View')
            ->firstOrFail();

        $this->assertSame(
            ['payment_status' => 'partial'],
            $imported->filters
        );
        $this->assertTrue($imported->is_default);

        $other = ReportSavedView::query()
            ->where('user_id', $otherUser->id)
            ->where('name', 'Other User Final Parser View')
            ->firstOrFail();

        $this->assertSame(
            ['payment_status' => 'overdue'],
            $other->filters
        );
        $this->assertTrue($other->is_default);
    }

    public function test_phase_75c_contract_locks_behavior_and_recommends_apply_service_contract(): void
    {
        $contract = $this->contract();

        foreach ([
            'preview_is_read_only',
            'preview_and_apply_share_parser',
            'apply_revalidates_before_writes',
            'invalid_header_or_row_blocks_all_writes',
            'legacy_without_payload_imports_empty_filters',
            'legacy_with_payload_preserves_filters',
            'explicit_v1_round_trip_is_lossless',
            'empty_unsupported_and_mixed_versions_rejected',
            'filters_summary_human_readable_only',
            'filters_payload_machine_source_only',
            'duplicate_import_skips_without_overwrite',
            'cross_user_records_unchanged',
            'default_normalization_per_user_report_preserved',
            'export_behavior_preserved',
            'bulk_selection_preserved',
            'pagination_preserved',
            'exact_arabic_validation_messages_preserved',
            'phase_69_through_75_historical_contracts_preserved',
        ] as $key) {
            $this->assertTrue(
                $contract['preserved_behavior'][$key],
                $key
            );
        }

        $this->assertSame(
            'Phase 76A',
            $contract['next_recommendation']['phase']
        );
        $this->assertSame(
            'Saved View Import Apply Service Contract',
            $contract['next_recommendation']['title']
        );
        $this->assertFalse(
            $contract['next_recommendation']
                ['implementation_changes_expected']
        );
        $this->assertNotEmpty($contract['guardrails']);
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
                    . 'phase-75c-saved-view-csv-import-parser-'
                    . 'finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($contract);

        return $contract;
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private function csv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);

        return stream_get_contents($handle);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseCsv(string $csv): array
    {
        $path = tempnam(
            sys_get_temp_dir(),
            'saved-view-parser-finalization-'
        );

        $this->assertNotFalse($path);

        file_put_contents($path, $csv);

        try {
            return (new ReportSavedViewCsvImportParser())->parse($path);
        } finally {
            @unlink($path);
        }
    }
}
