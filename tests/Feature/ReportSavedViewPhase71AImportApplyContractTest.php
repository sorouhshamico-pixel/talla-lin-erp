<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ReportSavedViewPhase71AImportApplyContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_71a_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-71a-saved-view-import-apply-contract.json'));
        $this->assertFileExists(base_path('docs/phase-71a-saved-view-import-apply-contract.md'));
    }

    public function test_phase_71a_contract_matches_baseline_and_scope(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-71a-saved-view-import-apply-contract.json')),
            true
        );

        $this->assertSame('Phase 71A', $contract['phase']);
        $this->assertSame('Phase 70C clean', $contract['baseline']['phase']);
        $this->assertSame('9525972', $contract['baseline']['commit']);
        $this->assertSame('1373 passed / 12225 assertions', $contract['baseline']['previous_tests']);
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

    public function test_phase_71a_contract_documented_pre_apply_gap(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-71a-saved-view-import-apply-contract.json')),
            true
        );

        foreach ([
            'import_preview_route_exists',
            'import_preview_controller_action_exists',
            'import_preview_form_exists',
            'import_preview_panel_exists',
            'csv_header_validation_exists',
            'row_level_validation_exists',
            'import_preview_is_read_only',
            'write_capable_import_route_absent',
            'write_capable_import_controller_action_absent',
            'write_capable_import_form_absent',
            'import_apply_tests_absent',
            'csv_export_still_exists',
            'bulk_selection_still_exists',
            'pagination_still_exists',
        ] as $key) {
            $this->assertTrue($contract['current_state'][$key], $key);
        }

        $this->assertSame('Implement Saved View Import Apply Workflow', $contract['phase_71b_recommendation']['title']);
        $this->assertSame('high', $contract['phase_71b_recommendation']['risk']);
        $this->assertNotEmpty($contract['phase_71b_recommendation']['implementation_targets']);
        $this->assertNotEmpty($contract['guardrails']);
    }

    public function test_phase_71a_contract_remains_historical_after_phase_71b_implementation(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-71a-saved-view-import-apply-contract.json')),
            true
        );

        $this->assertSame('audit_contract', $contract['scope']['type']);
        $this->assertFalse($contract['scope']['implementation_changes_expected']);

        foreach ([
            'write_capable_import_route_absent',
            'write_capable_import_controller_action_absent',
            'write_capable_import_form_absent',
            'import_apply_tests_absent',
        ] as $preImplementationGap) {
            $this->assertTrue($contract['current_state'][$preImplementationGap], $preImplementationGap);
        }
    }

    public function test_import_preview_is_still_read_only_before_apply_contract_is_implemented(): void
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
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-views.import-preview'), [
                'csv_file' => UploadedFile::fake()->createWithContent('saved-views.csv', $csv),
            ])
            ->assertOk()
            ->assertSee('Imported Profit')
            ->assertSee('صالحة: 1');

        $this->assertSame(1, ReportSavedView::query()->count());
        $this->assertFalse(ReportSavedView::query()->where('name', 'Imported Profit')->exists());
    }

    public function test_current_export_bulk_selection_and_pagination_capabilities_remain_present(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $service = file_get_contents(app_path('Services/ReportSavedViewService.php'));
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            'reports.saved-views.index',
            'reports.saved-views.export',
            'reports.saved-views.import-preview',
            'reports.saved-views.bulk-destroy',
            'reports.saved-views.destroy-all',
            'reports.saved-views.destroy',
        ] as $routeMarker) {
            $this->assertStringContainsString($routeMarker, $routes);
        }

        foreach ([
            'public function index(Request $request, ReportSavedViewService $savedViewService): View',
            'public function previewImport(Request $request, ReportSavedViewService $savedViewService): View',
            'public function export(Request $request, ReportSavedViewService $savedViewService): StreamedResponse',
            'public function bulkDestroy(Request $request): RedirectResponse',
            'private function managementReturnQuery(Request $request): array',
            'abort_unless((int) $savedView->user_id === (int) $request->user()->id, 404)',
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
            'data-testid="report-saved-views-report-key-select"',
            'data-testid="report-saved-views-per-page-select"',
            'data-testid="report-saved-views-results-summary"',
            'data-testid="report-saved-views-pagination"',
            'data-testid="report-saved-views-export-link"',
            'data-testid="report-saved-views-bulk-action-form"',
            'data-testid="report-saved-views-bulk-delete-button"',
            'data-testid="report-saved-views-selected-count"',
            'data-testid="report-saved-view-actions"',
            'data-testid="report-saved-view-danger-actions"',
        ] as $viewMarker) {
            $this->assertStringContainsString($viewMarker, $view);
        }
    }

    public function test_phase_71a_markdown_mentions_apply_import_risks_and_guardrails(): void
    {
        $markdown = file_get_contents(base_path('docs/phase-71a-saved-view-import-apply-contract.md'));

        foreach ([
            'Phase 71A',
            'Phase 71B',
            'write-capable saved view import apply workflow',
            'Do not add write-capable import in this contract phase',
            'Do not create or update `report_saved_views` rows in this phase',
            'Write-capable import must be separate from preview',
            'Write-capable import must use a database transaction',
            'Do not import rows with unknown `report_key` values',
            'Do not silently overwrite existing saved views',
            'Do not import saved views for another user',
            'Do not use `filters_summary` as machine-readable filters without a separate parsing contract',
            'Keep import preview read-only',
            'Keep CSV export read-only and stable',
        ] as $expectedText) {
            $this->assertStringContainsString($expectedText, $markdown);
        }
    }
}
