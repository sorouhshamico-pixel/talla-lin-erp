<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase73BImportExportFormatVersionImplementationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_73b_implementation_docs_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-73b-saved-view-import-export-format-version-implementation.json'));
        $this->assertFileExists(base_path('docs/phase-73b-saved-view-import-export-format-version-implementation.md'));
    }

    public function test_export_writes_format_version_as_the_first_column_and_value_one(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'Versioned Export',
            'filters' => ['payment_status' => 'paid'],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.saved-views.export'))
            ->assertOk();

        $rows = $this->csvRows($response->streamedContent());

        $this->assertGreaterThanOrEqual(2, count($rows));
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
        ], $rows[0]);
        $this->assertSame('1', $rows[1][0]);

        $record = array_combine($rows[0], $rows[1]);

        $this->assertIsArray($record);
        $this->assertSame('1', $record['format_version']);
        $this->assertSame(
            ['payment_status' => 'paid'],
            json_decode($record['filters_payload'], true)
        );
    }

    public function test_explicit_version_one_import_uses_filters_payload(): void
    {
        $user = User::factory()->create();

        $csv = $this->csv([
            $this->versionOneHeader(),
            [
                '1',
                'Version One Import',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'yes',
                '1',
                'ملخص بشري لا يستخدم للاستيراد',
                json_encode(
                    ['payment_status' => 'partial'],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                '2026-07-16 09:00:00',
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
            ->where('name', 'Version One Import')
            ->firstOrFail();

        $this->assertSame(['payment_status' => 'partial'], $savedView->filters);
        $this->assertTrue($savedView->is_default);
    }

    public function test_explicit_version_one_without_filters_payload_column_is_rejected(): void
    {
        $user = User::factory()->create();

        $csv = $this->csv([
            [
                'format_version',
                'name',
                'report_label',
                'report_key',
                'is_default',
                'filter_count',
                'filters_summary',
                'updated_at',
            ],
            [
                '1',
                'Missing Payload Column',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '0',
                '',
                '2026-07-16 09:00:00',
            ],
        ]);

        $this->assertRejectedWithoutWrites($user, $csv);
    }

    public function test_explicit_version_one_with_empty_filters_payload_is_rejected(): void
    {
        $user = User::factory()->create();

        $csv = $this->csv([
            $this->versionOneHeader(),
            [
                '1',
                'Empty Payload',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '0',
                '',
                '',
                '2026-07-16 09:00:00',
            ],
        ]);

        $this->assertRejectedWithoutWrites($user, $csv);
    }

    public function test_empty_or_unsupported_explicit_version_is_rejected(): void
    {
        foreach (['', '2', 'v1'] as $index => $version) {
            $user = User::factory()->create();

            $csv = $this->csv([
                $this->versionOneHeader(),
                [
                    $version,
                    'Invalid Version ' . $index,
                    'تقرير الأرباح والخسائر',
                    'profit-loss',
                    'no',
                    '0',
                    '',
                    '{}',
                    '2026-07-16 09:00:00',
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
        }

        $this->assertSame(0, ReportSavedView::query()->count());
    }

    public function test_mixed_explicit_versions_are_rejected_before_writes(): void
    {
        $user = User::factory()->create();

        $csv = $this->csv([
            $this->versionOneHeader(),
            [
                '1',
                'Mixed Version One',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '0',
                '',
                '{}',
                '2026-07-16 09:00:00',
            ],
            [
                '2',
                'Mixed Version Two',
                'تقرير أعمار ذمم فواتير المبيعات',
                'sales-invoice-aging',
                'no',
                '0',
                '',
                '{}',
                '2026-07-16 09:05:00',
            ],
        ]);

        $this->assertRejectedWithoutWrites($user, $csv);
    }

    public function test_legacy_unversioned_csv_without_filters_payload_remains_supported(): void
    {
        $user = User::factory()->create();

        $csv = $this->csv([
            [
                'name',
                'report_label',
                'report_key',
                'is_default',
                'filter_count',
                'filters_summary',
                'updated_at',
            ],
            [
                'Legacy Without Payload',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '1',
                'حالة الدفع: مدفوعة بالكامل (paid)',
                '2026-07-16 09:00:00',
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
            ->where('name', 'Legacy Without Payload')
            ->firstOrFail();

        $this->assertSame([], $savedView->filters);
    }

    public function test_legacy_unversioned_csv_with_filters_payload_remains_supported(): void
    {
        $user = User::factory()->create();

        $csv = $this->csv([
            [
                'name',
                'report_label',
                'report_key',
                'is_default',
                'filter_count',
                'filters_summary',
                'filters_payload',
                'updated_at',
            ],
            [
                'Legacy With Payload',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '1',
                'ملخص بشري غير مقروء آليًا',
                json_encode(
                    ['payment_status' => 'unpaid'],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                '2026-07-16 09:00:00',
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
            ->where('name', 'Legacy With Payload')
            ->firstOrFail();

        $this->assertSame(['payment_status' => 'unpaid'], $savedView->filters);
    }

    public function test_versioned_export_can_round_trip_without_filter_loss(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'Versioned Round Trip',
            'filters' => [
                'payment_status' => 'partial',
                'aging_bucket' => 'overdue_61_90',
            ],
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.saved-views.export'))
            ->assertOk();

        $csv = $response->streamedContent();

        ReportSavedView::query()
            ->where('user_id', $user->id)
            ->delete();

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
            ->where('name', 'Versioned Round Trip')
            ->firstOrFail();

        $this->assertSame([
            'payment_status' => 'partial',
            'aging_bucket' => 'overdue_61_90',
        ], $savedView->filters);
        $this->assertTrue($savedView->is_default);
    }

    public function test_source_contains_registry_backed_format_version_contract_markers(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $registry = file_get_contents(
            app_path('Support/Reports/ReportSavedViewImportExportVersionRegistry.php')
        );

        foreach ([
            'use App\Support\Reports\ReportSavedViewImportExportVersionRegistry;',
            'ReportSavedViewImportExportVersionRegistry::exportHeader()',
            'ReportSavedViewImportExportVersionRegistry::currentVersion()',
            '$this->csvImportParser->parse(',
            'private readonly ReportSavedViewCsvImportParser $csvImportParser',
            '$this->csvImportParser->parse(',
            '$this->csvImportParser->parse(',
            '$this->csvImportParser->parse(',
            'return DB::transaction(function () use ($request, $rows): array',
        ] as $marker) {
            $this->assertStringContainsString($marker, $controller);
        }

        foreach ([
            'final class ReportSavedViewImportExportVersionRegistry',
            "private const FORMAT_VERSION_COLUMN = 'format_version';",
            "private const CURRENT_VERSION = '1';",
            'public static function supportedVersions(): array',
            'public static function requiredColumns(string $version): array',
            'public static function exportHeader(): array',
            'public static function requiresFiltersPayload(string $version): bool',
        ] as $marker) {
            $this->assertStringContainsString($marker, $registry);
        }

        foreach ([
            'IMPORT_PREVIEW_REQUIRED_COLUMNS',
            'IMPORT_EXPORT_FORMAT_VERSION',
            'SUPPORTED_IMPORT_EXPORT_FORMAT_VERSIONS',
            'IMPORT_PREVIEW_V1_REQUIRED_COLUMNS',
        ] as $removedConstant) {
            $this->assertStringNotContainsString($removedConstant, $controller);
        }

        $this->assertStringNotContainsString('parseFiltersSummary', $controller);
        $this->assertStringNotContainsString(
            "json_decode(\$data['filters_summary']",
            $controller
        );
    }

    /**
     * @return array<int, string>
     */
    private function versionOneHeader(): array
    {
        return [
            'format_version',
            'name',
            'report_label',
            'report_key',
            'is_default',
            'filter_count',
            'filters_summary',
            'filters_payload',
            'updated_at',
        ];
    }

    private function assertRejectedWithoutWrites(User $user, string $csv): void
    {
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
     * @return array<int, array<int, string>>
     */
    private function csvRows(string $csv): array
    {
        $csv = str_replace("\xEF\xBB\xBF", '', $csv);
        $lines = preg_split('/\r\n|\n|\r/', trim($csv));

        return array_map(
            fn (string $line): array => str_getcsv($line),
            array_values(
                array_filter(
                    $lines,
                    fn (?string $line): bool => $line !== null && $line !== ''
                )
            )
        );
    }
}
