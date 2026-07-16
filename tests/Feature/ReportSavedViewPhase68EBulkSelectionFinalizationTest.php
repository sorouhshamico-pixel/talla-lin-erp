<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase68EBulkSelectionFinalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_68e_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-68e-saved-view-bulk-selection-finalization.json'));
        $this->assertFileExists(base_path('docs/phase-68e-saved-view-bulk-selection-finalization.md'));
    }

    public function test_phase_68e_contract_marks_finalization_without_implementation_changes(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-68e-saved-view-bulk-selection-finalization.json')),
            true
        );

        $this->assertSame('Phase 68E', $contract['phase']);
        $this->assertSame('Phase 68D clean', $contract['baseline']['phase']);
        $this->assertSame('b78dcd2', $contract['baseline']['commit']);
        $this->assertSame('1318 passed / 11614 assertions', $contract['baseline']['previous_tests']);
        $this->assertSame('finalization', $contract['scope']['type']);
        $this->assertFalse($contract['scope']['implementation_changes_expected']);

        foreach ([
            'app/Http/Controllers/ReportSavedViewController.php',
            'routes/web.php',
            'resources/views/reports/saved-views/index.blade.php',
            'app/Services/ReportSavedViewService.php',
            'resources/views/reports/saved-views/edit.blade.php',
            'app/Models/ReportSavedView.php',
            'app/Support/Reports/ReportSavedViewRegistry.php',
        ] as $lockedFile) {
            $this->assertContains($lockedFile, $contract['scope']['locked_implementation_files']);
        }
    }

    public function test_final_source_state_contains_bulk_selection_route_controller_and_view_contracts(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        $this->assertStringContainsString("reports.saved-views.bulk-destroy", $routes);
        $this->assertStringContainsString("public function bulkDestroy(Request \$request): RedirectResponse", $controller);
        $this->assertStringContainsString("'saved_view_ids' => ['required', 'array', 'min:1']", $controller);
        $this->assertStringContainsString("'saved_view_ids.*' => ['integer', 'distinct']", $controller);
        $this->assertStringContainsString('$this->importApplyService->apply(', $controller);
        $this->assertStringContainsString("->whereIn('id', \$selectedIds)", $controller);
        $this->assertStringContainsString("private function managementReturnQuery(Request \$request): array", $controller);
        $this->assertStringContainsString("ReportSavedViewRegistry::has(\$reportKey)", $controller);

        foreach ([
            'data-testid="report-saved-views-bulk-action-form"',
            'data-testid="report-saved-views-bulk-delete-button"',
            'data-testid="report-saved-views-selected-count"',
            'data-testid="report-saved-views-select-all-checkbox"',
            'data-testid="report-saved-view-bulk-select-checkbox"',
            'data-testid="report-saved-views-bulk-return-search"',
            'data-testid="report-saved-views-bulk-return-report-key"',
            'data-testid="report-saved-views-bulk-return-per-page"',
            'data-testid="report-saved-views-bulk-return-page"',
            'function updateBulkSelectionState()',
        ] as $marker) {
            $this->assertStringContainsString($marker, $view);
        }
    }

    public function test_final_blade_has_no_script_artifact_text(): void
    {
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            'SEARCH,',
            'REPLACE,',
            '$view = replace_once(',
            '<<<\'SEARCH\'',
            '<<<\'REPLACE\'',
            'insert saved view bulk action form',
            'insert hidden bulk management return context inputs',
        ] as $artifact) {
            $this->assertStringNotContainsString($artifact, $view);
        }
    }

    public function test_final_bulk_delete_is_selection_scoped_user_scoped_and_preserves_valid_context(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $selected = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'alpha selected',
            'filters' => [],
            'is_default' => false,
        ]);

        $notSelected = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'alpha kept',
            'filters' => [],
            'is_default' => false,
        ]);

        $otherUsersSavedView = ReportSavedView::query()->create([
            'user_id' => $otherUser->id,
            'report_key' => 'profit-loss',
            'name' => 'alpha other user',
            'filters' => [],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->delete(route('reports.saved-views.bulk-destroy'), [
                'saved_view_ids' => [
                    $selected->id,
                    $otherUsersSavedView->id,
                ],
                'return_search' => 'alpha',
                'return_report_key' => 'profit-loss',
                'return_per_page' => 5,
                'return_page' => 2,
            ])
            ->assertRedirect(route('reports.saved-views.index', [
                'search' => 'alpha',
                'report_key' => 'profit-loss',
                'per_page' => 5,
                'page' => 2,
            ]))
            ->assertSessionHas('status', 'تم حذف 1 من العروض المحددة.');

        $this->assertFalse(ReportSavedView::query()->whereKey($selected->id)->exists());
        $this->assertTrue(ReportSavedView::query()->whereKey($notSelected->id)->exists());
        $this->assertTrue(ReportSavedView::query()->whereKey($otherUsersSavedView->id)->exists());
    }

    public function test_final_bulk_delete_without_return_context_redirects_to_plain_index(): void
    {
        $user = User::factory()->create();

        $selected = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'plain selected',
            'filters' => [],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->delete(route('reports.saved-views.bulk-destroy'), [
                'saved_view_ids' => [$selected->id],
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas('status', 'تم حذف 1 من العروض المحددة.');

        $this->assertFalse(ReportSavedView::query()->whereKey($selected->id)->exists());
    }

    public function test_final_empty_bulk_selection_validation_is_locked(): void
    {
        $user = User::factory()->create();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'must remain',
            'filters' => [],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->from(route('reports.saved-views.index'))
            ->delete(route('reports.saved-views.bulk-destroy'), [
                'return_search' => 'alpha',
                'return_report_key' => 'profit-loss',
                'return_per_page' => 5,
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHasErrors('saved_view_ids');

        $this->assertTrue(ReportSavedView::query()->whereKey($savedView->id)->exists());
    }

    public function test_final_phase_67_and_phase_66_management_controls_remain_visible(): void
    {
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));

        foreach ([
            'data-testid="report-saved-views-search-form"',
            'data-testid="report-saved-views-report-key-select"',
            'data-testid="report-saved-views-per-page-select"',
            'data-testid="report-saved-views-active-filters"',
            'data-testid="report-saved-views-filtered-empty-message"',
            'data-testid="report-saved-views-results-summary"',
            'data-testid="report-saved-views-pagination"',
            'data-testid="report-saved-view-actions"',
            'data-testid="report-saved-view-primary-actions"',
            'data-testid="report-saved-view-secondary-actions"',
            'data-testid="report-saved-view-danger-actions"',
            'data-testid="report-saved-views-clear-all-button"',
        ] as $marker) {
            $this->assertStringContainsString($marker, $view);
        }

        $this->assertStringContainsString('abort_unless((int) $savedView->user_id === (int) $request->user()->id, 404)', $controller);
    }

    public function test_phase_68e_json_contract_documents_finalized_behavior_and_next_recommendation(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-68e-saved-view-bulk-selection-finalization.json')),
            true
        );

        foreach ([
            'bulk_selection_contract_prepared',
            'bulk_selection_implemented',
            'bulk_selection_ux_cleaned',
            'bulk_delete_context_preserved',
            'bulk_delete_user_scope_locked',
            'bulk_delete_selection_scope_locked',
            'empty_selection_validation_locked',
            'plain_redirect_without_return_context_locked',
            'valid_context_redirect_locked',
            'invalid_report_key_return_context_dropped',
            'phase_67_search_filter_pagination_preserved',
            'phase_66_row_actions_and_authorization_preserved',
        ] as $key) {
            $this->assertTrue($contract['finalized_behavior'][$key], $key);
        }

        $this->assertSame('Phase 69A', $contract['next_recommendation']['phase']);
        $this->assertSame('Saved View Management Export Contract', $contract['next_recommendation']['title']);
        $this->assertNotEmpty($contract['guardrails']);
    }
}
