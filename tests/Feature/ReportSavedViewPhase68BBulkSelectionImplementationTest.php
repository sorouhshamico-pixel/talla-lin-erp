<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase68BBulkSelectionImplementationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_68b_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-68b-saved-view-management-bulk-selection-implementation.json'));
        $this->assertFileExists(base_path('docs/phase-68b-saved-view-management-bulk-selection-implementation.md'));
    }

    public function test_management_page_renders_bulk_selection_controls_while_preserving_existing_controls(): void
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
            ->assertSee('حذف المحدد')
            ->assertSee('data-testid="report-saved-views-select-all-checkbox"', false)
            ->assertSee('data-testid="report-saved-view-bulk-select-checkbox"', false)
            ->assertSee('name="saved_view_ids[]"', false)
            ->assertSee('form="report_saved_views_bulk_delete_form"', false)
            ->assertSee('data-testid="report-saved-view-actions"', false)
            ->assertSee('data-testid="report-saved-view-delete-button"', false)
            ->assertSee('data-testid="report-saved-views-clear-all-button"', false)
            ->assertSee('data-testid="report-saved-views-search-form"', false)
            ->assertSee('data-testid="report-saved-views-report-key-select"', false)
            ->assertSee('data-testid="report-saved-views-per-page-select"', false);
    }

    public function test_bulk_destroy_deletes_only_selected_saved_views_owned_by_authenticated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $selectedOne = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'تحديد 1',
            'filters' => [],
            'is_default' => false,
        ]);

        $selectedTwo = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'تحديد 2',
            'filters' => [],
            'is_default' => false,
        ]);

        $notSelected = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'cash-flow-dashboard',
            'name' => 'غير محدد',
            'filters' => [],
            'is_default' => false,
        ]);

        $otherUsersSavedView = ReportSavedView::query()->create([
            'user_id' => $otherUser->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض مستخدم آخر',
            'filters' => [],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->delete(route('reports.saved-views.bulk-destroy'), [
                'saved_view_ids' => [
                    $selectedOne->id,
                    $selectedTwo->id,
                    $otherUsersSavedView->id,
                ],
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas('status', 'تم حذف 2 من العروض المحددة.');

        $this->assertFalse(ReportSavedView::query()->whereKey($selectedOne->id)->exists());
        $this->assertFalse(ReportSavedView::query()->whereKey($selectedTwo->id)->exists());
        $this->assertTrue(ReportSavedView::query()->whereKey($notSelected->id)->exists());
        $this->assertTrue(ReportSavedView::query()->whereKey($otherUsersSavedView->id)->exists());
    }

    public function test_bulk_destroy_requires_at_least_one_selected_saved_view_id(): void
    {
        $user = User::factory()->create();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'يبقى محفوظًا',
            'filters' => [],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->from(route('reports.saved-views.index'))
            ->delete(route('reports.saved-views.bulk-destroy'), [])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHasErrors('saved_view_ids');

        $this->assertTrue(ReportSavedView::query()->whereKey($savedView->id)->exists());
    }

    public function test_bulk_destroy_with_only_other_users_selected_ids_deletes_nothing(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownedSavedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'ملكي',
            'filters' => [],
            'is_default' => false,
        ]);

        $otherSavedView = ReportSavedView::query()->create([
            'user_id' => $otherUser->id,
            'report_key' => 'profit-loss',
            'name' => 'ليس ملكي',
            'filters' => [],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->delete(route('reports.saved-views.bulk-destroy'), [
                'saved_view_ids' => [$otherSavedView->id],
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas('status', 'لم يتم حذف أي عروض محفوظة.');

        $this->assertTrue(ReportSavedView::query()->whereKey($ownedSavedView->id)->exists());
        $this->assertTrue(ReportSavedView::query()->whereKey($otherSavedView->id)->exists());
    }

    public function test_phase_68b_source_has_bulk_route_controller_and_view_markers(): void
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

        foreach ([
            'data-testid="report-saved-views-bulk-action-form"',
            'data-testid="report-saved-views-bulk-delete-button"',
            'data-testid="report-saved-views-select-all-checkbox"',
            'data-testid="report-saved-view-bulk-select-checkbox"',
            'name="saved_view_ids[]"',
            'form="report_saved_views_bulk_delete_form"',
        ] as $marker) {
            $this->assertStringContainsString($marker, $view);
        }
    }

    public function test_phase_68b_preserves_phase_67_and_phase_66_management_controls(): void
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
        ] as $marker) {
            $this->assertStringContainsString($marker, $view);
        }

        $this->assertStringContainsString('abort_unless((int) $savedView->user_id === (int) $request->user()->id, 404)', $controller);
    }

    public function test_phase_68b_json_contract_documents_implementation(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-68b-saved-view-management-bulk-selection-implementation.json')),
            true
        );

        $this->assertSame('Phase 68B', $contract['phase']);
        $this->assertSame('Phase 68A clean', $contract['baseline']['phase']);
        $this->assertSame('0661b7f', $contract['baseline']['commit']);
        $this->assertSame('1295 passed / 11436 assertions', $contract['baseline']['previous_tests']);

        foreach ([
            'bulk_action_form_visible',
            'bulk_delete_button_visible',
            'select_all_checkbox_visible',
            'row_selection_checkboxes_visible',
            'bulk_destroy_route_added',
            'bulk_destroy_controller_method_added',
            'bulk_delete_is_selection_scoped',
            'bulk_delete_is_user_scoped',
            'bulk_delete_requires_at_least_one_selected_id',
            'phase_67_search_filter_pagination_controls_preserved',
            'phase_66_row_actions_preserved',
        ] as $key) {
            $this->assertTrue($contract['implemented_behavior'][$key], $key);
        }
    }
}
