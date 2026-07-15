<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase67BPaginationSearchImplementationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_67b_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-67b-saved-view-management-pagination-search-implementation.json'));
        $this->assertFileExists(base_path('docs/phase-67b-saved-view-management-pagination-search-implementation.md'));
    }

    public function test_management_page_renders_search_report_filter_and_pagination_controls(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 18; $i++) {
            ReportSavedView::query()->create([
                'user_id' => $user->id,
                'report_key' => 'sales-invoice-aging',
                'name' => sprintf('عرض إدارة %02d', $i),
                'filters' => [
                    'payment_status' => $i % 2 === 0 ? 'partial' : 'unpaid',
                ],
                'is_default' => false,
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('reports.saved-views.index', [
                'search' => 'عرض إدارة',
                'report_key' => 'sales-invoice-aging',
            ]))
            ->assertOk();

        $response->assertSee('data-testid="report-saved-views-search-form"', false);
        $response->assertSee('data-testid="report-saved-views-search-input"', false);
        $response->assertSee('data-testid="report-saved-views-report-key-select"', false);
        $response->assertSee('data-testid="report-saved-views-search-submit-button"', false);
        $response->assertSee('data-testid="report-saved-views-search-clear-link"', false);
        $response->assertSee('data-testid="report-saved-views-pagination"', false);
        $response->assertSee('عدد العروض المحفوظة: 18');
        $response->assertSee('value="عرض إدارة"', false);
        $response->assertSee('value="sales-invoice-aging" selected', false);
    }

    public function test_search_filters_saved_views_by_name(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'تحصيل عاجل',
            'filters' => [],
            'is_default' => false,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'أرباح شهرية',
            'filters' => [],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-views.index', ['search' => 'تحصيل']))
            ->assertOk()
            ->assertSee('تحصيل عاجل')
            ->assertDontSee('أرباح شهرية')
            ->assertSee('عدد العروض المحفوظة: 1');
    }

    public function test_report_key_filter_limits_results_to_selected_report(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض مبيعات',
            'filters' => [],
            'is_default' => false,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'عرض أرباح',
            'filters' => [],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-views.index', ['report_key' => 'profit-loss']))
            ->assertOk()
            ->assertSee('عرض أرباح')
            ->assertSee('تقرير الأرباح والخسائر')
            ->assertDontSee('عرض مبيعات')
            ->assertSee('عدد العروض المحفوظة: 1');
    }

    public function test_search_matches_registry_report_label(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'عرض مالي',
            'filters' => [],
            'is_default' => false,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض مبيعات',
            'filters' => [],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-views.index', ['search' => 'الأرباح']))
            ->assertOk()
            ->assertSee('عرض مالي')
            ->assertSee('تقرير الأرباح والخسائر')
            ->assertDontSee('عرض مبيعات');
    }

    public function test_search_matches_common_filter_display_label(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض جزئي',
            'filters' => [
                'payment_status' => 'partial',
            ],
            'is_default' => false,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض غير مدفوع',
            'filters' => [
                'payment_status' => 'unpaid',
            ],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-views.index', ['search' => 'مدفوعة جزئيًا']))
            ->assertOk()
            ->assertSee('عرض جزئي')
            ->assertSee('مدفوعة جزئيًا')
            ->assertDontSee('عرض غير مدفوع');
    }

    public function test_invalid_report_key_filter_is_ignored_safely(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض متاح',
            'filters' => [],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-views.index', ['report_key' => 'not-a-real-report']))
            ->assertOk()
            ->assertSee('عرض متاح')
            ->assertSee('عدد العروض المحفوظة: 1');
    }

    public function test_phase_67b_source_uses_paginated_management_query_and_preserves_query_string(): void
    {
        $service = file_get_contents(app_path('Services/ReportSavedViewService.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));
        $view = file_get_contents(resource_path('views/reports/saved-views/index.blade.php'));

        $this->assertStringContainsString('public function paginateForManagement(', $service);
        $this->assertStringContainsString('LengthAwarePaginator', $service);
        $this->assertStringContainsString('->paginate($perPage)', $service);
        $this->assertStringContainsString('->withQueryString()', $service);

        $this->assertStringContainsString('$savedViewService->paginateForManagement(', $controller);
        $this->assertStringContainsString('$savedViews->getCollection()->transform(', $controller);
        $this->assertStringContainsString("'totalSavedViews' => \$savedViews->total()", $controller);
        $this->assertStringContainsString('matchingReportKeysForSearch', $controller);
        $this->assertStringContainsString('matchingFilterValuesForSearch', $controller);

        $this->assertStringContainsString('data-testid="report-saved-views-search-form"', $view);
        $this->assertStringContainsString('data-testid="report-saved-views-pagination"', $view);
        $this->assertStringContainsString('$savedViews->links()', $view);
        $this->assertStringContainsString('$savedViews->count() === 0', $view);
    }

    public function test_phase_67b_json_contract_documents_implementation(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-67b-saved-view-management-pagination-search-implementation.json')),
            true
        );

        $this->assertSame('Phase 67B', $contract['phase']);
        $this->assertSame('Phase 67A clean', $contract['baseline']['phase']);
        $this->assertSame('5990865', $contract['baseline']['commit']);

        foreach ([
            'search_input',
            'report_key_filter',
            'pagination',
            'pagination_preserves_query_string',
            'search_matches_saved_view_name',
            'search_matches_report_key',
            'search_matches_registry_report_label',
            'search_matches_raw_filter_payload',
            'search_matches_common_filter_display_labels',
            'invalid_report_key_filter_is_ignored',
        ] as $key) {
            $this->assertTrue($contract['implemented_behavior'][$key], $key);
        }
    }
}
