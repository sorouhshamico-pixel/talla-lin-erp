<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueActiveFilterLabelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenues_index_shows_no_active_filter_labels_without_query_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index'));

        $response->assertOk();

        $response->assertSee('data-testid="revenue-active-filter-labels-card"', false);
        $response->assertSee('الفلاتر النشطة الحالية');
        $response->assertSee('data-testid="revenue-no-active-filter-labels"', false);
        $response->assertSee('لا توجد فلاتر نشطة حاليًا');
    }

    public function test_revenues_index_shows_current_active_filter_labels_and_ignores_page(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index', [
            'collection_method' => 'cash',
            'collection_status' => 'collected',
            'archive_status' => 'archived',
            'page' => 3,
        ]));

        $response->assertOk();

        $response->assertSee('data-testid="revenue-active-filter-labels-card"', false);
        $response->assertSee('طريقة التحصيل');
        $response->assertSee('نقدًا');
        $response->assertSee('حالة التحصيل');
        $response->assertSee('محصل');
        $response->assertSee('حالة الأرشفة');
        $response->assertSee('مؤرشف');

        $response->assertSee('data-filter-key="collection_method"', false);
        $response->assertSee('data-filter-key="collection_status"', false);
        $response->assertSee('data-filter-key="archive_status"', false);
        $response->assertDontSee('data-filter-key="page"', false);
    }
}
