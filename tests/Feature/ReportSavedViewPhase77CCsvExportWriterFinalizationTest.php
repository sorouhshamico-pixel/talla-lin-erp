<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Support\Reports\ReportSavedViewCsvExportWriter;
use App\Support\Reports\ReportSavedViewImportExportVersionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class ReportSavedViewPhase77CCsvExportWriterFinalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_77c_files_and_contract_exist(): void
    {
        $this->assertFileExists(
            base_path(
                'docs/phase-77c-saved-view-csv-export-writer-finalization.json'
            )
        );
        $this->assertFileExists(
            base_path(
                'docs/phase-77c-saved-view-csv-export-writer-finalization.md'
            )
        );

        $contract = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-77c-saved-view-csv-export-writer-'
                    . 'finalization.json'
                )
            ),
            true
        );

        $this->assertSame('Phase 77C', $contract['phase']);
        $this->assertSame('finalization', $contract['type']);
        $this->assertSame('Phase 77B', $contract['baseline']['latest_feature_phase']);
        $this->assertSame('40c6f60', $contract['baseline']['feature_commit']);
        $this->assertSame('8df3f25', $contract['baseline']['workflow_commit']);
        $this->assertFalse($contract['scope']['runtime_changes_expected']);
        $this->assertSame(
            'Phase 78A',
            $contract['next_recommendation']['phase']
        );
        $this->assertTrue(
            $contract['next_recommendation']['implementation_deferred']
        );
        $this->assertNotEmpty($contract['guardrails']);

        foreach ([
            'writer_exists_and_is_final',
            'writer_is_stateless',
            'writer_has_no_constructor_dependencies',
            'writer_accepts_iterable_formatted_rows',
            'writer_owns_php_output_stream',
            'writer_owns_utf8_bom',
            'writer_uses_registry_header',
            'writer_uses_registry_current_version',
            'writer_preserves_input_order',
            'writer_builds_filter_count',
            'writer_builds_human_summary',
            'writer_builds_machine_payload',
            'writer_preserves_unicode_and_slashes',
            'writer_returns_empty_object_on_json_failure',
            'writer_closes_output_stream',
            'controller_retains_http_and_query_boundary',
            'controller_delegates_csv_bytes_to_writer',
            'export_order_and_user_scope_preserved',
            'versioned_export_import_round_trip_preserved',
        ] as $key) {
            $this->assertTrue(
                $contract['finalized_behavior'][$key],
                $key
            );
        }
    }

    public function test_final_writer_identity_and_source_boundary_are_locked(): void
    {
        $reflection = new ReflectionClass(
            ReportSavedViewCsvExportWriter::class
        );
        $source = file_get_contents($reflection->getFileName());

        $this->assertTrue($reflection->isFinal());
        $this->assertNull($reflection->getConstructor());
        $this->assertTrue($reflection->getMethod('write')->isPublic());
        $this->assertSame(
            'iterable',
            (string) $reflection
                ->getMethod('write')
                ->getParameters()[0]
                ->getType()
        );

        foreach ([
            "fopen('php://output', 'w')",
            'fwrite($handle, "\\xEF\\xBB\\xBF")',
            'ReportSavedViewImportExportVersionRegistry::exportHeader()',
            'ReportSavedViewImportExportVersionRegistry::currentVersion()',
            '$filterCount++',
            '$filtersSummary = implode(\'; \', $summaryParts)',
            '$filtersPayload = json_encode(',
            '(object) $filtersPayloadData',
            'JSON_UNESCAPED_UNICODE',
            'JSON_UNESCAPED_SLASHES',
            '$filtersPayload === false ? \'{}\' : $filtersPayload',
            'fputcsv($handle',
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

    public function test_final_writer_preserves_generator_order_bytes_and_payload(): void
    {
        $updatedAt = now()->setMicrosecond(0);

        $csv = $this->captureWriterOutput(
            (function () use ($updatedAt): \Generator {
                yield (object) [
                    'name' => 'Generator First',
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
                            'key' => 'metadata',
                            'label' => 'بيانات',
                            'value' => [
                                'path' => '/reports/final',
                                'label' => 'عربي',
                            ],
                            'display_value' => 'قيمة مركبة',
                        ],
                    ]),
                    'updated_at' => $updatedAt,
                ];

                yield (object) [
                    'name' => 'Generator Second',
                    'report_label' =>
                        'تقرير أعمار ذمم فواتير المبيعات',
                    'report_key' => 'sales-invoice-aging',
                    'is_default' => false,
                    'filters' => collect(),
                    'updated_at' => null,
                ];
            })()
        );

        $rows = $this->parseCsv($csv);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertSame(
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            $rows[0]
        );
        $this->assertSame([
            'Generator First',
            'Generator Second',
        ], array_column(array_slice($rows, 1), 1));
        $this->assertSame('yes', $rows[1][4]);
        $this->assertSame('2', $rows[1][5]);
        $this->assertSame(
            'حالة الدفع: مدفوعة بالكامل (paid); '
            . 'بيانات: قيمة مركبة '
            . '({"path":"/reports/final","label":"عربي"})',
            $rows[1][6]
        );
        $this->assertSame([
            'payment_status' => 'paid',
            'metadata' => [
                'path' => '/reports/final',
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

    public function test_final_empty_and_json_failure_fallbacks_are_locked(): void
    {
        $empty = $this->captureWriterOutput([]);
        $emptyRows = $this->parseCsv($empty);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $empty);
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

    public function test_final_controller_writer_ownership_is_locked(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/ReportSavedViewController.php')
        );
        $writer = file_get_contents(
            app_path(
                'Support/Reports/ReportSavedViewCsvExportWriter.php'
            )
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
            $this->assertStringContainsString($marker, $writer);
        }
    }

    public function test_final_export_scope_order_and_round_trip_are_locked(): void
    {
        $sourceUser = User::factory()->create();
        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $sourceUser->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'Final Round Trip',
            'filters' => [
                'payment_status' => 'partial',
                'aging_bucket' => 'overdue_61_90',
            ],
            'is_default' => true,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $sourceUser->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'Zulu Final',
            'filters' => [],
            'is_default' => false,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $otherUser->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'Other User Final',
            'filters' => [],
            'is_default' => true,
        ]);

        $csv = $this->actingAs($sourceUser)
            ->get(route('reports.saved-views.export', [
                'report_key' => 'sales-invoice-aging',
                'page' => 99,
                'per_page' => 1,
            ]))
            ->assertOk()
            ->assertHeader(
                'content-type',
                'text/csv; charset=UTF-8'
            )
            ->streamedContent();

        $rows = $this->parseCsv($csv);

        $this->assertSame([
            'Final Round Trip',
            'Zulu Final',
        ], array_column(array_slice($rows, 1), 1));
        $this->assertStringNotContainsString('Other User Final', $csv);

        $this->actingAs($targetUser)
            ->post(route('reports.saved-views.import-apply'), [
                'csv_payload' => base64_encode($csv),
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas(
                'status',
                'تم تطبيق الاستيراد: تم إنشاء 2 عرض محفوظ، '
                . 'وتم تخطي 0 مكرر.'
            );

        $imported = ReportSavedView::query()
            ->where('user_id', $targetUser->id)
            ->where('name', 'Final Round Trip')
            ->firstOrFail();

        $this->assertSame([
            'payment_status' => 'partial',
            'aging_bucket' => 'overdue_61_90',
        ], $imported->filters);
        $this->assertTrue($imported->is_default);
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
