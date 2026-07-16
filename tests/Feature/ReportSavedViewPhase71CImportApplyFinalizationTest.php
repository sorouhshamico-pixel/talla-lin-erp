<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ReportSavedViewPhase71CImportApplyFinalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_71c_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-71c-saved-view-import-apply-finalization.json'));
        $this->assertFileExists(base_path('docs/phase-71c-saved-view-import-apply-finalization.md'));
    }

    public function test_phase_71c_contract_marks_finalization_without_implementation_changes(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-71c-saved-view-import-apply-finalization.json')),
            true
        );

        $this->assertSame('Phase 71C', $contract['phase']);
        $this->assertSame('Phase 71B clean', $contract['baseline']['phase']);
        $this->assertSame('7f35cea', $contract['baseline']['commit']);
        $this->assertSame('1389 passed / 12396 assertions', $contract['baseline']['previous_tests']);
        $this->assertSame('finalization', $contract['scope']['type']);
        $this->assertFalse($contract['scope']['implementation_changes_expected']);

        foreach ([
            'routes/web.php',
            'app/Http/Controllers/ReportSavedViewController.php',
            'resources/views/reports/saved-views/index.blade.php',
            'app/Services/ReportSavedViewService.php',
            'resources/views/reports/saved-views/edit.blade.php',
            'app/Models/ReportSavedView.php',
            'app/Support/Reports/ReportSavedViewRegistry.php',
        ] as $lockedFile) {
            $this->assertContains($lockedFile, $contract['scope']['locked_implementation_files']);
        }
    }

    public function test_final_source_state_contains_import_apply_route_controller_and_view_contracts(): void
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
            'applySavedViewImportRows($request, $preview',
            'private function applySavedViewImportRows(Request $request, array $rows): array',
            'return DB::transaction(function () use ($request, $rows): array',
            "'filters' => \$row['filters'] ?? []",
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

    public function test_final_import_apply_creates_rows_skips_duplicates_and_preserves_existing_records(): void
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

        $this->assertSame(4, ReportSavedView::query()->count());

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
            ->whereJsonContains('filters->payment_status', 'paid')
            ->exists());

        $this->assertTrue(ReportSavedView::query()
            ->where('user_id', $otherUser->id)
            ->where('report_key', 'profit-loss')
            ->where('name', 'Imported Profit')
            ->where('is_default', true)
            ->whereJsonContains('filters->payment_status', 'unpaid')
            ->exists());

        $this->assertSame(1, ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('report_key', 'profit-loss')
            ->where('is_default', true)
            ->count());

        $imported = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('name', 'Imported Profit')
            ->firstOrFail();

        $this->assertSame([], $imported->filters);
    }

    public function test_final_import_apply_rejects_invalid_payload_without_writes(): void
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

    public function test_final_valid_preview_still_shows_apply_form_and_does_not_write_until_apply(): void
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
            ->assertSee('data-testid="report-saved-views-import-preview-card"', false)
            ->assertSee('data-testid="report-saved-views-import-apply-form"', false)
            ->assertSee('data-testid="report-saved-views-import-apply-button"', false)
            ->assertSee('Imported Profit')
            ->assertSee('صالحة: 1');

        $this->assertSame(0, ReportSavedView::query()->count());
    }

    public function test_final_import_apply_requires_authentication(): void
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

    public function test_final_preview_export_bulk_selection_and_pagination_remain_locked(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $service = file_get_contents(app_path('Services/ReportSavedViewService.php'));
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            'reports.saved-views.import-preview',
            'reports.saved-views.import-apply',
            'reports.saved-views.export',
            'reports.saved-views.bulk-destroy',
            'reports.saved-views.destroy-all',
        ] as $routeMarker) {
            $this->assertStringContainsString($routeMarker, $routes);
        }

        foreach ([
            'public function previewImport(Request $request, ReportSavedViewService $savedViewService): View',
            'public function applyImport(Request $request): RedirectResponse',
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
            'data-testid="report-saved-views-import-apply-form"',
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

    public function test_phase_71c_json_contract_documents_finalized_behavior_and_next_recommendation(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-71c-saved-view-import-apply-finalization.json')),
            true
        );

        foreach ([
            'import_apply_route_locked',
            'import_apply_controller_action_locked',
            'import_apply_form_locked',
            'apply_requires_authentication_locked',
            'apply_revalidates_payload_before_writes_locked',
            'apply_rejects_invalid_payload_without_writes_locked',
            'apply_uses_database_transaction_locked',
            'apply_creates_for_authenticated_user_only_locked',
            'apply_skips_duplicates_locked',
            'apply_does_not_overwrite_existing_locked',
            'apply_normalizes_default_per_report_locked',
            'apply_imports_empty_filters_only_locked',
            'phase_70_preview_preserved',
            'phase_69_export_preserved',
            'phase_68_bulk_selection_preserved',
            'phase_67_pagination_preserved',
        ] as $key) {
            $this->assertTrue($contract['finalized_behavior'][$key], $key);
        }

        $this->assertSame('skip_existing_user_report_key_name', $contract['duplicate_policy']);
        $this->assertStringContainsString('filters_summary remains human-readable only', $contract['filters_policy']);
        $this->assertSame('Phase 72A', $contract['next_recommendation']['phase']);
        $this->assertSame('Saved View Machine-Readable Filters Payload Contract', $contract['next_recommendation']['title']);
        $this->assertNotEmpty($contract['guardrails']);
    }
}
