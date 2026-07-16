<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase72BFiltersPayloadImplementationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_72b_implementation_docs_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-72b-saved-view-filters-payload-implementation.json'));
        $this->assertFileExists(base_path('docs/phase-72b-saved-view-filters-payload-implementation.md'));
    }

    public function test_export_includes_machine_readable_filters_payload_column(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'Aging Paid Overdue',
            'filters' => [
                'payment_status' => 'paid',
                'aging_bucket' => 'overdue_1_30',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.saved-views.export'))
            ->assertOk();

        $rows = $this->csvRows($response->streamedContent());

        $this->assertGreaterThanOrEqual(2, count($rows));
        $this->assertContains('filters_summary', $rows[0]);
        $this->assertContains('filters_payload', $rows[0]);

        $record = array_combine($rows[0], $rows[1]);

        $this->assertIsArray($record);
        $this->assertSame('Aging Paid Overdue', $record['name']);

        $payload = json_decode($record['filters_payload'], true);

        $this->assertSame([
            'payment_status' => 'paid',
            'aging_bucket' => 'overdue_1_30',
        ], $payload);
    }

    public function test_import_apply_uses_filters_payload_and_ignores_filters_summary_for_machine_values(): void
    {
        $user = User::factory()->create();

        $csv = $this->csv([
            ['name', 'report_label', 'report_key', 'is_default', 'filter_count', 'filters_summary', 'filters_payload', 'updated_at'],
            [
                'Imported Payload View',
                'تقرير أعمار ذمم فواتير المبيعات',
                'sales-invoice-aging',
                'yes',
                '1',
                'حالة الدفع: غير مدفوعة (unpaid)',
                json_encode(['payment_status' => 'paid'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                '2026-07-15 10:00:00',
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
            ->where('report_key', 'sales-invoice-aging')
            ->where('name', 'Imported Payload View')
            ->firstOrFail();

        $this->assertSame(['payment_status' => 'paid'], $savedView->filters);
        $this->assertTrue($savedView->is_default);
    }

    public function test_old_csv_without_filters_payload_remains_supported_and_imports_empty_filters(): void
    {
        $user = User::factory()->create();

        $csv = $this->csv([
            ['name', 'report_label', 'report_key', 'is_default', 'filter_count', 'filters_summary', 'updated_at'],
            [
                'Legacy Imported View',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '1',
                'حالة الدفع: مدفوعة بالكامل (paid)',
                '2026-07-15 10:00:00',
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
            ->where('report_key', 'profit-loss')
            ->where('name', 'Legacy Imported View')
            ->firstOrFail();

        $this->assertSame([], $savedView->filters);
    }

    public function test_export_empty_filters_payload_imports_back_as_empty_filters(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'No Filters',
            'filters' => [],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.saved-views.export'))
            ->assertOk();

        $rows = $this->csvRows($response->streamedContent());
        $record = array_combine($rows[0], $rows[1]);

        $this->assertIsArray($record);
        $this->assertSame('{}', $record['filters_payload']);
    }

    public function test_invalid_filters_payload_rejects_apply_without_writes(): void
    {
        $user = User::factory()->create();

        $csv = $this->csv([
            ['name', 'report_label', 'report_key', 'is_default', 'filter_count', 'filters_summary', 'filters_payload', 'updated_at'],
            [
                'Invalid Payload View',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '1',
                'ignored summary',
                '{"payment_status":',
                '2026-07-15 10:00:00',
            ],
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-views.import-apply'), [
                'csv_payload' => base64_encode($csv),
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas('status', 'لم يتم تطبيق الاستيراد بسبب وجود أخطاء في الملف.');

        $this->assertSame(0, ReportSavedView::query()->count());
    }

    public function test_filters_payload_must_be_json_object_not_json_list(): void
    {
        $user = User::factory()->create();

        $csv = $this->csv([
            ['name', 'report_label', 'report_key', 'is_default', 'filter_count', 'filters_summary', 'filters_payload', 'updated_at'],
            [
                'List Payload View',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '1',
                'ignored summary',
                json_encode(['paid'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                '2026-07-15 10:00:00',
            ],
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-views.import-apply'), [
                'csv_payload' => base64_encode($csv),
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas('status', 'لم يتم تطبيق الاستيراد بسبب وجود أخطاء في الملف.');

        $this->assertSame(0, ReportSavedView::query()->count());
    }

    public function test_duplicate_import_skips_without_overwrite(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'Existing Profit',
            'filters' => ['payment_status' => 'unpaid'],
            'is_default' => true,
        ]);

        $csv = $this->csv([
            ['name', 'report_label', 'report_key', 'is_default', 'filter_count', 'filters_summary', 'filters_payload', 'updated_at'],
            [
                'Existing Profit',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '1',
                'ignored summary',
                json_encode(['payment_status' => 'paid'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                '2026-07-15 10:00:00',
            ],
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-views.import-apply'), [
                'csv_payload' => base64_encode($csv),
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas('status', 'تم تطبيق الاستيراد: تم إنشاء 0 عرض محفوظ، وتم تخطي 1 مكرر.');

        $savedView = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('report_key', 'profit-loss')
            ->where('name', 'Existing Profit')
            ->firstOrFail();

        $this->assertSame(['payment_status' => 'unpaid'], $savedView->filters);
        $this->assertTrue($savedView->is_default);
    }

    public function test_source_contains_filters_payload_import_export_markers(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));

        foreach ([
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
