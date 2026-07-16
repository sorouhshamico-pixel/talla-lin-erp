<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ReportSavedViewPhase72AFiltersPayloadContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_72a_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-72a-saved-view-machine-readable-filters-payload-contract.json'));
        $this->assertFileExists(base_path('docs/phase-72a-saved-view-machine-readable-filters-payload-contract.md'));
    }

    public function test_phase_72a_contract_matches_baseline_and_scope(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-72a-saved-view-machine-readable-filters-payload-contract.json')),
            true
        );

        $this->assertSame('Phase 72A', $contract['phase']);
        $this->assertSame('Phase 71C clean', $contract['baseline']['phase']);
        $this->assertSame('1ae3818', $contract['baseline']['commit']);
        $this->assertSame('1398 passed / 12494 assertions', $contract['baseline']['previous_tests']);
        $this->assertSame('audit_contract', $contract['scope']['type']);
        $this->assertFalse($contract['scope']['implementation_changes_expected']);

        foreach ([
            'routes/web.php',
            'app/Http/Controllers/ReportSavedViewController.php',
            'app/Services/ReportSavedViewService.php',
            'resources/views/reports/saved-views/index.blade.php',
            'resources/views/reports/saved-views/edit.blade.php',
            'app/Models/ReportSavedView.php',
            'app/Support/Reports/ReportSavedViewRegistry.php',
        ] as $excludedFile) {
            $this->assertContains($excludedFile, $contract['scope']['excluded_files']);
        }
    }

    public function test_phase_72a_historical_contract_keeps_human_filters_summary_marker(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/ReportSavedViewController.php')
        );
        $versionRegistry = file_get_contents(
            app_path(
                'Support/Reports/'
                . 'ReportSavedViewImportExportVersionRegistry.php'
            )
        );
        $parser = file_get_contents(
            app_path('Support/Reports/ReportSavedViewCsvImportParser.php')
        );

        foreach ([
            '$filtersSummary = $formatted->filters',
            '$filter[\'label\'] . \': \' . $displayValue',
            '$filter[\'label\'] . \': \' . $displayValue . \' (\' . $rawValue . \')\'',
        ] as $controllerSummaryMarker) {
            $this->assertStringContainsString(
                $controllerSummaryMarker,
                $controller
            );
        }

        $this->assertStringContainsString(
            "'filters_summary'",
            $versionRegistry
        );
        $this->assertStringContainsString(
            "'filters_summary'",
            $parser
        );
        $this->assertStringNotContainsString(
            'json_decode($data[\'filters_summary\']',
            $parser
        );
        $this->assertStringNotContainsString(
            'parseFiltersSummary',
            $parser
        );
    }

    public function test_current_import_apply_still_creates_empty_filters_and_does_not_parse_summary(): void
    {
        $user = User::factory()->create();

        $csv = implode("\n", [
            'name,report_label,report_key,is_default,filter_count,filters_summary,updated_at',
            'Imported Profit,تقرير الأرباح والخسائر,profit-loss,yes,1,حالة الدفع: مدفوعة بالكامل (paid),2026-07-15 10:00:00',
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
            ->where('name', 'Imported Profit')
            ->firstOrFail();

        $this->assertSame([], $savedView->filters);
    }

    public function test_current_import_preview_and_apply_contracts_remain_present(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            "Route::post('/reports/saved-views/import-preview'",
            "Route::post('/reports/saved-views/import-apply'",
            'reports.saved-views.import-preview',
            'reports.saved-views.import-apply',
        ] as $routeMarker) {
            $this->assertStringContainsString($routeMarker, $routes);
        }

        foreach ([
            'public function previewImport(Request $request, ReportSavedViewService $savedViewService): View',
            'public function applyImport(Request $request): RedirectResponse',
            '$this->csvImportParser->parse($tempPath)',
            'private function applySavedViewImportRows(Request $request, array $rows): array',
            'return DB::transaction(function () use ($request, $rows): array',
            "'filters' => \$row['filters'] ?? []",
        ] as $controllerMarker) {
            $this->assertStringContainsString($controllerMarker, $controller);
        }

        foreach ([
            'data-testid="report-saved-views-import-preview-form"',
            'data-testid="report-saved-views-import-apply-form"',
            'data-testid="report-saved-views-import-apply-button"',
        ] as $viewMarker) {
            $this->assertStringContainsString($viewMarker, $view);
        }
    }

    public function test_current_export_preview_apply_bulk_selection_and_pagination_capabilities_remain_present(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $service = file_get_contents(app_path('Services/ReportSavedViewService.php'));
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            'reports.saved-views.index',
            'reports.saved-views.export',
            'reports.saved-views.import-preview',
            'reports.saved-views.import-apply',
            'reports.saved-views.bulk-destroy',
            'reports.saved-views.destroy-all',
            'reports.saved-views.destroy',
        ] as $routeMarker) {
            $this->assertStringContainsString($routeMarker, $routes);
        }

        foreach ([
            'public function index(Request $request, ReportSavedViewService $savedViewService): View',
            'public function export(Request $request, ReportSavedViewService $savedViewService): StreamedResponse',
            'public function previewImport(Request $request, ReportSavedViewService $savedViewService): View',
            'public function applyImport(Request $request): RedirectResponse',
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
            '->where(\'user_id\', $user->id)',
        ] as $serviceMarker) {
            $this->assertStringContainsString($serviceMarker, $service);
        }

        foreach ([
            'data-testid="report-saved-views-search-form"',
            'data-testid="report-saved-views-import-preview-form"',
            'data-testid="report-saved-views-import-apply-form"',
            'data-testid="report-saved-views-report-key-select"',
            'data-testid="report-saved-views-per-page-select"',
            'data-testid="report-saved-views-results-summary"',
            'data-testid="report-saved-views-pagination"',
            'data-testid="report-saved-views-export-link"',
            'data-testid="report-saved-views-bulk-action-form"',
            'data-testid="report-saved-views-bulk-delete-button"',
            'data-testid="report-saved-views-selected-count"',
        ] as $viewMarker) {
            $this->assertStringContainsString($viewMarker, $view);
        }
    }

    public function test_phase_72a_contract_documents_current_state_and_next_recommendation(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-72a-saved-view-machine-readable-filters-payload-contract.json')),
            true
        );

        foreach ([
            'csv_export_exists',
            'csv_export_human_filters_summary_exists',
            'filters_payload_export_column_absent',
            'import_preview_exists',
            'import_apply_exists',
            'import_apply_revalidates_payload',
            'import_apply_uses_transaction',
            'import_apply_skips_duplicates',
            'import_apply_imports_empty_filters',
            'filters_summary_not_machine_readable',
            'lossless_saved_view_import_absent',
            'phase_71_import_apply_stable',
            'phase_70_import_preview_stable',
            'phase_69_csv_export_stable',
        ] as $key) {
            $this->assertTrue($contract['current_state'][$key], $key);
        }

        $this->assertSame('Implement Saved View Filters Payload Export And Import', $contract['phase_72b_recommendation']['title']);
        $this->assertSame('medium-high', $contract['phase_72b_recommendation']['risk']);
        $this->assertContains('filters_payload', $contract['proposed_csv_columns']);
        $this->assertNotEmpty($contract['phase_72b_recommendation']['implementation_targets']);
        $this->assertNotEmpty($contract['guardrails']);
    }

    public function test_phase_72a_markdown_mentions_filters_payload_risks_and_guardrails(): void
    {
        $markdown = file_get_contents(base_path('docs/phase-72a-saved-view-machine-readable-filters-payload-contract.md'));

        foreach ([
            'Phase 72A',
            'Phase 72B',
            'machine-readable `filters_payload` column',
            '`filters_summary` is human-readable and not safe to parse as structured data',
            'Add `filters_payload` as a machine-readable JSON column to saved view CSV export',
            'Validate `filters_payload` as JSON before import apply writes',
            'Use `filters_payload` for imported saved view filters when present and valid',
            'Keep `filters_summary` ignored for machine-readable import',
            'Do not implement `filters_payload` in this contract phase',
            'Invalid `filters_payload` must block writes',
            'Keep Phase 71 import apply stable',
            'Keep Phase 70 import preview stable',
            'Keep Phase 69 CSV export stable',
        ] as $expectedText) {
            $this->assertStringContainsString($expectedText, $markdown);
        }
    }
}
