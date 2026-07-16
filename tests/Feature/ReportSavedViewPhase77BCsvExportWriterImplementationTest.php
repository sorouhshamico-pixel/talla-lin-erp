<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Support\Reports\ReportSavedViewCsvExportWriter;
use App\Support\Reports\ReportSavedViewImportExportVersionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class ReportSavedViewPhase77BCsvExportWriterImplementationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_77b_files_exist(): void
    {
        $this->assertFileExists(
            app_path(
                'Support/Reports/ReportSavedViewCsvExportWriter.php'
            )
        );
        $this->assertFileExists(
            base_path(
                'docs/'
                . 'phase-77b-saved-view-csv-export-writer-'
                . 'implementation.json'
            )
        );
        $this->assertFileExists(
            base_path(
                'docs/'
                . 'phase-77b-saved-view-csv-export-writer-'
                . 'implementation.md'
            )
        );
    }

    public function test_writer_is_final_stateless_and_output_only(): void
    {
        $reflection = new ReflectionClass(
            ReportSavedViewCsvExportWriter::class
        );
        $source = file_get_contents($reflection->getFileName());

        $this->assertTrue($reflection->isFinal());
        $this->assertNull($reflection->getConstructor());
        $this->assertTrue($reflection->getMethod('write')->isPublic());

        foreach ([
            'public function write(iterable $formattedSavedViews): void',
            "fopen('php://output', 'w')",
            'fwrite($handle, "\\xEF\\xBB\\xBF")',
            'ReportSavedViewImportExportVersionRegistry::exportHeader()',
            'ReportSavedViewImportExportVersionRegistry::currentVersion()',
            '$filtersSummary = implode(\'; \', $summaryParts)',
            '$filtersPayload = json_encode(',
            '(object) $filtersPayloadData',
            'JSON_UNESCAPED_UNICODE',
            'JSON_UNESCAPED_SLASHES',
            '$filtersPayload === false ? \'{}\' : $filtersPayload',
            'fclose($handle)',
        ] as $marker) {
            $this->assertStringContainsString($marker, $source);
        }

        foreach ([
            'App\\Models\\',
            'App\\Services\\',
            'Illuminate\\Http\\',
            'Illuminate\\Support\\Facades\\DB',
            'Request $request',
            'response(',
            'session(',
            'auth(',
            'redirect(',
            'view(',
            'route(',
            'ReportSavedViewCsvImportParser',
            'ReportSavedViewImportApplyService',
        ] as $marker) {
            $this->assertStringNotContainsString($marker, $source);
        }
    }

    public function test_writer_outputs_exact_bytes_and_payload(): void
    {
        $updatedAt = now()->setMicrosecond(0);

        $formatted = [
            (object) [
                'name' => 'First Writer Row',
                'report_label' => 'تقرير الأرباح والخسائر',
                'report_key' => 'profit-loss',
                'is_default' => true,
                'filters' => collect([
                    [
                        'key' => 'payment_status',
                        'label' => 'حالة الدفع',
                        'value' => 'paid',
                        'display_value' => 'مدفوعة بالكامل',
                    ],
                    [
                        'key' => 'nested',
                        'label' => 'بيانات',
                        'value' => [
                            'path' => '/reports/test',
                            'label' => 'عربي',
                        ],
                        'display_value' => 'بيانات مركبة',
                    ],
                ]),
                'updated_at' => $updatedAt,
            ],
            (object) [
                'name' => 'Second Writer Row',
                'report_label' =>
                    'تقرير أعمار ذمم فواتير المبيعات',
                'report_key' => 'sales-invoice-aging',
                'is_default' => false,
                'filters' => collect(),
                'updated_at' => null,
            ],
        ];

        $csv = $this->captureWriterOutput($formatted);
        $rows = $this->parseCsv($csv);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertSame(
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            $rows[0]
        );
        $this->assertCount(3, $rows);
        $this->assertSame([
            'First Writer Row',
            'Second Writer Row',
        ], array_column(array_slice($rows, 1), 1));
        $this->assertSame('1', $rows[1][0]);
        $this->assertSame('yes', $rows[1][4]);
        $this->assertSame('2', $rows[1][5]);
        $this->assertSame(
            'حالة الدفع: مدفوعة بالكامل (paid); '
            . 'بيانات: بيانات مركبة '
            . '({"path":"/reports/test","label":"عربي"})',
            $rows[1][6]
        );
        $this->assertSame([
            'payment_status' => 'paid',
            'nested' => [
                'path' => '/reports/test',
                'label' => 'عربي',
            ],
        ], json_decode($rows[1][7], true));
        $this->assertSame(
            $updatedAt->toDateTimeString(),
            $rows[1][8]
        );
        $this->assertSame('no', $rows[2][4]);
        $this->assertSame('0', $rows[2][5]);
        $this->assertSame('', $rows[2][6]);
        $this->assertSame('{}', $rows[2][7]);
        $this->assertSame('', $rows[2][8]);
    }

    public function test_writer_keeps_empty_header_and_json_failure_fallback(): void
    {
        $emptyCsv = $this->captureWriterOutput([]);
        $emptyRows = $this->parseCsv($emptyCsv);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $emptyCsv);
        $this->assertCount(1, $emptyRows);
        $this->assertSame(
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            $emptyRows[0]
        );

        $failureRows = $this->parseCsv(
            $this->captureWriterOutput([
                (object) [
                    'name' => 'Encoding Failure',
                    'report_label' => 'تقرير الأرباح والخسائر',
                    'report_key' => 'profit-loss',
                    'is_default' => false,
                    'filters' => collect([
                        [
                            'key' => 'invalid_number',
                            'label' => 'رقم',
                            'value' => INF,
                            'display_value' => 'غير محدود',
                        ],
                    ]),
                    'updated_at' => null,
                ],
            ])
        );

        $this->assertSame('{}', $failureRows[1][7]);
    }

    public function test_controller_delegates_writer_and_keeps_http_boundary(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/ReportSavedViewController.php')
        );

        foreach ([
            'use App\\Support\\Reports\\ReportSavedViewCsvExportWriter;',
            'private readonly ReportSavedViewCsvExportWriter $csvExportWriter',
            '$savedViewService->exportForManagement(',
            '$formattedSavedViews = $savedViews->map(',
            '$this->formatSavedView($savedView)',
            "'saved-views-' . now()->format('Ymd-His') . '.csv'",
            'response()->streamDownload(',
            '$this->csvExportWriter->write($formattedSavedViews)',
            "'Content-Type' => 'text/csv; charset=UTF-8'",
        ] as $marker) {
            $this->assertStringContainsString($marker, $controller);
        }

        foreach ([
            "fopen('php://output', 'w')",
            'fwrite($handle, "\\xEF\\xBB\\xBF")',
            'ReportSavedViewImportExportVersionRegistry::exportHeader()',
            'ReportSavedViewImportExportVersionRegistry::currentVersion()',
            '$filtersSummary =',
            '$filtersPayload = json_encode(',
            'fputcsv($handle',
            'fclose($handle)',
        ] as $marker) {
            $this->assertStringNotContainsString($marker, $controller);
        }
    }

    public function test_controller_export_preserves_scope_order_and_payload(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'Zulu Writer Controller',
            'filters' => [],
            'is_default' => false,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'Alpha Writer Controller',
            'filters' => ['payment_status' => 'paid'],
            'is_default' => true,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $otherUser->id,
            'report_key' => 'profit-loss',
            'name' => 'Other Writer Controller',
            'filters' => ['payment_status' => 'unpaid'],
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
        $rows = $this->parseCsv($csv);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertSame([
            'Alpha Writer Controller',
            'Zulu Writer Controller',
        ], array_column(array_slice($rows, 1), 1));
        $this->assertSame(
            'حالة الدفع: مدفوعة بالكامل (paid)',
            $rows[1][6]
        );
        $this->assertSame([
            'payment_status' => 'paid',
        ], json_decode($rows[1][7], true));
        $this->assertStringNotContainsString(
            'Other Writer Controller',
            $csv
        );
    }

    public function test_exported_csv_round_trips_through_import(): void
    {
        $sourceUser = User::factory()->create();
        $targetUser = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $sourceUser->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'Writer Round Trip',
            'filters' => [
                'payment_status' => 'partial',
                'aging_bucket' => 'overdue_61_90',
            ],
            'is_default' => true,
        ]);

        $csv = $this->actingAs($sourceUser)
            ->get(route('reports.saved-views.export'))
            ->assertOk()
            ->streamedContent();

        $this->actingAs($targetUser)
            ->post(route('reports.saved-views.import-apply'), [
                'csv_payload' => base64_encode($csv),
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas(
                'status',
                'تم تطبيق الاستيراد: تم إنشاء 1 عرض محفوظ، '
                . 'وتم تخطي 0 مكرر.'
            );

        $imported = ReportSavedView::query()
            ->where('user_id', $targetUser->id)
            ->where('name', 'Writer Round Trip')
            ->firstOrFail();

        $this->assertSame([
            'payment_status' => 'partial',
            'aging_bucket' => 'overdue_61_90',
        ], $imported->filters);
        $this->assertTrue($imported->is_default);
    }

    public function test_phase_77b_documentation_records_clean_extraction(): void
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-77b-saved-view-csv-export-writer-'
                    . 'implementation.json'
                )
            ),
            true
        );

        $this->assertSame('Phase 77B', $document['phase']);
        $this->assertSame('Phase 77A clean', $document['baseline']['phase']);
        $this->assertSame('0b47c18', $document['baseline']['commit']);
        $this->assertSame(
            '1559 passed / 14194 assertions',
            $document['baseline']['previous_tests']
        );
        $this->assertTrue(
            $document['implementation']['runtime_behavior_preserved']
        );
        $this->assertSame(
            'Phase 77C',
            $document['next_recommendation']['phase']
        );
    }

    /**
     * @param iterable<int, object> $formattedSavedViews
     */
    private function captureWriterOutput(
        iterable $formattedSavedViews
    ): string {
        ob_start();

        try {
            (new ReportSavedViewCsvExportWriter())
                ->write($formattedSavedViews);

            return (string) ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }
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
