<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase69BCsvExportImplementationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_69b_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-69b-saved-view-management-csv-export-implementation.json'));
        $this->assertFileExists(base_path('docs/phase-69b-saved-view-management-csv-export-implementation.md'));
    }

    public function test_management_page_renders_export_link_preserving_active_filters(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'alpha exportable',
            'filters' => ['payment_status' => 'paid'],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-views.index', [
                'search' => 'alpha',
                'report_key' => 'profit-loss',
                'per_page' => 5,
            ]))
            ->assertOk()
            ->assertSee('data-testid="report-saved-views-export-link"', false)
            ->assertSee('تصدير CSV')
            ->assertSee('reports/saved-views/export?search=alpha&amp;report_key=profit-loss', false);
    }

    public function test_csv_export_is_user_scoped_filtered_and_not_page_scoped(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'alpha default',
            'filters' => ['payment_status' => 'paid'],
            'is_default' => true,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'alpha second',
            'filters' => ['payment_status' => 'unpaid'],
            'is_default' => false,
        ]);

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
        $this->assertStringContainsString('alpha default', $csv);
        $this->assertStringContainsString('alpha second', $csv);
        $this->assertStringContainsString('profit-loss', $csv);
        $this->assertStringContainsString('paid', $csv);
        $this->assertStringContainsString('unpaid', $csv);
        $this->assertStringNotContainsString('alpha different report', $csv);
        $this->assertStringNotContainsString('alpha other user', $csv);
    }

    public function test_csv_export_drops_invalid_report_key_and_uses_search_only(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'alpha profit',
            'filters' => [],
            'is_default' => false,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'alpha aging',
            'filters' => [],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.saved-views.export', [
                'search' => 'alpha',
                'report_key' => 'not-a-report',
            ]))
            ->assertOk();

        $csv = $this->captureStreamedContent($response);

        $this->assertStringContainsString('alpha profit', $csv);
        $this->assertStringContainsString('alpha aging', $csv);
    }

    public function test_csv_export_requires_authentication(): void
    {
        $this->get(route('reports.saved-views.export'))
            ->assertRedirect(route('login'));
    }

    public function test_phase_69b_source_contains_route_service_controller_and_view_markers(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $service = file_get_contents(app_path('Services/ReportSavedViewService.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            "Route::get('/reports/saved-views/export'",
            'reports.saved-views.export',
        ] as $routeMarker) {
            $this->assertStringContainsString($routeMarker, $routes);
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
            'use Symfony\Component\HttpFoundation\StreamedResponse;',
            'public function export(Request $request, ReportSavedViewService $savedViewService): StreamedResponse',
            '$savedViewService->exportForManagement(',
            'streamDownload(function () use ($savedViews): void',
            "'Content-Type' => 'text/csv; charset=UTF-8'",
            "'name'",
            "'report_label'",
            "'filters_summary'",
        ] as $controllerMarker) {
            $this->assertStringContainsString($controllerMarker, $controller);
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

    public function test_phase_69b_preserves_phase_68_and_phase_67_markers(): void
    {
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $service = file_get_contents(app_path('Services/ReportSavedViewService.php'));

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
    }

    public function test_phase_69b_json_contract_documents_csv_export_implementation(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-69b-saved-view-management-csv-export-implementation.json')),
            true
        );

        $this->assertSame('Phase 69B', $contract['phase']);
        $this->assertSame('Phase 69A clean', $contract['baseline']['phase']);
        $this->assertSame('3b96f53', $contract['baseline']['commit']);
        $this->assertSame('1333 passed / 11773 assertions', $contract['baseline']['previous_tests']);

        foreach ([
            'export_route_added',
            'export_controller_action_added',
            'export_service_query_added',
            'export_link_added_to_management_filters',
            'export_is_user_scoped',
            'export_honors_search_filter',
            'export_honors_report_key_filter',
            'export_ignores_page_and_per_page',
            'export_uses_csv_download',
            'export_columns_are_stable',
            'phase_68_bulk_selection_preserved',
            'phase_67_pagination_preserved',
            'phase_66_authorization_preserved',
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
            $this->assertContains($column, $contract['csv_columns']);
        }
    }

    private function captureStreamedContent($response): string
    {
        ob_start();

        $response->baseResponse->sendContent();

        return (string) ob_get_clean();
    }
}
