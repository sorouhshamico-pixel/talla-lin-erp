<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenuePageQueryIgnoredAsFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_query_parameter_is_not_counted_as_active_revenue_filter(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index', [
            'page' => 3,
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
        $response->assertDontSee('data-filter-key="page"', false);
    }
}
