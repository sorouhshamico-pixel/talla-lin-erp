<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Support\Reports\ReportSavedViewImportExportVersionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class ReportSavedViewPhase74BImportExportVersionRegistryImplementationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_74b_implementation_files_exist(): void
    {
        $this->assertFileExists(
            app_path('Support/Reports/ReportSavedViewImportExportVersionRegistry.php')
        );
        $this->assertFileExists(
            base_path('docs/phase-74b-saved-view-import-export-version-registry-implementation.json')
        );
        $this->assertFileExists(
            base_path('docs/phase-74b-saved-view-import-export-version-registry-implementation.md')
        );
    }

    public function test_registry_is_final_static_metadata_only_class(): void
    {
        $reflection = new ReflectionClass(
            ReportSavedViewImportExportVersionRegistry::class
        );
        $source = file_get_contents($reflection->getFileName());

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->getConstructor()->isPrivate());

        foreach ([
            'Illuminate\\',
            'App\\Models\\',
            'DB::',
            'request(',
            'response(',
            'session(',
            'auth(',
            'file_get_contents(',
            'fopen(',
        ] as $forbiddenMarker) {
            $this->assertStringNotContainsString($forbiddenMarker, $source);
        }
    }

    public function test_registry_public_api_returns_locked_metadata(): void
    {
        $this->assertSame(
            'format_version',
            ReportSavedViewImportExportVersionRegistry::formatVersionColumn()
        );
        $this->assertSame(
            '1',
            ReportSavedViewImportExportVersionRegistry::currentVersion()
        );
        $this->assertSame(
            ['1'],
            ReportSavedViewImportExportVersionRegistry::supportedVersions()
        );
        $this->assertTrue(
            ReportSavedViewImportExportVersionRegistry::supports('1')
        );
        $this->assertFalse(
            ReportSavedViewImportExportVersionRegistry::supports('2')
        );

        $this->assertSame([
            'name',
            'report_label',
            'report_key',
            'is_default',
            'filter_count',
            'filters_summary',
            'updated_at',
        ], ReportSavedViewImportExportVersionRegistry::legacyRequiredColumns());

        $this->assertSame($this->versionOneHeader(), 
            ReportSavedViewImportExportVersionRegistry::requiredColumns('1')
        );
        $this->assertSame(
            [],
            ReportSavedViewImportExportVersionRegistry::requiredColumns('2')
        );
        $this->assertSame(
            $this->versionOneHeader(),
            ReportSavedViewImportExportVersionRegistry::exportHeader()
        );
        $this->assertTrue(
            ReportSavedViewImportExportVersionRegistry::requiresFiltersPayload('1')
        );
        $this->assertFalse(
            ReportSavedViewImportExportVersionRegistry::requiresFiltersPayload('2')
        );
    }

    public function test_controller_uses_registry_and_has_no_inline_version_metadata(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/ReportSavedViewController.php')
        );

        foreach ([
            'use App\\Support\\Reports\\ReportSavedViewImportExportVersionRegistry;',
            'ReportSavedViewImportExportVersionRegistry::exportHeader()',
            'ReportSavedViewImportExportVersionRegistry::currentVersion()',
            '$this->csvImportParser->parse(',
            'private readonly ReportSavedViewCsvImportParser $csvImportParser',
            '$this->csvImportParser->parse(',
            '$this->csvImportParser->parse(',
            '$this->csvImportParser->parse(',
        ] as $marker) {
            $this->assertStringContainsString($marker, $controller);
        }

        foreach ([
            'IMPORT_PREVIEW_REQUIRED_COLUMNS',
            'IMPORT_EXPORT_FORMAT_VERSION',
            'SUPPORTED_IMPORT_EXPORT_FORMAT_VERSIONS',
            'IMPORT_PREVIEW_V1_REQUIRED_COLUMNS',
        ] as $removedConstant) {
            $this->assertStringNotContainsString($removedConstant, $controller);
        }
    }

    public function test_registry_backed_export_schema_and_version_remain_unchanged(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'Registry Export',
            'filters' => ['payment_status' => 'paid'],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.saved-views.export'))
            ->assertOk();

        $rows = $this->csvRows($response->streamedContent());

        $this->assertGreaterThanOrEqual(2, count($rows));
        $this->assertSame(
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            $rows[0]
        );
        $this->assertSame(
            ReportSavedViewImportExportVersionRegistry::currentVersion(),
            $rows[1][0]
        );

        $record = array_combine($rows[0], $rows[1]);

        $this->assertIsArray($record);
        $this->assertSame(
            ['payment_status' => 'paid'],
            json_decode($record['filters_payload'], true)
        );
    }

    public function test_registry_backed_version_one_round_trip_remains_lossless(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'Registry Round Trip',
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
            ->where('name', 'Registry Round Trip')
            ->firstOrFail();

        $this->assertSame([
            'payment_status' => 'partial',
            'aging_bucket' => 'overdue_61_90',
        ], $savedView->filters);
        $this->assertTrue($savedView->is_default);
    }

    public function test_registry_backed_legacy_modes_remain_supported(): void
    {
        $withoutPayloadUser = User::factory()->create();

        $withoutPayload = $this->csv([
            ReportSavedViewImportExportVersionRegistry::legacyRequiredColumns(),
            [
                'Registry Legacy Empty',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '1',
                'حالة الدفع: مدفوعة بالكامل (paid)',
                '2026-07-16 11:00:00',
            ],
        ]);

        $this->actingAs($withoutPayloadUser)
            ->post(route('reports.saved-views.import-apply'), [
                'csv_payload' => base64_encode($withoutPayload),
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas(
                'status',
                'تم تطبيق الاستيراد: تم إنشاء 1 عرض محفوظ، وتم تخطي 0 مكرر.'
            );

        $withoutPayloadView = ReportSavedView::query()
            ->where('user_id', $withoutPayloadUser->id)
            ->where('name', 'Registry Legacy Empty')
            ->firstOrFail();

        $this->assertSame([], $withoutPayloadView->filters);

        $withPayloadUser = User::factory()->create();

        $legacyWithPayloadHeader = ReportSavedViewImportExportVersionRegistry::legacyRequiredColumns();
        array_splice($legacyWithPayloadHeader, 6, 0, ['filters_payload']);

        $withPayload = $this->csv([
            $legacyWithPayloadHeader,
            [
                'Registry Legacy Payload',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '1',
                'ignored summary',
                '{"payment_status":"unpaid"}',
                '2026-07-16 11:05:00',
            ],
        ]);

        $this->actingAs($withPayloadUser)
            ->post(route('reports.saved-views.import-apply'), [
                'csv_payload' => base64_encode($withPayload),
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas(
                'status',
                'تم تطبيق الاستيراد: تم إنشاء 1 عرض محفوظ، وتم تخطي 0 مكرر.'
            );

        $withPayloadView = ReportSavedView::query()
            ->where('user_id', $withPayloadUser->id)
            ->where('name', 'Registry Legacy Payload')
            ->firstOrFail();

        $this->assertSame(['payment_status' => 'unpaid'], $withPayloadView->filters);
    }

    public function test_registry_backed_rejection_and_transaction_guards_remain_locked(): void
    {
        $user = User::factory()->create();

        $csv = $this->csv([
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            [
                '2',
                'Unsupported Registry Version',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '0',
                '',
                '{}',
                '2026-07-16 11:00:00',
            ],
            [
                '1',
                'Otherwise Valid Registry Row',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '0',
                '',
                '{}',
                '2026-07-16 11:05:00',
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

        $controller = file_get_contents(
            app_path('Http/Controllers/ReportSavedViewController.php')
        );

        $this->assertStringContainsString(
            'return DB::transaction(function () use ($request, $rows): array',
            $controller
        );
        $this->assertStringNotContainsString('parseFiltersSummary', $controller);
    }

    public function test_phase_74b_documentation_records_behavior_preserving_extraction(): void
    {
        $document = json_decode(
            file_get_contents(
                base_path('docs/phase-74b-saved-view-import-export-version-registry-implementation.json')
            ),
            true
        );

        $this->assertSame('Phase 74B', $document['phase']);
        $this->assertSame('Phase 74A clean', $document['baseline']['phase']);
        $this->assertSame('bd83a6c', $document['baseline']['commit']);
        $this->assertSame('1464 passed / 13082 assertions', $document['baseline']['previous_tests']);

        foreach ([
            'registry_created',
            'controller_inline_metadata_removed',
            'controller_uses_registry_for_export_header',
            'controller_uses_registry_for_current_version',
            'controller_uses_registry_for_version_column',
            'controller_uses_registry_for_legacy_columns',
            'controller_uses_registry_for_required_columns',
            'controller_uses_registry_for_supported_version_check',
            'controller_uses_registry_for_payload_requirement',
            'runtime_behavior_preserved',
        ] as $key) {
            $this->assertTrue($document['implementation'][$key], $key);
        }

        $this->assertSame('Phase 74C', $document['next_recommendation']['phase']);
        $this->assertSame(
            'Finalize Saved View Import Export Version Registry',
            $document['next_recommendation']['title']
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
