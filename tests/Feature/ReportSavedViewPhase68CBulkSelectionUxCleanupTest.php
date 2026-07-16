<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase68CBulkSelectionUxCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_68c_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-68c-saved-view-management-bulk-selection-ux-cleanup.json'));
        $this->assertFileExists(base_path('docs/phase-68c-saved-view-management-bulk-selection-ux-cleanup.md'));
    }

    public function test_saved_view_management_blade_has_no_script_artifact_text(): void
    {
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            'SEARCH,',
            'REPLACE,',
            '$view = replace_once(',
            '<<<\'SEARCH\'',
            '<<<\'REPLACE\'',
            'insert saved view bulk action form',
            'insert saved view select all header checkbox',
            'insert saved view row bulk checkbox',
            'insert saved view select all helper script',
        ] as $artifact) {
            $this->assertStringNotContainsString($artifact, $view);
        }
    }

    public function test_management_page_renders_clean_bulk_selection_ux(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض قابل للتحديد',
            'filters' => [],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-views.index'))
            ->assertOk()
            ->assertSee('data-testid="report-saved-views-bulk-action-form"', false)
            ->assertSee('data-testid="report-saved-views-bulk-delete-button"', false)
            ->assertSee('disabled', false)
            ->assertSee('data-testid="report-saved-views-selected-count"', false)
            ->assertSee('المحدد: 0')
            ->assertSee('data-testid="report-saved-views-select-all-checkbox"', false)
            ->assertSee('data-testid="report-saved-view-bulk-select-checkbox"', false)
            ->assertSee('name="saved_view_ids[]"', false)
            ->assertSee('form="report_saved_views_bulk_delete_form"', false)
            ->assertDontSee('SEARCH,')
            ->assertDontSee('$view = replace_once(');
    }

    public function test_bulk_selection_script_updates_count_and_button_state(): void
    {
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            'function updateBulkSelectionState()',
            "selectedCountLabel.textContent = 'المحدد: ' + selectedCount",
            'bulkDeleteButton.disabled = selectedCount === 0',
            'selectAll.indeterminate = selectedCount > 0 && selectedCount < rowCheckboxes.length',
            "alert('اختر عرضًا واحدًا على الأقل.')",
            "confirm('هل تريد حذف العروض المحددة؟')",
            'updateBulkSelectionState();',
        ] as $marker) {
            $this->assertStringContainsString($marker, $view);
        }
    }

    public function test_phase_68b_bulk_delete_behavior_still_deletes_only_selected_owned_views(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $selected = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'محدد',
            'filters' => [],
            'is_default' => false,
        ]);

        $notSelected = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'غير محدد',
            'filters' => [],
            'is_default' => false,
        ]);

        $otherUsersSavedView = ReportSavedView::query()->create([
            'user_id' => $otherUser->id,
            'report_key' => 'cash-flow-dashboard',
            'name' => 'مستخدم آخر',
            'filters' => [],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->delete(route('reports.saved-views.bulk-destroy'), [
                'saved_view_ids' => [
                    $selected->id,
                    $otherUsersSavedView->id,
                ],
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas('status', 'تم حذف 1 من العروض المحددة.');

        $this->assertFalse(ReportSavedView::query()->whereKey($selected->id)->exists());
        $this->assertTrue(ReportSavedView::query()->whereKey($notSelected->id)->exists());
        $this->assertTrue(ReportSavedView::query()->whereKey($otherUsersSavedView->id)->exists());
    }

    public function test_phase_68c_preserves_phase_67_and_phase_68b_view_markers(): void
    {
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            'data-testid="report-saved-views-search-form"',
            'data-testid="report-saved-views-report-key-select"',
            'data-testid="report-saved-views-per-page-select"',
            'data-testid="report-saved-views-active-filters"',
            'data-testid="report-saved-views-filtered-empty-message"',
            'data-testid="report-saved-views-results-summary"',
            'data-testid="report-saved-views-pagination"',
            'data-testid="report-saved-views-bulk-action-form"',
            'data-testid="report-saved-views-bulk-delete-button"',
            'data-testid="report-saved-views-selected-count"',
            'data-testid="report-saved-views-select-all-checkbox"',
            'data-testid="report-saved-view-bulk-select-checkbox"',
            'data-testid="report-saved-view-actions"',
            'data-testid="report-saved-view-primary-actions"',
            'data-testid="report-saved-view-secondary-actions"',
            'data-testid="report-saved-view-danger-actions"',
            'data-testid="report-saved-views-clear-all-button"',
        ] as $marker) {
            $this->assertStringContainsString($marker, $view);
        }
    }

    public function test_phase_68c_does_not_change_route_or_controller_bulk_destroy_contract(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));

        $this->assertStringContainsString("reports.saved-views.bulk-destroy", $routes);
        $this->assertStringContainsString("public function bulkDestroy(Request \$request): RedirectResponse", $controller);
        $this->assertStringContainsString("'saved_view_ids' => ['required', 'array', 'min:1']", $controller);
        $this->assertStringContainsString("'saved_view_ids.*' => ['integer', 'distinct']", $controller);
        $this->assertStringContainsString('$this->importApplyService->apply(', $controller);
        $this->assertStringContainsString("->whereIn('id', \$selectedIds)", $controller);
    }

    public function test_phase_68c_json_contract_documents_cleanup_and_ux(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-68c-saved-view-management-bulk-selection-ux-cleanup.json')),
            true
        );

        $this->assertSame('Phase 68C', $contract['phase']);
        $this->assertSame('Phase 68B clean', $contract['baseline']['phase']);
        $this->assertSame('a9277d9', $contract['baseline']['commit']);
        $this->assertSame('1301 passed / 11472 assertions', $contract['baseline']['previous_tests']);

        foreach ([
            'removed_script_artifact_text_from_blade',
            'bulk_delete_button_disabled_until_selection',
            'selected_count_visible',
            'select_all_updates_selected_count',
            'row_selection_updates_selected_count',
            'select_all_indeterminate_state_supported',
            'empty_submit_guard_present',
            'bulk_delete_confirmation_preserved',
            'phase_68b_bulk_route_and_controller_preserved',
            'phase_67_search_filter_pagination_controls_preserved',
        ] as $key) {
            $this->assertTrue($contract['implemented_behavior'][$key], $key);
        }
    }
}
