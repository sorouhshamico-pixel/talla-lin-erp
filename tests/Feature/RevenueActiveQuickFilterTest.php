<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueActiveQuickFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenues_index_shows_active_quick_filter_link(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index'));

        $response->assertOk();

        $response->assertSee('data-testid="revenue-active-quick-filter-card"', false);
        $response->assertSee('فلتر الإيرادات النشطة');
        $response->assertSee('عرض الإيرادات النشطة');
        $response->assertSee('archive_status=active', false);
    }

    public function test_active_quick_filter_preserves_current_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index', [
            'collection_method' => 'cash',
            'collection_status' => 'uncollected',
        ]));

        $response->assertOk();

        $response->assertSee('collection_method=cash', false);
        $response->assertSee('collection_status=uncollected', false);
        $response->assertSee('archive_status=active', false);
    }
}
