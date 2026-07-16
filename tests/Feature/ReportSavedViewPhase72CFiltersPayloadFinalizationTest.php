<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase72CFiltersPayloadFinalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_72c_finalization_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-72c-saved-view-filters-payload-finalization.json'));
        $this->assertFileExists(base_path('docs/phase-72c-saved-view-filters-payload-finalization.md'));
    }

    public function test_phase_72c_contract_marks_finalization_without_implementation_changes(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-72c-saved-view-filters-payload-finalization.json')),
            true
        );

        $this->assertSame('Phase 72C', $contract['phase']);
        $this->assertSame('Phase 72B clean', $contract['baseline']['phase']);
        $this->assertSame('e1ad866', $contract['baseline']['commit']);
        $this->assertSame('1415 passed / 12637 assertions', $contract['baseline']['previous_tests']);
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

    public function test_final_export_import_round_trip_preserves_machine_readable_filters(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'Round Trip Aging',
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
        $rows = $this->csvRows($csv);

        $this->assertGreaterThanOrEqual(2, count($rows));
        $this->assertContains('filters_summary', $rows[0]);
        $this->assertContains('filters_payload', $rows[0]);

        $record = array_combine($rows[0], $rows[1]);

        $this->assertIsArray($record);
        $this->assertSame([
            'payment_status' => 'partial',
            'aging_bucket' => 'overdue_31_60',
        ], json_decode($record['filters_payload'], true));

        ReportSavedView::query()
            ->where('user_id', $user->id)
            ->delete();

        $this->actingAs($user)
            ->post(route('reports.saved-views.import-apply'), [
                'csv_payload' => base64_encode($csv),
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas('status', 'تم تطبيق الاستيراد: تم إنشاء 1 عرض محفوظ، وتم تخطي 0 مكرر.');

        $imported = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('report_key', 'sales-invoice-aging')
            ->where('name', 'Round Trip Aging')
            ->firstOrFail();

        $this->assertSame([
            'payment_status' => 'partial',
            'aging_bucket' => 'overdue_31_60',
        ], $imported->filters);
        $this->assertTrue($imported->is_default);
    }

    public function test_final_legacy_csv_without_filters_payload_remains_supported(): void
    {
        $user = User::factory()->create();

        $csv = $this->csv([
            ['name', 'report_label', 'report_key', 'is_default', 'filter_count', 'filters_summary', 'updated_at'],
            [
                'Legacy Final View',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '1',
                'حالة الدفع: مدفوعة بالكامل (paid)',
                '2026-07-16 08:00:00',
            ],
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-views.import-apply'), [
                'csv_payload' => base64_encode($csv),
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas('status', 'تم تطبيق الاستيراد: تم إنشاء 1 عرض محفوظ، وتم تخطي 0 مكرر.');

        $savedView = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('name', 'Legacy Final View')
            ->firstOrFail();

        $this->assertSame([], $savedView->filters);
    }

    public function test_final_invalid_or_list_filters_payload_blocks_all_writes(): void
    {
        $user = User::factory()->create();

        foreach ([
            '{"payment_status":',
            json_encode(['paid'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ] as $index => $filtersPayload) {
            $csv = $this->csv([
                ['name', 'report_label', 'report_key', 'is_default', 'filter_count', 'filters_summary', 'filters_payload', 'updated_at'],
                [
                    'Invalid Final View ' . $index,
                    'تقرير الأرباح والخسائر',
                    'profit-loss',
                    'no',
                    '1',
                    'ignored human summary',
                    $filtersPayload,
                    '2026-07-16 08:00:00',
                ],
            ]);

            $this->actingAs($user)
                ->post(route('reports.saved-views.import-apply'), [
                    'csv_payload' => base64_encode($csv),
                ])
                ->assertRedirect(route('reports.saved-views.index'))
                ->assertSessionHas('status', 'لم يتم تطبيق الاستيراد بسبب وجود أخطاء في الملف.');
        }

        $this->assertSame(0, ReportSavedView::query()->count());
    }

    public function test_final_duplicate_policy_skips_without_overwrite_and_keeps_user_scope(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'Scoped Duplicate',
            'filters' => ['payment_status' => 'unpaid'],
            'is_default' => true,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $otherUser->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'Cross User Name',
            'filters' => ['payment_status' => 'overdue'],
            'is_default' => true,
        ]);

        $csv = $this->csv([
            ['name', 'report_label', 'report_key', 'is_default', 'filter_count', 'filters_summary', 'filters_payload', 'updated_at'],
            [
                'Scoped Duplicate',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '1',
                'ignored summary',
                json_encode(['payment_status' => 'paid'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                '2026-07-16 08:00:00',
            ],
            [
                'Cross User Name',
                'تقرير أعمار ذمم فواتير المبيعات',
                'sales-invoice-aging',
                'no',
                '1',
                'ignored summary',
                json_encode(['payment_status' => 'partial'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                '2026-07-16 08:05:00',
            ],
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-views.import-apply'), [
                'csv_payload' => base64_encode($csv),
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas('status', 'تم تطبيق الاستيراد: تم إنشاء 1 عرض محفوظ، وتم تخطي 1 مكرر.');

        $existing = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('report_key', 'profit-loss')
            ->where('name', 'Scoped Duplicate')
            ->firstOrFail();

        $this->assertSame(['payment_status' => 'unpaid'], $existing->filters);
        $this->assertTrue($existing->is_default);

        $created = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('report_key', 'sales-invoice-aging')
            ->where('name', 'Cross User Name')
            ->firstOrFail();

        $this->assertSame(['payment_status' => 'partial'], $created->filters);

        $other = ReportSavedView::query()
            ->where('user_id', $otherUser->id)
            ->where('report_key', 'sales-invoice-aging')
            ->where('name', 'Cross User Name')
            ->firstOrFail();

        $this->assertSame(['payment_status' => 'overdue'], $other->filters);
        $this->assertTrue($other->is_default);
    }

    public function test_final_source_state_locks_filters_payload_contract_and_transaction_boundary(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));

        foreach ([
            "'filters_summary'",
            "'filters_payload'",
            '$filtersPayload = json_encode((object) ($savedView->filters ?? []',
            'json_decode($filtersPayload)',
            'private function decodeImportFiltersPayload(string $filtersPayload, array &$errors): array',
            'private function cleanImportedFilters(array $filters): array',
            "'filters' => \$row['filters'] ?? []",
            'return DB::transaction(function () use ($request, $rows): array',
        ] as $marker) {
            $this->assertStringContainsString($marker, $controller);
        }

        $this->assertStringNotContainsString('json_decode($data[\'filters_summary\']', $controller);
        $this->assertStringNotContainsString('parseFiltersSummary', $controller);
    }

    public function test_phase_72c_json_contract_documents_finalized_behavior_and_next_recommendation(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-72c-saved-view-filters-payload-finalization.json')),
            true
        );

        foreach ([
            'filters_payload_export_column_locked',
            'filters_payload_json_object_format_locked',
            'empty_filters_export_as_object_locked',
            'filters_summary_human_readable_only_locked',
            'filters_payload_only_machine_import_source_locked',
            'legacy_csv_without_payload_supported_locked',
            'legacy_csv_imports_empty_filters_locked',
            'invalid_json_blocks_writes_locked',
            'json_list_blocks_writes_locked',
            'import_transaction_boundary_locked',
            'authenticated_user_scope_locked',
            'duplicate_skip_without_overwrite_locked',
            'lossless_round_trip_locked',
            'phase_71_import_apply_preserved',
            'phase_70_import_preview_preserved',
            'phase_69_csv_export_preserved',
            'phase_68_bulk_selection_preserved',
            'phase_67_pagination_preserved',
        ] as $key) {
            $this->assertTrue($contract['finalized_behavior'][$key], $key);
        }

        $this->assertSame('filters_payload', $contract['machine_readable_filters_source']);
        $this->assertSame('human_readable_only_not_parsed', $contract['filters_summary_policy']);
        $this->assertSame('skip_existing_user_report_key_name_without_overwrite', $contract['duplicate_policy']);
        $this->assertSame('Phase 73A', $contract['next_recommendation']['phase']);
        $this->assertSame('Saved View Import Export Format Version Contract', $contract['next_recommendation']['title']);
        $this->assertNotEmpty($contract['guardrails']);
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
            array_values(array_filter($lines, fn (?string $line): bool => $line !== null && $line !== ''))
        );
    }
}
