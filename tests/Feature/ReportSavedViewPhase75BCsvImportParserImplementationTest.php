<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Support\Reports\ReportSavedViewCsvImportParser;
use App\Support\Reports\ReportSavedViewImportExportVersionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class ReportSavedViewPhase75BCsvImportParserImplementationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_75b_implementation_files_exist(): void
    {
        $this->assertFileExists(
            app_path('Support/Reports/ReportSavedViewCsvImportParser.php')
        );
        $this->assertFileExists(
            base_path('docs/phase-75b-saved-view-csv-import-parser-implementation.json')
        );
        $this->assertFileExists(
            base_path('docs/phase-75b-saved-view-csv-import-parser-implementation.md')
        );
    }

    public function test_parser_is_final_stateless_and_has_no_forbidden_dependencies(): void
    {
        $reflection = new ReflectionClass(
            ReportSavedViewCsvImportParser::class
        );
        $source = file_get_contents($reflection->getFileName());

        $this->assertTrue($reflection->isFinal());
        $this->assertNull($reflection->getConstructor());

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
    }

    public function test_parser_returns_exact_valid_version_one_result_shape(): void
    {
        $csv = $this->csv([
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            [
                '1',
                'Parser Version One',
                'ignored label',
                'profit-loss',
                'yes',
                '2',
                'human summary only',
                '{"payment_status":"paid","nested":{"keep":"yes","drop":""}}',
                '2026-07-16 13:00:00',
            ],
        ]);

        $result = $this->parseCsv($csv);

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
        $this->assertSame('Parser Version One', $row['name']);
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

    public function test_parser_preserves_legacy_modes_and_skips_empty_rows(): void
    {
        $legacyHeader =
            ReportSavedViewImportExportVersionRegistry::legacyRequiredColumns();

        $withoutPayload = $this->csv([
            $legacyHeader,
            ['', '', '', '', '', '', ''],
            [
                'Parser Legacy Empty',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '1',
                'حالة الدفع: مدفوعة بالكامل (paid)',
                '2026-07-16 13:00:00',
            ],
        ]);

        $withoutPayloadResult = $this->parseCsv($withoutPayload);

        $this->assertSame(1, $withoutPayloadResult['total_rows']);
        $this->assertSame(1, $withoutPayloadResult['valid_rows']);
        $this->assertNull(
            $withoutPayloadResult['rows'][0]['format_version']
        );
        $this->assertSame(
            [],
            $withoutPayloadResult['rows'][0]['filters']
        );

        $withPayloadHeader = $legacyHeader;
        array_splice($withPayloadHeader, 6, 0, ['filters_payload']);

        $withPayload = $this->csv([
            $withPayloadHeader,
            [
                'Parser Legacy Payload',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '1',
                'ignored summary',
                '{"payment_status":"unpaid"}',
                '2026-07-16 13:05:00',
            ],
        ]);

        $withPayloadResult = $this->parseCsv($withPayload);

        $this->assertSame(1, $withPayloadResult['valid_rows']);
        $this->assertSame([
            'payment_status' => 'unpaid',
        ], $withPayloadResult['rows'][0]['filters']);
    }

    public function test_parser_preserves_exact_header_and_row_errors(): void
    {
        $missingHeaderResult = $this->parseCsv(
            $this->csv([
                ['name', 'report_key'],
                ['Parser Missing', 'profit-loss'],
            ])
        );

        $this->assertSame(0, $missingHeaderResult['total_rows']);
        $this->assertCount(
            1,
            $missingHeaderResult['header_errors']
        );
        $this->assertStringStartsWith(
            'الأعمدة المطلوبة غير موجودة: ',
            $missingHeaderResult['header_errors'][0]
        );

        $invalidResult = $this->parseCsv(
            $this->csv([
                ReportSavedViewImportExportVersionRegistry::exportHeader(),
                [
                    '',
                    '',
                    'Unknown',
                    'not-a-report',
                    'maybe',
                    'abc',
                    'ignored',
                    '["paid"]',
                    '2026-07-16 13:00:00',
                ],
            ])
        );

        $this->assertSame(1, $invalidResult['invalid_rows']);
        $this->assertSame('invalid', $invalidResult['rows'][0]['status']);

        foreach ([
            'قيمة format_version مطلوبة.',
            'اسم العرض مطلوب.',
            'مفتاح التقرير غير معروف.',
            'قيمة الافتراضي غير صالحة.',
            'عدد الفلاتر يجب أن يكون رقمًا صحيحًا.',
            'filters_payload يجب أن يكون JSON object صالحًا.',
        ] as $error) {
            $this->assertContains(
                $error,
                $invalidResult['rows'][0]['errors']
            );
        }
    }

    public function test_parser_preserves_unsupported_and_mixed_version_rejection(): void
    {
        $result = $this->parseCsv(
            $this->csv([
                ReportSavedViewImportExportVersionRegistry::exportHeader(),
                [
                    '1',
                    'Parser Supported',
                    'تقرير الأرباح والخسائر',
                    'profit-loss',
                    'no',
                    '0',
                    '',
                    '{}',
                    '2026-07-16 13:00:00',
                ],
                [
                    '2',
                    'Parser Unsupported',
                    'تقرير أعمار ذمم فواتير المبيعات',
                    'sales-invoice-aging',
                    'no',
                    '0',
                    '',
                    '{}',
                    '2026-07-16 13:05:00',
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

    public function test_controller_injects_parser_and_keeps_database_apply_logic(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/ReportSavedViewController.php')
        );

        foreach ([
            'use App\\Support\\Reports\\ReportSavedViewCsvImportParser;',
            'private readonly ReportSavedViewCsvImportParser $csvImportParser',
            '$this->csvImportParser->parse($csvPath)',
            '$this->csvImportParser->parse($tempPath)',
            'private function applySavedViewImportRows(Request $request, array $rows): array',
            'return DB::transaction(function () use ($request, $rows): array',
            "'user_id' => \$request->user()->id",
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

    public function test_preview_and_apply_continue_to_use_same_parser_behavior(): void
    {
        $user = User::factory()->create();
        $csv = $this->csv([
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            [
                '1',
                'Parser Shared Flow',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'yes',
                '1',
                'ignored summary',
                '{"payment_status":"paid"}',
                '2026-07-16 13:00:00',
            ],
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-views.import-apply'), [
                'csv_payload' => base64_encode($csv),
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas(
                'status',
                'تم تطبيق الاستيراد: تم إنشاء 1 عرض محفوظ، وتم تخطي 0 مكرر.'
            );

        $savedView = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('name', 'Parser Shared Flow')
            ->firstOrFail();

        $this->assertSame(
            ['payment_status' => 'paid'],
            $savedView->filters
        );
        $this->assertTrue($savedView->is_default);
    }

    public function test_phase_75b_documentation_records_behavior_preserving_extraction(): void
    {
        $document = json_decode(
            file_get_contents(
                base_path('docs/phase-75b-saved-view-csv-import-parser-implementation.json')
            ),
            true
        );

        $this->assertSame('Phase 75B', $document['phase']);
        $this->assertSame('Phase 75A clean', $document['baseline']['phase']);
        $this->assertSame('480eba8', $document['baseline']['commit']);
        $this->assertSame(
            '1495 passed / 13430 assertions',
            $document['baseline']['previous_tests']
        );

        foreach ([
            'parser_created',
            'parser_is_final',
            'parser_is_stateless',
            'csv_reading_extracted',
            'header_validation_extracted',
            'row_validation_extracted',
            'filters_payload_decoding_extracted',
            'filter_cleaning_extracted',
            'controller_injects_parser',
            'preview_and_apply_share_parser',
            'database_apply_remains_in_controller',
            'runtime_behavior_preserved',
        ] as $key) {
            $this->assertTrue($document['implementation'][$key], $key);
        }

        $this->assertSame(
            'Phase 75C',
            $document['next_recommendation']['phase']
        );
        $this->assertSame(
            'Finalize Saved View CSV Import Parser',
            $document['next_recommendation']['title']
        );
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
        $path = tempnam(sys_get_temp_dir(), 'saved-view-parser-test-');

        $this->assertNotFalse($path);

        file_put_contents($path, $csv);

        try {
            return (new ReportSavedViewCsvImportParser())->parse($path);
        } finally {
            @unlink($path);
        }
    }
}
