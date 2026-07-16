<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase73CImportExportFormatVersionFinalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_73c_finalization_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-73c-saved-view-import-export-format-version-finalization.json'));
        $this->assertFileExists(base_path('docs/phase-73c-saved-view-import-export-format-version-finalization.md'));
    }

    public function test_phase_73c_is_finalization_without_runtime_changes(): void
    {
        $contract = $this->contract();

        $this->assertSame('Phase 73C', $contract['phase']);
        $this->assertSame('Saved View Import Export Format Version Finalization', $contract['title']);
        $this->assertSame('Phase 73B clean', $contract['baseline']['phase']);
        $this->assertSame('0bb324d', $contract['baseline']['commit']);
        $this->assertSame('1444 passed / 12866 assertions', $contract['baseline']['previous_tests']);
        $this->assertSame('finalization', $contract['scope']['type']);
        $this->assertFalse($contract['scope']['implementation_changes_expected']);

        foreach ([
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

    public function test_final_export_schema_starts_with_format_version_one(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'Final Versioned Export',
            'filters' => ['payment_status' => 'paid'],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.saved-views.export'))
            ->assertOk();

        $rows = $this->csvRows($response->streamedContent());

        $this->assertGreaterThanOrEqual(2, count($rows));
        $this->assertSame($this->versionOneHeader(), $rows[0]);
        $this->assertSame('1', $rows[1][0]);

        $record = array_combine($rows[0], $rows[1]);

        $this->assertIsArray($record);
        $this->assertSame('1', $record['format_version']);
        $this->assertSame(
            ['payment_status' => 'paid'],
            json_decode($record['filters_payload'], true)
        );
    }

    public function test_final_versioned_export_import_round_trip_is_lossless(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'Final Versioned Round Trip',
            'filters' => [
                'payment_status' => 'partial',
                'aging_bucket' => 'overdue_31_60',
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
            ->where('name', 'Final Versioned Round Trip')
            ->firstOrFail();

        $this->assertSame([
            'payment_status' => 'partial',
            'aging_bucket' => 'overdue_31_60',
        ], $savedView->filters);
        $this->assertTrue($savedView->is_default);
    }

    public function test_final_explicit_v1_requires_payload_column_and_non_empty_object_payload(): void
    {
        $cases = [
            $this->csv([
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
                    'Missing Final Payload Column',
                    'تقرير الأرباح والخسائر',
                    'profit-loss',
                    'no',
                    '0',
                    '',
                    '2026-07-16 10:00:00',
                ],
            ]),
            $this->csv([
                $this->versionOneHeader(),
                [
                    '1',
                    'Empty Final Payload',
                    'تقرير الأرباح والخسائر',
                    'profit-loss',
                    'no',
                    '0',
                    '',
                    '',
                    '2026-07-16 10:00:00',
                ],
            ]),
            $this->csv([
                $this->versionOneHeader(),
                [
                    '1',
                    'List Final Payload',
                    'تقرير الأرباح والخسائر',
                    'profit-loss',
                    'no',
                    '1',
                    'ignored summary',
                    '["paid"]',
                    '2026-07-16 10:00:00',
                ],
            ]),
        ];

        foreach ($cases as $index => $csv) {
            $user = User::factory()->create();

            $this->actingAs($user)
                ->post(route('reports.saved-views.import-apply'), [
                    'csv_payload' => base64_encode($csv),
                ])
                ->assertRedirect(route('reports.saved-views.index'))
                ->assertSessionHas(
                    'status',
                    'لم يتم تطبيق الاستيراد بسبب وجود أخطاء في الملف.',
                    'case ' . $index
                );
        }

        $this->assertSame(0, ReportSavedView::query()->count());
    }

    public function test_final_empty_unsupported_and_mixed_explicit_versions_block_all_writes(): void
    {
        $singleVersionCases = ['', '2', 'v1'];

        foreach ($singleVersionCases as $index => $version) {
            $user = User::factory()->create();

            $csv = $this->csv([
                $this->versionOneHeader(),
                [
                    $version,
                    'Rejected Final Version ' . $index,
                    'تقرير الأرباح والخسائر',
                    'profit-loss',
                    'no',
                    '0',
                    '',
                    '{}',
                    '2026-07-16 10:00:00',
                ],
            ]);

            $this->actingAs($user)
                ->post(route('reports.saved-views.import-apply'), [
                    'csv_payload' => base64_encode($csv),
                ])
                ->assertRedirect(route('reports.saved-views.index'))
                ->assertSessionHas('status', 'لم يتم تطبيق الاستيراد بسبب وجود أخطاء في الملف.');
        }

        $mixedUser = User::factory()->create();
        $mixedCsv = $this->csv([
            $this->versionOneHeader(),
            [
                '1',
                'Mixed Final One',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '0',
                '',
                '{}',
                '2026-07-16 10:00:00',
            ],
            [
                '2',
                'Mixed Final Two',
                'تقرير أعمار ذمم فواتير المبيعات',
                'sales-invoice-aging',
                'no',
                '0',
                '',
                '{}',
                '2026-07-16 10:05:00',
            ],
        ]);

        $this->actingAs($mixedUser)
            ->post(route('reports.saved-views.import-apply'), [
                'csv_payload' => base64_encode($mixedCsv),
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas('status', 'لم يتم تطبيق الاستيراد بسبب وجود أخطاء في الملف.');

        $this->assertSame(0, ReportSavedView::query()->count());
    }

    public function test_final_legacy_unversioned_modes_remain_supported(): void
    {
        $withoutPayloadUser = User::factory()->create();

        $withoutPayload = $this->csv([
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
                'Final Legacy Without Payload',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '1',
                'حالة الدفع: مدفوعة بالكامل (paid)',
                '2026-07-16 10:00:00',
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
            ->where('name', 'Final Legacy Without Payload')
            ->firstOrFail();

        $this->assertSame([], $withoutPayloadView->filters);

        $withPayloadUser = User::factory()->create();

        $withPayload = $this->csv([
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
                'Final Legacy With Payload',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '1',
                'ignored human summary',
                '{"payment_status":"unpaid"}',
                '2026-07-16 10:05:00',
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
            ->where('name', 'Final Legacy With Payload')
            ->firstOrFail();

        $this->assertSame(['payment_status' => 'unpaid'], $withPayloadView->filters);
    }

    public function test_final_duplicate_and_authenticated_user_scope_remain_locked(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'Final Scoped Duplicate',
            'filters' => ['payment_status' => 'unpaid'],
            'is_default' => true,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $otherUser->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'Final Cross User',
            'filters' => ['payment_status' => 'overdue'],
            'is_default' => true,
        ]);

        $csv = $this->csv([
            $this->versionOneHeader(),
            [
                '1',
                'Final Scoped Duplicate',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '1',
                'ignored',
                '{"payment_status":"paid"}',
                '2026-07-16 10:00:00',
            ],
            [
                '1',
                'Final Cross User',
                'تقرير أعمار ذمم فواتير المبيعات',
                'sales-invoice-aging',
                'no',
                '1',
                'ignored',
                '{"payment_status":"partial"}',
                '2026-07-16 10:05:00',
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
            ->where('name', 'Final Scoped Duplicate')
            ->firstOrFail();

        $this->assertSame(['payment_status' => 'unpaid'], $existing->filters);
        $this->assertTrue($existing->is_default);

        $created = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('name', 'Final Cross User')
            ->firstOrFail();

        $this->assertSame(['payment_status' => 'partial'], $created->filters);

        $other = ReportSavedView::query()
            ->where('user_id', $otherUser->id)
            ->where('name', 'Final Cross User')
            ->firstOrFail();

        $this->assertSame(['payment_status' => 'overdue'], $other->filters);
        $this->assertTrue($other->is_default);
    }

    public function test_final_source_state_locks_registry_backed_version_and_import_safety_markers(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $registry = file_get_contents(
            app_path('Support/Reports/ReportSavedViewImportExportVersionRegistry.php')
        );

        foreach ([
            'use App\Support\Reports\ReportSavedViewImportExportVersionRegistry;',
            'ReportSavedViewImportExportVersionRegistry::exportHeader()',
            'ReportSavedViewImportExportVersionRegistry::currentVersion()',
            'ReportSavedViewImportExportVersionRegistry::formatVersionColumn()',
            'ReportSavedViewImportExportVersionRegistry::legacyRequiredColumns()',
            'ReportSavedViewImportExportVersionRegistry::requiredColumns(',
            'ReportSavedViewImportExportVersionRegistry::supports($formatVersion)',
            'ReportSavedViewImportExportVersionRegistry::requiresFiltersPayload($formatVersion)',
            'private function decodeImportFiltersPayload(string $filtersPayload, array &$errors): array',
            'return DB::transaction(function () use ($request, $rows): array',
        ] as $marker) {
            $this->assertStringContainsString($marker, $controller);
        }

        foreach ([
            'final class ReportSavedViewImportExportVersionRegistry',
            "private const FORMAT_VERSION_COLUMN = 'format_version';",
            "private const CURRENT_VERSION = '1';",
            'public static function supports(string $version): bool',
            'public static function legacyRequiredColumns(): array',
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

    public function test_phase_73c_contract_locks_behavior_and_recommends_version_registry(): void
    {
        $contract = $this->contract();

        foreach ([
            'format_version_export_column_locked',
            'format_version_is_first_column_locked',
            'export_version_one_locked',
            'explicit_v1_requires_filters_payload_column_locked',
            'explicit_v1_requires_non_empty_filters_payload_locked',
            'explicit_v1_filters_payload_json_object_locked',
            'empty_explicit_version_rejected_locked',
            'unsupported_explicit_version_rejected_locked',
            'mixed_explicit_versions_rejected_locked',
            'legacy_unversioned_without_payload_supported_locked',
            'legacy_unversioned_without_payload_imports_empty_filters_locked',
            'legacy_unversioned_with_payload_supported_locked',
            'version_not_inferred_from_other_columns_locked',
            'filters_summary_human_readable_only_locked',
            'filters_payload_machine_source_locked',
            'versioned_lossless_round_trip_locked',
            'import_revalidation_before_writes_locked',
            'transaction_boundary_locked',
            'authenticated_user_scope_locked',
            'duplicate_skip_without_overwrite_locked',
            'default_normalization_per_user_report_locked',
            'preview_export_bulk_selection_pagination_preserved',
        ] as $key) {
            $this->assertTrue($contract['finalized_behavior'][$key], $key);
        }

        $this->assertSame($this->versionOneHeader(), $contract['current_export_schema']);
        $this->assertSame('1', $contract['current_explicit_version']);
        $this->assertSame(['1'], $contract['supported_explicit_versions']);
        $this->assertSame('absence_of_format_version_header', $contract['legacy_mode']);
        $this->assertSame('human_readable_only_not_parsed', $contract['filters_summary_policy']);
        $this->assertSame('filters_payload', $contract['machine_readable_filters_source']);
        $this->assertSame(
            'skip_existing_user_report_key_name_without_overwrite',
            $contract['duplicate_policy']
        );
        $this->assertSame('Phase 74A', $contract['next_recommendation']['phase']);
        $this->assertSame(
            'Saved View Import Export Version Registry Contract',
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
            file_get_contents(base_path('docs/phase-73c-saved-view-import-export-format-version-finalization.json')),
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
