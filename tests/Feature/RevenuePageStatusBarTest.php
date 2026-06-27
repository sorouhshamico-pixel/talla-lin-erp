<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenuePageStatusBarTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenues_index_shows_status_bar_without_active_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index'));

        $response->assertOk();

        $response->assertSee('data-testid="revenue-page-status-bar"', false);
        $response->assertSee('حالة صفحة الإيرادات');
        $response->assertSee('بدون فلاتر نشطة');
        $response->assertSee('data-page-active-filter-count="0"', false);
        $response->assertSee('data-page-has-active-filters="no"', false);
        $response->assertSee('data-testid="revenue-page-status-filter-count"', false);
    }

    public function test_revenues_index_status_bar_counts_active_filters_and_ignores_page(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index', [
            'collection_method' => 'cash',
            'collection_status' => 'collected',
            'archive_status' => 'archived',
            'page' => 4,
        ]));

        $response->assertOk();

        $response->assertSee('data-testid="revenue-page-status-bar"', false);
        $response->assertSee('فلترة نشطة');
        $response->assertSee('data-page-active-filter-count="3"', false);
        $response->assertSee('data-page-has-active-filters="yes"', false);
        $response->assertDontSee('data-page-active-filter-count="4"', false);
    }

    public function test_revenue_page_status_bar_appears_before_filter_summary_cards(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index', [
            'collection_method' => 'cash',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $statusBarPosition = strpos($content, 'data-testid="revenue-page-status-bar"');
        $activeFilterCountPosition = strpos($content, 'data-testid="revenue-active-filter-count-card"');
        $filterLabelsPosition = strpos($content, 'data-testid="revenue-active-filter-labels-card"');
        $filterFormPosition = strpos($content, 'فلترة الإيرادات');

        $this->assertNotFalse($statusBarPosition);
        $this->assertNotFalse($activeFilterCountPosition);
        $this->assertNotFalse($filterLabelsPosition);
        $this->assertNotFalse($filterFormPosition);

        $this->assertLessThan($activeFilterCountPosition, $statusBarPosition);
        $this->assertLessThan($filterLabelsPosition, $statusBarPosition);
        $this->assertLessThan($filterFormPosition, $statusBarPosition);
    }
}
