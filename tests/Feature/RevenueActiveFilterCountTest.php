<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueActiveFilterCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenues_index_shows_zero_active_filters_without_query_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index'));

        $response->assertOk();

        $response->assertSee('data-testid="revenue-active-filter-count-card"', false);
        $response->assertSee('عدد الفلاتر النشطة');
        $response->assertSee('data-active-filter-count="0"', false);
        $response->assertSee('data-testid="revenue-active-filter-count">0</strong>', false);
    }

    public function test_revenues_index_counts_current_active_filters_and_ignores_page(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index', [
            'collection_method' => 'cash',
            'collection_status' => 'collected',
            'archive_status' => 'archived',
            'page' => 2,
        ]));

        $response->assertOk();

        $response->assertSee('data-testid="revenue-active-filter-count-card"', false);
        $response->assertSee('data-active-filter-count="3"', false);
        $response->assertSee('data-testid="revenue-active-filter-count">3</strong>', false);
    }
}
