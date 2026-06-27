<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueEmptyQueryValuesIgnoredAsFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_scalar_query_values_are_not_counted_as_active_revenue_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index', [
            'collection_method' => '',
            'collection_status' => '',
            'archive_status' => '',
            'date_from' => '',
            'date_to' => '',
            'page' => 2,
        ]));

        $response->assertOk();

        $response->assertSee('data-testid="revenue-page-status-bar"', false);
        $response->assertSee('بدون فلاتر نشطة');
        $response->assertSee('data-page-active-filter-count="0"', false);
        $response->assertSee('data-page-has-active-filters="no"', false);

        $response->assertSee('data-testid="revenue-active-filter-count-card"', false);
        $response->assertSee('data-active-filter-count="0"', false);
        $response->assertSee('data-testid="revenue-active-filter-count">0</strong>', false);

        $response->assertSee('data-testid="revenue-active-filter-labels-card"', false);
        $response->assertSee('data-testid="revenue-no-active-filter-labels"', false);
        $response->assertSee('لا توجد فلاتر نشطة حاليًا');

        $response->assertDontSee('data-testid="revenue-active-filter-alert"', false);
        $response->assertDontSee('تنبيه: توجد فلاتر نشطة');
        $response->assertDontSee('data-filter-key="collection_method"', false);
        $response->assertDontSee('data-filter-key="collection_status"', false);
        $response->assertDontSee('data-filter-key="archive_status"', false);
        $response->assertDontSee('data-filter-key="date_from"', false);
        $response->assertDontSee('data-filter-key="date_to"', false);
    }

    public function test_empty_array_query_values_are_not_counted_as_active_revenue_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index', [
            'branch_id' => ['', null],
            'revenue_category_id' => ['', null],
            'page' => 4,
        ]));

        $response->assertOk();

        $response->assertSee('data-testid="revenue-page-status-bar"', false);
        $response->assertSee('بدون فلاتر نشطة');
        $response->assertSee('data-page-active-filter-count="0"', false);
        $response->assertSee('data-page-has-active-filters="no"', false);

        $response->assertSee('data-testid="revenue-active-filter-count-card"', false);
        $response->assertSee('data-active-filter-count="0"', false);
        $response->assertSee('لا توجد فلاتر نشطة حاليًا');

        $response->assertDontSee('data-testid="revenue-active-filter-alert"', false);
        $response->assertDontSee('data-filter-key="branch_id"', false);
        $response->assertDontSee('data-filter-key="revenue_category_id"', false);
    }
}
