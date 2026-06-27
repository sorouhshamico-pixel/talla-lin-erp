<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueArchivedQuickFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenues_index_shows_archived_quick_filter_link(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index'));

        $response->assertOk();

        $response->assertSee('data-testid="revenue-archived-quick-filter-card"', false);
        $response->assertSee('فلتر الإيرادات المؤرشفة');
        $response->assertSee('عرض الإيرادات المؤرشفة');
        $response->assertSee('archive_status=archived', false);
    }

    public function test_archived_quick_filter_preserves_current_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index', [
            'collection_method' => 'cash',
            'collection_status' => 'collected',
        ]));

        $response->assertOk();

        $response->assertSee('collection_method=cash', false);
        $response->assertSee('collection_status=collected', false);
        $response->assertSee('archive_status=archived', false);
    }
}
