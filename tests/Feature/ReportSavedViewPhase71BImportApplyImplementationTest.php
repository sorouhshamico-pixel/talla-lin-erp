<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ReportSavedViewPhase71BImportApplyImplementationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_71b_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-71b-saved-view-import-apply-implementation.json'));
        $this->assertFileExists(base_path('docs/phase-71b-saved-view-import-apply-implementation.md'));
    }

    public function test_valid_preview_shows_apply_form_with_payload(): void
    {
        $user = User::factory()->create();

        $csv = implode("\n", [
            'name,report_label,report_key,is_default,filter_count,filters_summary,updated_at',
            'Imported Profit,تقرير الأرباح والخسائر,profit-loss,yes,1,حالة الدفع: مدفوعة بالكامل (paid),2026-07-15 10:00:00',
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-views.import-preview'), [
                'csv_file' => UploadedFile::fake()->createWithContent('saved-views.csv', $csv),
            ])
            ->assertOk()
            ->assertSee('data-testid="report-saved-views-import-apply-form"', false)
            ->assertSee('data-testid="report-saved-views-import-apply-payload"', false)
            ->assertSee('data-testid="report-saved-views-import-apply-warning"', false)
            ->assertSee('data-testid="report-saved-views-import-apply-button"', false)
            ->assertSee('تطبيق الاستيراد');

        $this->assertSame(0, ReportSavedView::query()->count());
    }

    public function test_invalid_preview_does_not_show_apply_form(): void
    {
        $user = User::factory()->create();

        $csv = implode("\n", [
            'name,report_label,report_key,is_default,filter_count,filters_summary,updated_at',
            ',Unknown,not-a-report,maybe,abc,Invalid filters,2026-07-15 10:00:00',
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-views.import-preview'), [
                'csv_file' => UploadedFile::fake()->createWithContent('saved-views.csv', $csv),
            ])
            ->assertOk()
            ->assertSee('غير صالحة: 1')
            ->assertDontSee('data-testid="report-saved-views-import-apply-form"', false)
            ->assertDontSee('تطبيق الاستيراد');

        $this->assertSame(0, ReportSavedView::query()->count());
    }

    public function test_import_apply_creates_valid_rows_skips_duplicates_and_normalizes_default(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'Existing Profit',
            'filters' => ['payment_status' => 'paid'],
            'is_default' => true,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $otherUser->id,
            'report_key' => 'profit-loss',
            'name' => 'Imported Profit',
            'filters' => ['payment_status' => 'unpaid'],
            'is_default' => true,
        ]);

        $csv = implode("\n", [
            'name,report_label,report_key,is_default,filter_count,filters_summary,updated_at',
            'Imported Profit,تقرير الأرباح والخسائر,profit-loss,yes,1,حالة الدفع: مدفوعة بالكامل (paid),2026-07-15 10:00:00',
            'Existing Profit,تقرير الأرباح والخسائر,profit-loss,no,0,,2026-07-15 10:05:00',
            'Imported Aging,تقرير أعمار ذمم فواتير المبيعات,sales-invoice-aging,no,0,,2026-07-15 10:10:00',
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-views.import-apply'), [
                'csv_payload' => base64_encode($csv),
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas('status', 'تم تطبيق الاستيراد: تم إنشاء 2 عرض محفوظ، وتم تخطي 1 مكرر.');

        $this->assertTrue(ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('report_key', 'profit-loss')
            ->where('name', 'Imported Profit')
            ->where('is_default', true)
            ->exists());

        $this->assertTrue(ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('report_key', 'profit-loss')
            ->where('name', 'Existing Profit')
            ->where('is_default', false)
            ->exists());

        $this->assertTrue(ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('report_key', 'sales-invoice-aging')
            ->where('name', 'Imported Aging')
            ->where('is_default', false)
            ->exists());

        $this->assertSame(1, ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('report_key', 'profit-loss')
            ->where('is_default', true)
            ->count());

        $this->assertTrue(ReportSavedView::query()
            ->where('user_id', $otherUser->id)
            ->where('report_key', 'profit-loss')
            ->where('name', 'Imported Profit')
            ->where('is_default', true)
            ->exists());

        $imported = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('name', 'Imported Profit')
            ->firstOrFail();

        $this->assertSame([], $imported->filters);
    }

    public function test_import_apply_rejects_invalid_payload_without_writes(): void
    {
        $user = User::factory()->create();

        $csv = implode("\n", [
            'name,report_label,report_key,is_default,filter_count,filters_summary,updated_at',
            ',Unknown,not-a-report,maybe,abc,Invalid filters,2026-07-15 10:00:00',
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-views.import-apply'), [
                'csv_payload' => base64_encode($csv),
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas('status', 'لم يتم تطبيق الاستيراد بسبب وجود أخطاء في الملف.');

        $this->assertSame(0, ReportSavedView::query()->count());
    }

    public function test_import_apply_requires_authentication(): void
    {
        $csv = implode("\n", [
            'name,report_label,report_key,is_default,filter_count,filters_summary,updated_at',
            'Imported Profit,تقرير الأرباح والخسائر,profit-loss,yes,0,,2026-07-15 10:00:00',
        ]);

        $this->post(route('reports.saved-views.import-apply'), [
            'csv_payload' => base64_encode($csv),
        ])->assertRedirect(route('login'));

        $this->assertSame(0, ReportSavedView::query()->count());
    }

    public function test_phase_71b_source_contains_route_controller_and_view_markers(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            "Route::post('/reports/saved-views/import-apply'",
            'reports.saved-views.import-apply',
        ] as $routeMarker) {
            $this->assertStringContainsString($routeMarker, $routes);
        }

        foreach ([
            'public function applyImport(Request $request): RedirectResponse',
            "'csv_payload' => ['required', 'string']",
            'base64_decode((string) $validated',
            '$this->csvImportParser->parse($tempPath)',
            '$this->importApplyService->apply($request->user(), $preview',
            'private readonly ReportSavedViewImportApplyService $importApplyService',
            '$this->importApplyService->apply(',
            '$this->importApplyService->apply(',
        ] as $controllerMarker) {
            $this->assertStringContainsString($controllerMarker, $controller);
        }

        foreach ([
            "route('reports.saved-views.import-apply')",
            'data-testid="report-saved-views-import-apply-form"',
            'data-testid="report-saved-views-import-apply-payload"',
            'data-testid="report-saved-views-import-apply-warning"',
            'data-testid="report-saved-views-import-apply-button"',
            'تطبيق الاستيراد',
        ] as $viewMarker) {
            $this->assertStringContainsString($viewMarker, $view);
        }
    }

    public function test_phase_71b_preserves_preview_export_bulk_selection_and_pagination_markers(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $service = file_get_contents(app_path('Services/ReportSavedViewService.php'));
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            'reports.saved-views.import-preview',
            'reports.saved-views.export',
            'reports.saved-views.bulk-destroy',
            'reports.saved-views.destroy-all',
        ] as $routeMarker) {
            $this->assertStringContainsString($routeMarker, $routes);
        }

        foreach ([
            'public function previewImport(Request $request, ReportSavedViewService $savedViewService): View',
            'public function export(Request $request, ReportSavedViewService $savedViewService): StreamedResponse',
            'public function bulkDestroy(Request $request): RedirectResponse',
            'private function managementReturnQuery(Request $request): array',
        ] as $controllerMarker) {
            $this->assertStringContainsString($controllerMarker, $controller);
        }

        foreach ([
            'public function paginateForManagement(',
            'public function exportForManagement(',
            '->paginate($perPage)',
            '->withQueryString()',
        ] as $serviceMarker) {
            $this->assertStringContainsString($serviceMarker, $service);
        }

        foreach ([
            'data-testid="report-saved-views-import-preview-form"',
            'data-testid="report-saved-views-export-link"',
            'data-testid="report-saved-views-search-form"',
            'data-testid="report-saved-views-per-page-select"',
            'data-testid="report-saved-views-pagination"',
            'data-testid="report-saved-views-bulk-action-form"',
            'data-testid="report-saved-views-bulk-delete-button"',
            'data-testid="report-saved-views-selected-count"',
        ] as $viewMarker) {
            $this->assertStringContainsString($viewMarker, $view);
        }
    }

    public function test_phase_71b_json_contract_documents_import_apply_implementation(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-71b-saved-view-import-apply-implementation.json')),
            true
        );

        $this->assertSame('Phase 71B', $contract['phase']);
        $this->assertSame('Phase 71A clean', $contract['baseline']['phase']);
        $this->assertSame('7dbfea6', $contract['baseline']['commit']);
        $this->assertSame('1380 passed / 12330 assertions', $contract['baseline']['previous_tests']);

        foreach ([
            'import_apply_route_added',
            'import_apply_controller_action_added',
            'import_apply_form_added_after_valid_preview',
            'apply_requires_authentication',
            'apply_revalidates_payload_before_writes',
            'apply_rejects_invalid_payload_without_writes',
            'apply_uses_database_transaction',
            'apply_creates_saved_views_for_authenticated_user_only',
            'apply_skips_duplicate_user_report_name_pairs',
            'apply_does_not_overwrite_existing_saved_views',
            'apply_normalizes_single_default_per_report',
            'apply_imports_empty_filters_only',
            'phase_70_preview_preserved',
            'phase_69_export_preserved',
            'phase_68_bulk_selection_preserved',
            'phase_67_pagination_preserved',
        ] as $key) {
            $this->assertTrue($contract['implemented_behavior'][$key], $key);
        }

        $this->assertSame('skip_existing_user_report_key_name', $contract['duplicate_policy']);
        $this->assertStringContainsString('filters_summary is not machine-readable', $contract['filters_policy']);
        $this->assertNotEmpty($contract['guardrails']);
    }
}
