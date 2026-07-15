<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase66CSavedViewEditFiltersReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_phase_66c_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-66c-saved-view-edit-filters-read-only.json'));
        $this->assertFileExists(base_path('docs/phase-66c-saved-view-edit-filters-read-only.md'));
    }

    public function test_edit_view_renders_saved_filters_as_read_only_values(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض قراءة فقط',
            'filters' => [
                'payment_status' => 'partial',
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.saved-views.edit', $savedView->id))
            ->assertOk();

        $response->assertSee('data-testid="report-saved-view-edit-filter-list"', false);
        $response->assertSee('data-testid="report-saved-view-edit-filter-value"', false);
        $response->assertSee('data-testid="report-saved-view-edit-filter-raw-value"', false);
        $response->assertSee('مدفوعة جزئيًا');
        $response->assertSee('بدون تاريخ استحقاق');
        $response->assertSee('partial');
        $response->assertSee('without_due_date');

        $response->assertDontSee('data-testid="report-saved-view-edit-filter-input"', false);
        $response->assertDontSee('name="filters[', false);
        $response->assertDontSee('old(&#039;filters.', false);
    }

    public function test_update_ignores_submitted_filter_payload_and_preserves_existing_filters(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'اسم قبل التعديل',
            'filters' => [
                'payment_status' => 'partial',
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->patch(route('reports.saved-views.update', $savedView->id), [
                'name' => 'اسم بعد التعديل',
                'filters' => [
                    'payment_status' => 'paid',
                    'aging_bucket' => 'not_due',
                    'unexpected' => 'should-not-persist',
                ],
            ])
            ->assertRedirect(route('reports.saved-views.index'));

        $savedView->refresh();

        $this->assertSame('اسم بعد التعديل', $savedView->name);
        $this->assertSame([
            'payment_status' => 'partial',
            'aging_bucket' => 'without_due_date',
        ], $savedView->filters);
    }

    public function test_controller_no_longer_accepts_generic_filter_mutation_rules(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));

        $this->assertStringNotContainsString("'filters' => ['nullable', 'array']", $controller);
        $this->assertStringNotContainsString("'filters.*' => ['nullable', 'string', 'max:255']", $controller);
        $this->assertStringNotContainsString('$validated[\'filters\']', $controller);
        $this->assertStringNotContainsString("'filters' => \$filters", $controller);
    }

    public function test_phase_66c_json_contract_documents_read_only_filter_decision(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-66c-saved-view-edit-filters-read-only.json')),
            true
        );

        $this->assertSame('Phase 66C', $contract['phase']);
        $this->assertSame('Phase 66B clean', $contract['baseline']['phase']);
        $this->assertSame('09f9e39', $contract['baseline']['commit']);
        $this->assertSame('read_only', $contract['decision']['saved_view_edit_filters']);
        $this->assertTrue($contract['behavior']['display_saved_filters']);
        $this->assertFalse($contract['behavior']['render_filter_inputs']);
        $this->assertFalse($contract['behavior']['accept_filter_mutation']);
        $this->assertTrue($contract['behavior']['preserve_existing_filters']);
        $this->assertContains('edit_filter_mutation_risk', $contract['resolved_findings']);
    }
}
