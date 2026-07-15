<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase67DPerPageResultsSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_67d_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-67d-saved-view-management-per-page-results-summary.json'));
        $this->assertFileExists(base_path('docs/phase-67d-saved-view-management-per-page-results-summary.md'));
    }

    public function test_per_page_select_and_results_summary_are_rendered(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 18; $i++) {
            ReportSavedView::query()->create([
                'user_id' => $user->id,
                'report_key' => 'sales-invoice-aging',
                'name' => sprintf('عرض ملخص %02d', $i),
                'filters' => [],
                'is_default' => false,
            ]);
        }

        $this->actingAs($user)
            ->get(route('reports.saved-views.index', ['per_page' => 5]))
            ->assertOk()
            ->assertSee('data-testid="report-saved-views-per-page-select"', false)
            ->assertSee('value="5" selected', false)
            ->assertSee('data-testid="report-saved-views-results-summary"', false)
            ->assertSee('عرض 1 إلى 5 من 18 نتيجة')
            ->assertSee('data-testid="report-saved-views-per-page-summary"', false)
            ->assertSee('عدد النتائج في الصفحة: 5')
            ->assertSee('data-testid="report-saved-views-pagination"', false);
    }

    public function test_per_page_is_preserved_with_search_and_report_filter(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 7; $i++) {
            ReportSavedView::query()->create([
                'user_id' => $user->id,
                'report_key' => 'profit-loss',
                'name' => sprintf('أرباح محفوظة %02d', $i),
                'filters' => [],
                'is_default' => false,
            ]);
        }

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'تحصيل محفوظ',
            'filters' => [],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-views.index', [
                'search' => 'أرباح',
                'report_key' => 'profit-loss',
                'per_page' => 5,
            ]))
            ->assertOk()
            ->assertSee('data-testid="report-saved-views-active-filters"', false)
            ->assertSee('بحث: أرباح')
            ->assertSee('التقرير: تقرير الأرباح والخسائر')
            ->assertSee('value="5" selected', false)
            ->assertSee('عرض 1 إلى 5 من 7 نتيجة')
            ->assertSee('عدد النتائج في الصفحة: 5')
            ->assertSee('أرباح محفوظة')
            ->assertDontSee('تحصيل محفوظ');
    }

    public function test_empty_state_does_not_render_results_summary(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.saved-views.index', ['search' => 'لا توجد نتيجة', 'per_page' => 5]))
            ->assertOk()
            ->assertSee('data-testid="report-saved-views-per-page-select"', false)
            ->assertSee('data-testid="report-saved-views-filtered-empty-message"', false)
            ->assertDontSee('data-testid="report-saved-views-results-summary"', false)
            ->assertDontSee('data-testid="report-saved-views-per-page-summary"', false);
    }

    public function test_phase_67d_source_exposes_per_page_to_view_without_changing_service(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));
        $service = file_get_contents(app_path('Services/ReportSavedViewService.php'));

        $this->assertStringContainsString('$perPage = (int) ($validated[\'per_page\'] ?? 15);', $controller);
        $this->assertStringContainsString("'per_page' => \$savedViews->perPage()", $controller);
        $this->assertStringContainsString('data-testid="report-saved-views-per-page-select"', $view);
        $this->assertStringContainsString('data-testid="report-saved-views-results-summary"', $view);
        $this->assertStringContainsString('data-testid="report-saved-views-per-page-summary"', $view);
        $this->assertStringContainsString('public function paginateForManagement(', $service);
    }

    public function test_phase_67d_json_contract_documents_results_summary_and_per_page_control(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-67d-saved-view-management-per-page-results-summary.json')),
            true
        );

        $this->assertSame('Phase 67D', $contract['phase']);
        $this->assertSame('Phase 67C clean', $contract['baseline']['phase']);
        $this->assertSame('a53a825', $contract['baseline']['commit']);
        $this->assertSame('1275 passed / 11236 assertions', $contract['baseline']['previous_tests']);
        $this->assertSame([5, 10, 15, 25, 50, 100], $contract['implemented_behavior']['per_page_options']);

        foreach ([
            'per_page_select_visible',
            'selected_per_page_is_preserved',
            'controller_passes_per_page_to_view_filters',
            'results_range_summary_visible',
            'per_page_summary_visible',
            'empty_state_does_not_show_range_summary',
        ] as $key) {
            $this->assertTrue($contract['implemented_behavior'][$key], $key);
        }
    }
}
