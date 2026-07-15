<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ReportSavedViewPhase70CImportPreviewFinalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_70c_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-70c-saved-view-management-import-preview-finalization.json'));
        $this->assertFileExists(base_path('docs/phase-70c-saved-view-management-import-preview-finalization.md'));
    }

    public function test_phase_70c_contract_marks_finalization_without_implementation_changes(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-70c-saved-view-management-import-preview-finalization.json')),
            true
        );

        $this->assertSame('Phase 70C', $contract['phase']);
        $this->assertSame('Phase 70B clean', $contract['baseline']['phase']);
        $this->assertSame('219e45c', $contract['baseline']['commit']);
        $this->assertSame('1364 passed / 12121 assertions', $contract['baseline']['previous_tests']);
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

    public function test_final_source_state_contains_import_preview_route_controller_and_view_contracts(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            "Route::post('/reports/saved-views/import-preview'",
            'reports.saved-views.import-preview',
        ] as $routeMarker) {
            $this->assertStringContainsString($routeMarker, $routes);
        }

        foreach ([
            'private const IMPORT_PREVIEW_REQUIRED_COLUMNS',
            'public function previewImport(Request $request, ReportSavedViewService $savedViewService): View',
            "'csv_file' => ['required', 'file', 'max:2048']",
            'private function previewSavedViewImport(string $path): array',
            'private function isEmptyCsvRow(array $row): bool',
            'ReportSavedViewRegistry::has($reportKey)',
            "'status' => \$errors === [] ? 'valid' : 'invalid'",
        ] as $controllerMarker) {
            $this->assertStringContainsString($controllerMarker, $controller);
        }

        foreach ([
            "route('reports.saved-views.import-preview')",
            'data-testid="report-saved-views-import-preview-form"',
            'data-testid="report-saved-views-import-file-input"',
            'data-testid="report-saved-views-import-preview-button"',
            'data-testid="report-saved-views-import-preview-card"',
            'data-testid="report-saved-views-import-preview-summary"',
            'data-testid="report-saved-views-import-preview-table"',
            'data-testid="report-saved-views-import-preview-row"',
            'data-testid="report-saved-views-import-row-valid"',
            'data-testid="report-saved-views-import-row-invalid"',
        ] as $viewMarker) {
            $this->assertStringContainsString($viewMarker, $view);
        }
    }

    public function test_final_import_preview_is_read_only_for_valid_rows(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'existing saved view',
            'filters' => [],
            'is_default' => false,
        ]);

        $csv = implode("\n", [
            'name,report_label,report_key,is_default,filter_count,filters_summary,updated_at',
            'Imported Profit,تقرير الأرباح والخسائر,profit-loss,yes,1,حالة الدفع: مدفوعة بالكامل (paid),2026-07-15 10:00:00',
            'Imported Aging,تقرير أعمار ذمم فواتير المبيعات,sales-invoice-aging,no,0,,2026-07-15 10:05:00',
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-views.import-preview'), [
                'csv_file' => UploadedFile::fake()->createWithContent('saved-views.csv', $csv),
            ])
            ->assertOk()
            ->assertSee('data-testid="report-saved-views-import-preview-card"', false)
            ->assertSee('إجمالي الصفوف: 2')
            ->assertSee('صالحة: 2')
            ->assertSee('غير صالحة: 0')
            ->assertSee('Imported Profit')
            ->assertSee('Imported Aging');

        $this->assertSame(1, ReportSavedView::query()->count());
        $this->assertFalse(ReportSavedView::query()->where('name', 'Imported Profit')->exists());
        $this->assertFalse(ReportSavedView::query()->where('name', 'Imported Aging')->exists());
    }

    public function test_final_import_preview_rejects_invalid_rows_and_remains_read_only(): void
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
            ->assertSee('اسم العرض مطلوب')
            ->assertSee('مفتاح التقرير غير معروف')
            ->assertSee('قيمة الافتراضي غير صالحة')
            ->assertSee('عدد الفلاتر يجب أن يكون رقمًا صحيحًا');

        $this->assertSame(0, ReportSavedView::query()->count());
    }

    public function test_final_import_preview_rejects_missing_headers_and_remains_read_only(): void
    {
        $user = User::factory()->create();

        $csv = implode("\n", [
            'name,report_key',
            'Imported Profit,profit-loss',
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-views.import-preview'), [
                'csv_file' => UploadedFile::fake()->createWithContent('saved-views.csv', $csv),
            ])
            ->assertOk()
            ->assertSee('data-testid="report-saved-views-import-header-errors"', false)
            ->assertSee('الأعمدة المطلوبة غير موجودة')
            ->assertSee('report_label')
            ->assertSee('filters_summary');

        $this->assertSame(0, ReportSavedView::query()->count());
    }

    public function test_final_import_preview_requires_authentication_and_phase_70c_contract_remains_historical(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-70c-saved-view-management-import-preview-finalization.json')),
            true
        );

        $this->assertTrue($contract['finalized_behavior']['write_capable_import_route_absent']);

        $csv = implode("\n", [
            'name,report_label,report_key,is_default,filter_count,filters_summary,updated_at',
            'Imported Profit,تقرير الأرباح والخسائر,profit-loss,yes,0,,2026-07-15 10:00:00',
        ]);

        $this->post(route('reports.saved-views.import-preview'), [
            'csv_file' => UploadedFile::fake()->createWithContent('saved-views.csv', $csv),
        ])->assertRedirect(route('login'));
    }

    public function test_final_phase_69_export_phase_68_bulk_selection_and_phase_67_pagination_remain_locked(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $service = file_get_contents(app_path('Services/ReportSavedViewService.php'));
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            'reports.saved-views.export',
            'reports.saved-views.bulk-destroy',
            'reports.saved-views.destroy-all',
        ] as $routeMarker) {
            $this->assertStringContainsString($routeMarker, $routes);
        }

        foreach ([
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

    public function test_phase_70c_json_contract_documents_finalized_behavior_and_next_recommendation(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-70c-saved-view-management-import-preview-finalization.json')),
            true
        );

        foreach ([
            'import_preview_route_locked',
            'import_preview_controller_action_locked',
            'import_preview_form_locked',
            'import_preview_panel_locked',
            'csv_header_validation_locked',
            'row_level_validation_locked',
            'unknown_report_key_validation_locked',
            'preview_requires_authentication_locked',
            'preview_is_read_only_locked',
            'write_capable_import_route_absent',
            'phase_69_export_preserved',
            'phase_68_bulk_selection_preserved',
            'phase_67_management_pagination_preserved',
        ] as $key) {
            $this->assertTrue($contract['finalized_behavior'][$key], $key);
        }

        foreach ([
            'name',
            'report_label',
            'report_key',
            'is_default',
            'filter_count',
            'filters_summary',
            'updated_at',
        ] as $column) {
            $this->assertContains($column, $contract['required_csv_columns']);
        }

        $this->assertSame('Phase 71A', $contract['next_recommendation']['phase']);
        $this->assertSame('Saved View Management Import Apply Contract', $contract['next_recommendation']['title']);
        $this->assertNotEmpty($contract['guardrails']);
    }
}
