<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase68DBulkDeleteManagementContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_68d_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-68d-saved-view-bulk-delete-management-context.json'));
        $this->assertFileExists(base_path('docs/phase-68d-saved-view-bulk-delete-management-context.md'));
    }

    public function test_bulk_delete_form_carries_current_management_context(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 7; $i++) {
            ReportSavedView::query()->create([
                'user_id' => $user->id,
                'report_key' => 'profit-loss',
                'name' => sprintf('alpha saved view %02d', $i),
                'filters' => [],
                'is_default' => false,
            ]);
        }

        $this->actingAs($user)
            ->get(route('reports.saved-views.index', [
                'search' => 'alpha',
                'report_key' => 'profit-loss',
                'per_page' => 5,
                'page' => 2,
            ]))
            ->assertOk()
            ->assertSee('data-testid="report-saved-views-bulk-return-search"', false)
            ->assertSee('name="return_search"', false)
            ->assertSee('value="alpha"', false)
            ->assertSee('data-testid="report-saved-views-bulk-return-report-key"', false)
            ->assertSee('name="return_report_key"', false)
            ->assertSee('value="profit-loss"', false)
            ->assertSee('data-testid="report-saved-views-bulk-return-per-page"', false)
            ->assertSee('name="return_per_page"', false)
            ->assertSee('value="5"', false)
            ->assertSee('data-testid="report-saved-views-bulk-return-page"', false)
            ->assertSee('name="return_page"', false)
            ->assertSee('value="2"', false);
    }

    public function test_bulk_destroy_redirect_preserves_valid_management_context(): void
    {
        $user = User::factory()->create();

        $selected = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'alpha selected',
            'filters' => [],
            'is_default' => false,
        ]);

        $kept = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'alpha kept',
            'filters' => [],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->delete(route('reports.saved-views.bulk-destroy'), [
                'saved_view_ids' => [$selected->id],
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
        $this->assertTrue(ReportSavedView::query()->whereKey($kept->id)->exists());
    }

    public function test_bulk_destroy_without_return_context_redirects_to_plain_management_index(): void
    {
        $user = User::factory()->create();

        $selected = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'plain redirect selected',
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

    public function test_bulk_destroy_drops_invalid_return_report_key_but_keeps_safe_context(): void
    {
        $user = User::factory()->create();

        $selected = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'alpha selected',
            'filters' => [],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->delete(route('reports.saved-views.bulk-destroy'), [
                'saved_view_ids' => [$selected->id],
                'return_search' => 'alpha',
                'return_report_key' => 'not-a-report',
                'return_per_page' => 5,
                'return_page' => 3,
            ])
            ->assertRedirect(route('reports.saved-views.index', [
                'search' => 'alpha',
                'per_page' => 5,
                'page' => 3,
            ]));
    }

    public function test_bulk_destroy_still_deletes_only_selected_owned_views_with_return_context(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $selected = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'selected',
            'filters' => [],
            'is_default' => false,
        ]);

        $notSelected = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'not selected',
            'filters' => [],
            'is_default' => false,
        ]);

        $otherUsersSavedView = ReportSavedView::query()->create([
            'user_id' => $otherUser->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'other user',
            'filters' => [],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->delete(route('reports.saved-views.bulk-destroy'), [
                'saved_view_ids' => [$selected->id, $otherUsersSavedView->id],
                'return_search' => 'selected',
                'return_report_key' => 'sales-invoice-aging',
                'return_per_page' => 10,
            ])
            ->assertRedirect(route('reports.saved-views.index', [
                'search' => 'selected',
                'report_key' => 'sales-invoice-aging',
                'per_page' => 10,
            ]))
            ->assertSessionHas('status', 'تم حذف 1 من العروض المحددة.');

        $this->assertFalse(ReportSavedView::query()->whereKey($selected->id)->exists());
        $this->assertTrue(ReportSavedView::query()->whereKey($notSelected->id)->exists());
        $this->assertTrue(ReportSavedView::query()->whereKey($otherUsersSavedView->id)->exists());
    }

    public function test_phase_68d_source_has_context_preservation_markers(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            "'return_search' => ['nullable', 'string', 'max:120']",
            "'return_report_key' => ['nullable', 'string', 'max:120']",
            "'return_per_page' => ['nullable', 'integer', 'min:5', 'max:100']",
            "'return_page' => ['nullable', 'integer', 'min:1']",
            'private function managementReturnQuery(Request $request): array',
            "ReportSavedViewRegistry::has(\$reportKey)",
            "->route('reports.saved-views.index', \$this->managementReturnQuery(\$request))",
        ] as $marker) {
            $this->assertStringContainsString($marker, $controller);
        }

        foreach ([
            'data-testid="report-saved-views-bulk-return-search"',
            'data-testid="report-saved-views-bulk-return-report-key"',
            'data-testid="report-saved-views-bulk-return-per-page"',
            'data-testid="report-saved-views-bulk-return-page"',
            'name="return_search"',
            'name="return_report_key"',
            'name="return_per_page"',
            'name="return_page"',
        ] as $marker) {
            $this->assertStringContainsString($marker, $view);
        }
    }

    public function test_phase_68d_preserves_phase_68c_bulk_selection_ux_markers(): void
    {
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            'data-testid="report-saved-views-bulk-action-form"',
            'data-testid="report-saved-views-bulk-delete-button"',
            'data-testid="report-saved-views-selected-count"',
            'data-testid="report-saved-views-select-all-checkbox"',
            'data-testid="report-saved-view-bulk-select-checkbox"',
            'function updateBulkSelectionState()',
            'bulkDeleteButton.disabled = selectedCount === 0',
            "selectedCountLabel.textContent = 'المحدد: ' + selectedCount",
        ] as $marker) {
            $this->assertStringContainsString($marker, $view);
        }
    }

    public function test_phase_68d_json_contract_documents_context_preservation(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-68d-saved-view-bulk-delete-management-context.json')),
            true
        );

        $this->assertSame('Phase 68D', $contract['phase']);
        $this->assertSame('Phase 68C clean', $contract['baseline']['phase']);
        $this->assertSame('c5eb843', $contract['baseline']['commit']);
        $this->assertSame('1309 passed / 11545 assertions', $contract['baseline']['previous_tests']);

        foreach ([
            'bulk_form_carries_return_search',
            'bulk_form_carries_return_report_key',
            'bulk_form_carries_return_per_page',
            'bulk_form_carries_return_page',
            'bulk_destroy_validates_return_context',
            'bulk_destroy_redirect_preserves_valid_context',
            'invalid_return_report_key_is_dropped',
            'bulk_destroy_user_scope_preserved',
            'phase_68c_selection_ux_preserved',
            'phase_67_search_filter_pagination_controls_preserved',
        ] as $key) {
            $this->assertTrue($contract['implemented_behavior'][$key], $key);
        }
    }
}
