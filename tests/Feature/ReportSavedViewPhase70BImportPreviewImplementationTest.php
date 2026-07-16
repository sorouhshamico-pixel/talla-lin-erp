<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ReportSavedViewPhase70BImportPreviewImplementationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_70b_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-70b-saved-view-management-import-preview-implementation.json'));
        $this->assertFileExists(base_path('docs/phase-70b-saved-view-management-import-preview-implementation.md'));
    }

    public function test_management_page_renders_import_preview_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.saved-views.index'))
            ->assertOk()
            ->assertSee('data-testid="report-saved-views-import-preview-form"', false)
            ->assertSee('data-testid="report-saved-views-import-file-input"', false)
            ->assertSee('data-testid="report-saved-views-import-preview-button"', false)
            ->assertSee(route('reports.saved-views.import-preview'), false)
            ->assertSee('معاينة الاستيراد');
    }

    public function test_import_preview_parses_valid_rows_without_writing_database_records(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'existing',
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
            ->assertSee('data-testid="report-saved-views-import-preview-summary"', false)
            ->assertSee('إجمالي الصفوف: 2')
            ->assertSee('صالحة: 2')
            ->assertSee('غير صالحة: 0')
            ->assertSee('Imported Profit')
            ->assertSee('Imported Aging')
            ->assertSee('data-testid="report-saved-views-import-row-valid"', false);

        $this->assertSame(1, ReportSavedView::query()->count());
        $this->assertFalse(ReportSavedView::query()->where('name', 'Imported Profit')->exists());
        $this->assertFalse(ReportSavedView::query()->where('name', 'Imported Aging')->exists());
    }

    public function test_import_preview_reports_invalid_rows_without_writing_database_records(): void
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
            ->assertSee('data-testid="report-saved-views-import-row-invalid"', false)
            ->assertSee('اسم العرض مطلوب')
            ->assertSee('مفتاح التقرير غير معروف')
            ->assertSee('قيمة الافتراضي غير صالحة')
            ->assertSee('عدد الفلاتر يجب أن يكون رقمًا صحيحًا');

        $this->assertSame(0, ReportSavedView::query()->count());
    }

    public function test_import_preview_reports_missing_headers_without_writing_database_records(): void
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

    public function test_import_preview_requires_authentication(): void
    {
        $csv = implode("\n", [
            'name,report_label,report_key,is_default,filter_count,filters_summary,updated_at',
            'Imported Profit,تقرير الأرباح والخسائر,profit-loss,yes,0,,2026-07-15 10:00:00',
        ]);

        $this->post(route('reports.saved-views.import-preview'), [
            'csv_file' => UploadedFile::fake()->createWithContent('saved-views.csv', $csv),
        ])->assertRedirect(route('login'));
    }

    public function test_phase_70b_source_contains_import_preview_route_controller_and_view_markers(): void
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
            'ReportSavedViewImportExportVersionRegistry::legacyRequiredColumns()',
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
        ] as $viewMarker) {
            $this->assertStringContainsString($viewMarker, $view);
        }
    }

    public function test_phase_70b_preserves_export_bulk_selection_and_pagination_markers(): void
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

    public function test_phase_70b_json_contract_documents_import_preview_implementation(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-70b-saved-view-management-import-preview-implementation.json')),
            true
        );

        $this->assertSame('Phase 70B', $contract['phase']);
        $this->assertSame('Phase 70A clean', $contract['baseline']['phase']);
        $this->assertSame('99f4022', $contract['baseline']['commit']);
        $this->assertSame('1355 passed / 12043 assertions', $contract['baseline']['previous_tests']);

        foreach ([
            'import_preview_route_added',
            'import_preview_controller_action_added',
            'import_preview_form_added',
            'csv_header_validation_added',
            'row_level_validation_added',
            'preview_panel_added',
            'preview_is_authenticated',
            'preview_is_read_only',
            'unknown_report_key_rejected_per_row',
            'phase_69_export_preserved',
            'phase_68_bulk_selection_preserved',
            'phase_67_pagination_preserved',
        ] as $key) {
            $this->assertTrue($contract['implemented_behavior'][$key], $key);
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
    }
}
