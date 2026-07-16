<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Support\Reports\ReportSavedViewImportExportVersionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class ReportSavedViewPhase74CImportExportVersionRegistryFinalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_74c_finalization_files_exist(): void
    {
        $this->assertFileExists(
            base_path('docs/phase-74c-saved-view-import-export-version-registry-finalization.json')
        );
        $this->assertFileExists(
            base_path('docs/phase-74c-saved-view-import-export-version-registry-finalization.md')
        );
    }

    public function test_phase_74c_is_finalization_without_runtime_changes(): void
    {
        $contract = $this->contract();

        $this->assertSame('Phase 74C', $contract['phase']);
        $this->assertSame(
            'Saved View Import Export Version Registry Finalization',
            $contract['title']
        );
        $this->assertSame('Phase 74B clean', $contract['baseline']['phase']);
        $this->assertSame('18aa201', $contract['baseline']['commit']);
        $this->assertSame(
            '1473 passed / 13188 assertions',
            $contract['baseline']['previous_tests']
        );
        $this->assertSame('finalization', $contract['scope']['type']);
        $this->assertFalse($contract['scope']['implementation_changes_expected']);

        foreach ([
            'app/Support/Reports/ReportSavedViewImportExportVersionRegistry.php',
            'app/Http/Controllers/ReportSavedViewController.php',
            'routes/web.php',
            'app/Services/ReportSavedViewService.php',
            'resources/views/reports/saved-views/index.blade.php',
            'resources/views/reports/saved-views/edit.blade.php',
            'app/Models/ReportSavedView.php',
            'app/Support/Reports/ReportSavedViewRegistry.php',
        ] as $lockedFile) {
            $this->assertContains($lockedFile, $contract['scope']['locked_implementation_files']);
        }
    }

    public function test_final_registry_is_final_private_constructed_and_metadata_only(): void
    {
        $reflection = new ReflectionClass(
            ReportSavedViewImportExportVersionRegistry::class
        );
        $source = file_get_contents($reflection->getFileName());

        $this->assertTrue($reflection->isFinal());
        $this->assertNotNull($reflection->getConstructor());
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

    public function test_final_registry_api_returns_exact_locked_string_metadata(): void
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
        $this->assertIsString(
            ReportSavedViewImportExportVersionRegistry::supportedVersions()[0]
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

        $this->assertSame(
            $this->versionOneHeader(),
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

    public function test_final_controller_integration_uses_registry_without_inline_metadata(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/ReportSavedViewController.php')
        );

        foreach ([
            'use App\\Support\\Reports\\ReportSavedViewImportExportVersionRegistry;',
            'ReportSavedViewImportExportVersionRegistry::exportHeader()',
            'ReportSavedViewImportExportVersionRegistry::currentVersion()',
            'ReportSavedViewImportExportVersionRegistry::formatVersionColumn()',
            'ReportSavedViewImportExportVersionRegistry::legacyRequiredColumns()',
            'ReportSavedViewImportExportVersionRegistry::requiredColumns(',
            'ReportSavedViewImportExportVersionRegistry::supports($formatVersion)',
            'ReportSavedViewImportExportVersionRegistry::requiresFiltersPayload($formatVersion)',
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

    public function test_final_registry_backed_versioned_round_trip_remains_lossless(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'Final Registry Round Trip',
            'filters' => [
                'payment_status' => 'partial',
                'aging_bucket' => 'overdue_31_60',
            ],
            'is_default' => true,
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
        $this->assertSame('1', $rows[1][0]);

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
            ->where('name', 'Final Registry Round Trip')
            ->firstOrFail();

        $this->assertSame([
            'payment_status' => 'partial',
            'aging_bucket' => 'overdue_31_60',
        ], $savedView->filters);
        $this->assertTrue($savedView->is_default);
    }

    public function test_final_legacy_modes_remain_supported_through_registry_metadata(): void
    {
        $withoutPayloadUser = User::factory()->create();

        $withoutPayload = $this->csv([
            ReportSavedViewImportExportVersionRegistry::legacyRequiredColumns(),
            [
                'Final Registry Legacy Empty',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '1',
                'حالة الدفع: مدفوعة بالكامل (paid)',
                '2026-07-16 12:00:00',
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
            ->where('name', 'Final Registry Legacy Empty')
            ->firstOrFail();

        $this->assertSame([], $withoutPayloadView->filters);

        $withPayloadUser = User::factory()->create();
        $legacyWithPayloadHeader =
            ReportSavedViewImportExportVersionRegistry::legacyRequiredColumns();
        array_splice($legacyWithPayloadHeader, 6, 0, ['filters_payload']);

        $withPayload = $this->csv([
            $legacyWithPayloadHeader,
            [
                'Final Registry Legacy Payload',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '1',
                'ignored summary',
                '{"payment_status":"unpaid"}',
                '2026-07-16 12:05:00',
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
            ->where('name', 'Final Registry Legacy Payload')
            ->firstOrFail();

        $this->assertSame(['payment_status' => 'unpaid'], $withPayloadView->filters);
    }

    public function test_final_unsupported_and_mixed_versions_block_all_writes(): void
    {
        $user = User::factory()->create();

        $csv = $this->csv([
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            [
                '1',
                'Final Registry Valid Row',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '0',
                '',
                '{}',
                '2026-07-16 12:00:00',
            ],
            [
                '2',
                'Final Registry Unsupported Row',
                'تقرير أعمار ذمم فواتير المبيعات',
                'sales-invoice-aging',
                'no',
                '0',
                '',
                '{}',
                '2026-07-16 12:05:00',
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

    public function test_final_source_locks_filters_and_transaction_safety(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/ReportSavedViewController.php')
        );

        foreach ([
            'private function previewSavedViewImport(string $path): array',
            'private function decodeImportFiltersPayload(string $filtersPayload, array &$errors): array',
            'private function cleanImportedFilters(array $filters): array',
            'return DB::transaction(function () use ($request, $rows): array',
            "'filters' => \$row['filters'] ?? []",
        ] as $marker) {
            $this->assertStringContainsString($marker, $controller);
        }

        $this->assertStringNotContainsString('parseFiltersSummary', $controller);
        $this->assertStringNotContainsString(
            "json_decode(\$data['filters_summary']",
            $controller
        );
    }

    public function test_phase_74c_contract_locks_registry_and_recommends_parser_contract(): void
    {
        $contract = $this->contract();

        foreach ([
            'format_version_first_export_column_locked',
            'new_exports_use_version_one_locked',
            'versioned_lossless_round_trip_locked',
            'explicit_v1_requires_non_empty_filters_payload_locked',
            'invalid_or_list_filters_payload_rejected_locked',
            'empty_unsupported_mixed_versions_rejected_locked',
            'legacy_unversioned_without_payload_supported_locked',
            'legacy_unversioned_without_payload_imports_empty_filters_locked',
            'legacy_unversioned_with_payload_supported_locked',
            'version_not_inferred_from_other_columns_locked',
            'filters_summary_human_readable_only_locked',
            'filters_payload_machine_source_locked',
            'import_revalidation_before_writes_locked',
            'transaction_boundary_locked',
            'authenticated_user_scope_locked',
            'duplicate_skip_without_overwrite_locked',
            'default_normalization_per_user_report_locked',
            'preview_export_bulk_selection_pagination_preserved',
            'phase_70_import_preview_contract_preserved',
            'phase_73_format_version_contract_preserved',
        ] as $key) {
            $this->assertTrue($contract['finalized_behavior'][$key], $key);
        }

        $this->assertSame(
            ['1'],
            $contract['finalized_registry_api']['supportedVersions']['value']
        );
        $this->assertTrue(
            $contract['finalized_registry_api']['supportedVersions']['identifiers_are_strings']
        );
        $this->assertSame('Phase 75A', $contract['next_recommendation']['phase']);
        $this->assertSame(
            'Saved View CSV Import Parser Contract',
            $contract['next_recommendation']['title']
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
                base_path('docs/phase-74c-saved-view-import-export-version-registry-finalization.json')
            ),
            true
        );

        $this->assertIsArray($contract);

        return $contract;
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
