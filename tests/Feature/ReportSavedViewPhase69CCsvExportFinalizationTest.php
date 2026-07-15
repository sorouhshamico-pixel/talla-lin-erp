<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase69CCsvExportFinalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_69c_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-69c-saved-view-management-csv-export-finalization.json'));
        $this->assertFileExists(base_path('docs/phase-69c-saved-view-management-csv-export-finalization.md'));
    }

    public function test_phase_69c_contract_marks_finalization_without_implementation_changes(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-69c-saved-view-management-csv-export-finalization.json')),
            true
        );

        $this->assertSame('Phase 69C', $contract['phase']);
        $this->assertSame('Phase 69B clean', $contract['baseline']['phase']);
        $this->assertSame('3696fc3', $contract['baseline']['commit']);
        $this->assertSame('1341 passed / 11851 assertions', $contract['baseline']['previous_tests']);
        $this->assertSame('finalization', $contract['scope']['type']);
        $this->assertFalse($contract['scope']['implementation_changes_expected']);

        foreach ([
            'routes/web.php',
            'app/Http/Controllers/ReportSavedViewController.php',
            'app/Services/ReportSavedViewService.php',
            'resources/views/reports/saved-views/index.blade.php',
            'resources/views/reports/saved-views/edit.blade.php',
            'app/Models/ReportSavedView.php',
            'app/Support/Reports/ReportSavedViewRegistry.php',
        ] as $lockedFile) {
            $this->assertContains($lockedFile, $contract['scope']['locked_implementation_files']);
        }
    }

    public function test_final_source_state_contains_export_route_controller_service_and_view_contracts(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $service = file_get_contents(app_path('Services/ReportSavedViewService.php'));
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            "Route::get('/reports/saved-views/export'",
            'reports.saved-views.export',
        ] as $routeMarker) {
            $this->assertStringContainsString($routeMarker, $routes);
        }

        foreach ([
            'use Symfony\Component\HttpFoundation\StreamedResponse;',
            'public function export(Request $request, ReportSavedViewService $savedViewService): StreamedResponse',
            "'search' => ['nullable', 'string', 'max:120']",
            "'report_key' => ['nullable', 'string', 'max:120']",
            '$savedViewService->exportForManagement(',
            'streamDownload(function () use ($savedViews): void',
            "'Content-Type' => 'text/csv; charset=UTF-8'",
            "'name'",
            "'report_label'",
            "'report_key'",
            "'is_default'",
            "'filter_count'",
            "'filters_summary'",
            "'updated_at'",
            '$rawValue = (string) ($filter[\'value\'] ?? \'\');',
        ] as $controllerMarker) {
            $this->assertStringContainsString($controllerMarker, $controller);
        }

        foreach ([
            'public function exportForManagement(',
            '->where(\'user_id\', $user->id)',
            '->when($reportKey !== \'\', fn ($query) => $query->where(\'report_key\', $reportKey))',
            '->orderByDesc(\'is_default\')',
            '->orderBy(\'name\')',
            '->get();',
        ] as $serviceMarker) {
            $this->assertStringContainsString($serviceMarker, $service);
        }

        foreach ([
            '$exportQuery = array_filter(',
            "route('reports.saved-views.export', \$exportQuery)",
            'data-testid="report-saved-views-export-link"',
            'تصدير CSV',
        ] as $viewMarker) {
            $this->assertStringContainsString($viewMarker, $view);
        }
    }

    public function test_final_csv_export_is_user_scoped_filter_scoped_and_full_result_set(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        for ($i = 1; $i <= 7; $i++) {
            ReportSavedView::query()->create([
                'user_id' => $user->id,
                'report_key' => 'profit-loss',
                'name' => sprintf('alpha export %02d', $i),
                'filters' => ['payment_status' => $i === 1 ? 'paid' : 'unpaid'],
                'is_default' => $i === 1,
            ]);
        }

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'alpha different report',
            'filters' => [],
            'is_default' => false,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $otherUser->id,
            'report_key' => 'profit-loss',
            'name' => 'alpha other user',
            'filters' => [],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.saved-views.export', [
                'search' => 'alpha',
                'report_key' => 'profit-loss',
                'per_page' => 1,
                'page' => 99,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $this->captureStreamedContent($response);

        $this->assertStringContainsString('name,report_label,report_key,is_default,filter_count,filters_summary,updated_at', $csv);
        $this->assertStringContainsString('alpha export 01', $csv);
        $this->assertStringContainsString('alpha export 07', $csv);
        $this->assertStringContainsString('profit-loss', $csv);
        $this->assertStringContainsString('paid', $csv);
        $this->assertStringContainsString('unpaid', $csv);
        $this->assertStringNotContainsString('alpha different report', $csv);
        $this->assertStringNotContainsString('alpha other user', $csv);
    }

    public function test_final_export_link_preserves_filters_but_not_page_or_per_page(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'alpha export link',
            'filters' => [],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-views.index', [
                'search' => 'alpha',
                'report_key' => 'profit-loss',
                'per_page' => 5,
                'page' => 2,
            ]))
            ->assertOk()
            ->assertSee('data-testid="report-saved-views-export-link"', false)
            ->assertSee('reports/saved-views/export?search=alpha&amp;report_key=profit-loss', false)
            ->assertDontSee('per_page=5', false)
            ->assertDontSee('page=2', false);
    }

    public function test_final_csv_export_requires_authentication(): void
    {
        $this->get(route('reports.saved-views.export'))
            ->assertRedirect(route('login'));
    }

    public function test_final_phase_68_and_phase_67_management_behavior_remains_locked(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $service = file_get_contents(app_path('Services/ReportSavedViewService.php'));
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            'reports.saved-views.index',
            'reports.saved-views.bulk-destroy',
            'reports.saved-views.destroy-all',
            'reports.saved-views.destroy',
        ] as $routeMarker) {
            $this->assertStringContainsString($routeMarker, $routes);
        }

        foreach ([
            'public function bulkDestroy(Request $request): RedirectResponse',
            'private function managementReturnQuery(Request $request): array',
            'abort_unless((int) $savedView->user_id === (int) $request->user()->id, 404)',
        ] as $controllerMarker) {
            $this->assertStringContainsString($controllerMarker, $controller);
        }

        foreach ([
            'public function paginateForManagement(',
            '->paginate($perPage)',
            '->withQueryString()',
        ] as $serviceMarker) {
            $this->assertStringContainsString($serviceMarker, $service);
        }

        foreach ([
            'data-testid="report-saved-views-search-form"',
            'data-testid="report-saved-views-report-key-select"',
            'data-testid="report-saved-views-per-page-select"',
            'data-testid="report-saved-views-results-summary"',
            'data-testid="report-saved-views-pagination"',
            'data-testid="report-saved-views-bulk-action-form"',
            'data-testid="report-saved-views-bulk-delete-button"',
            'data-testid="report-saved-views-selected-count"',
            'data-testid="report-saved-views-bulk-return-search"',
            'data-testid="report-saved-view-actions"',
            'data-testid="report-saved-view-danger-actions"',
        ] as $viewMarker) {
            $this->assertStringContainsString($viewMarker, $view);
        }
    }

    public function test_phase_69c_json_contract_documents_finalized_behavior_and_next_recommendation(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-69c-saved-view-management-csv-export-finalization.json')),
            true
        );

        foreach ([
            'csv_export_route_locked',
            'csv_export_controller_action_locked',
            'csv_export_service_query_locked',
            'csv_export_link_locked',
            'csv_export_user_scope_locked',
            'csv_export_search_filter_locked',
            'csv_export_report_key_filter_locked',
            'csv_export_full_filtered_set_locked',
            'csv_export_ignores_page_and_per_page_locked',
            'csv_export_columns_locked',
            'csv_filter_summary_includes_display_and_raw_value_locked',
            'phase_68_bulk_selection_preserved',
            'phase_67_management_pagination_preserved',
            'phase_66_authorization_preserved',
        ] as $key) {
            $this->assertTrue($contract['finalized_behavior'][$key], $key);
        }

        $this->assertSame('Phase 70A', $contract['next_recommendation']['phase']);
        $this->assertSame('Saved View Management Import Contract', $contract['next_recommendation']['title']);
        $this->assertNotEmpty($contract['guardrails']);
    }

    private function captureStreamedContent($response): string
    {
        ob_start();

        $response->baseResponse->sendContent();

        return (string) ob_get_clean();
    }
}
