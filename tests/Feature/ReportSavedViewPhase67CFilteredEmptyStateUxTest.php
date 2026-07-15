<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase67CFilteredEmptyStateUxTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_67c_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-67c-saved-view-management-filtered-empty-state-ux.json'));
        $this->assertFileExists(base_path('docs/phase-67c-saved-view-management-filtered-empty-state-ux.md'));
    }

    public function test_active_filter_summary_is_rendered_for_search_and_report_filter(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'عرض أرباح الربع الأول',
            'filters' => [],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-views.index', [
                'search' => 'أرباح',
                'report_key' => 'profit-loss',
            ]))
            ->assertOk()
            ->assertSee('data-testid="report-saved-views-active-filters"', false)
            ->assertSee('data-testid="report-saved-views-active-search"', false)
            ->assertSee('بحث: أرباح')
            ->assertSee('data-testid="report-saved-views-active-report-key"', false)
            ->assertSee('التقرير: تقرير الأرباح والخسائر')
            ->assertSee('data-testid="report-saved-views-active-filters-clear-link"', false)
            ->assertSee('عرض أرباح الربع الأول');
    }

    public function test_filtered_empty_state_has_specific_message_and_clear_link(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض تحصيل',
            'filters' => [],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-views.index', [
                'search' => 'لا يوجد هذا النص',
                'report_key' => 'profit-loss',
            ]))
            ->assertOk()
            ->assertSee('data-testid="report-saved-views-active-filters"', false)
            ->assertSee('data-testid="report-saved-views-filtered-empty-message"', false)
            ->assertSee('لا توجد نتائج مطابقة للفلاتر الحالية.')
            ->assertSee('data-testid="report-saved-views-filtered-empty-clear-link"', false)
            ->assertSee('عرض كل العروض')
            ->assertDontSee('data-testid="report-saved-views-unfiltered-empty-message"', false);
    }

    public function test_unfiltered_empty_state_message_is_preserved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.saved-views.index'))
            ->assertOk()
            ->assertSee('data-testid="report-saved-views-empty"', false)
            ->assertSee('data-testid="report-saved-views-unfiltered-empty-message"', false)
            ->assertSee('لا توجد عروض محفوظة حاليًا.')
            ->assertDontSee('data-testid="report-saved-views-active-filters"', false)
            ->assertDontSee('data-testid="report-saved-views-filtered-empty-message"', false);
    }

    public function test_phase_67c_view_has_filtered_empty_state_markers_without_changing_search_and_pagination_markers(): void
    {
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        foreach ([
            'data-testid="report-saved-views-search-form"',
            'data-testid="report-saved-views-search-input"',
            'data-testid="report-saved-views-report-key-select"',
            'data-testid="report-saved-views-pagination"',
            'data-testid="report-saved-views-active-filters"',
            'data-testid="report-saved-views-active-search"',
            'data-testid="report-saved-views-active-report-key"',
            'data-testid="report-saved-views-active-filters-clear-link"',
            'data-testid="report-saved-views-filtered-empty-message"',
            'data-testid="report-saved-views-filtered-empty-clear-link"',
            'data-testid="report-saved-views-unfiltered-empty-message"',
        ] as $marker) {
            $this->assertStringContainsString($marker, $view);
        }

        $this->assertStringContainsString('لا توجد نتائج مطابقة للفلاتر الحالية.', $view);
        $this->assertStringContainsString('لا توجد عروض محفوظة حاليًا.', $view);
    }

    public function test_phase_67c_json_contract_documents_filtered_empty_state_ux(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-67c-saved-view-management-filtered-empty-state-ux.json')),
            true
        );

        $this->assertSame('Phase 67C', $contract['phase']);
        $this->assertSame('Phase 67B clean', $contract['baseline']['phase']);
        $this->assertSame('e974436', $contract['baseline']['commit']);
        $this->assertSame('1269 passed / 11189 assertions', $contract['baseline']['previous_tests']);

        foreach ([
            'active_filter_summary',
            'active_search_label',
            'active_report_filter_label',
            'clear_active_filters_link',
            'filtered_empty_state_message',
            'filtered_empty_state_clear_link',
            'unfiltered_empty_state_preserved',
        ] as $key) {
            $this->assertTrue($contract['implemented_behavior'][$key], $key);
        }
    }
}
