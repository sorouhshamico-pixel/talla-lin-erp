<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase66EManagementTableActionsUxCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_66e_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-66e-saved-view-management-table-actions-ux-cleanup.json'));
        $this->assertFileExists(base_path('docs/phase-66e-saved-view-management-table-actions-ux-cleanup.md'));
    }

    public function test_saved_view_management_row_actions_are_grouped_without_removing_existing_action_controls(): void
    {
        $user = User::factory()->create();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض إجراءات مرتب',
            'filters' => [
                'payment_status' => 'partial',
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.saved-views.index'))
            ->assertOk();

        $response->assertSee('الإجراءات');
        $response->assertSee('data-testid="report-saved-view-actions"', false);
        $response->assertSee('data-testid="report-saved-view-primary-actions"', false);
        $response->assertSee('data-testid="report-saved-view-secondary-actions"', false);
        $response->assertSee('data-testid="report-saved-view-danger-actions"', false);

        $response->assertSee('data-testid="report-saved-view-open-link"', false);
        $response->assertSee('data-testid="report-saved-view-apply-link"', false);
        $response->assertSee('data-testid="report-saved-view-edit-link"', false);
        $response->assertSee('data-testid="report-saved-view-duplicate-form"', false);
        $response->assertSee('data-testid="report-saved-view-duplicate-button"', false);
        $response->assertSee('data-testid="report-saved-view-make-default-form"', false);
        $response->assertSee('data-testid="report-saved-view-make-default-button"', false);
        $response->assertSee('data-testid="report-saved-view-delete-button"', false);

        $response->assertSee(route('reports.saved-views.edit', $savedView->id), false);
        $response->assertSee(route('reports.saved-views.apply', $savedView->id), false);
        $response->assertSee(route('reports.saved-views.duplicate', $savedView->id), false);
        $response->assertSee(route('reports.saved-views.make-default', $savedView->id), false);
        $response->assertSee(route('reports.saved-views.destroy', $savedView->id), false);
    }

    public function test_default_saved_view_keeps_grouped_actions_without_make_default_button(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض افتراضي مرتب',
            'filters' => [],
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.saved-views.index'))
            ->assertOk();

        $response->assertSee('data-testid="report-saved-view-actions"', false);
        $response->assertSee('data-testid="report-saved-view-primary-actions"', false);
        $response->assertSee('data-testid="report-saved-view-secondary-actions"', false);
        $response->assertSee('data-testid="report-saved-view-danger-actions"', false);
        $response->assertSee('data-testid="report-saved-view-default-badge"', false);
        $response->assertDontSee('data-testid="report-saved-view-make-default-button"', false);
    }

    public function test_phase_66e_view_preserves_existing_action_test_ids_and_adds_group_markers(): void
    {
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            'data-testid="report-saved-view-actions"',
            'data-testid="report-saved-view-primary-actions"',
            'data-testid="report-saved-view-secondary-actions"',
            'data-testid="report-saved-view-danger-actions"',
            'data-testid="report-saved-view-open-link"',
            'data-testid="report-saved-view-edit-link"',
            'data-testid="report-saved-view-apply-link"',
            'data-testid="report-saved-view-duplicate-button"',
            'data-testid="report-saved-view-make-default-button"',
            'data-testid="report-saved-view-delete-button"',
        ] as $marker) {
            $this->assertStringContainsString($marker, $view);
        }

        $this->assertStringContainsString('<th>الإجراءات</th>', $view);
        $this->assertStringNotContainsString('<th>إجراء</th>', $view);
    }

    public function test_phase_66e_json_contract_documents_actions_ux_cleanup(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-66e-saved-view-management-table-actions-ux-cleanup.json')),
            true
        );

        $this->assertSame('Phase 66E', $contract['phase']);
        $this->assertSame('Phase 66D clean', $contract['baseline']['phase']);
        $this->assertSame('ba529f5', $contract['baseline']['commit']);
        $this->assertSame('1242 passed / 10966 assertions', $contract['baseline']['previous_tests']);
        $this->assertSame('الإجراءات', $contract['ux_contract']['actions_column_heading']);
        $this->assertSame(['open', 'apply'], $contract['ux_contract']['primary_actions_group']);
        $this->assertSame(['edit', 'duplicate', 'make_default'], $contract['ux_contract']['secondary_actions_group']);
        $this->assertSame(['delete'], $contract['ux_contract']['danger_actions_group']);
        $this->assertTrue($contract['ux_contract']['preserve_existing_action_test_ids']);
        $this->assertContains('index_action_density', $contract['resolved_findings']);
    }
}
