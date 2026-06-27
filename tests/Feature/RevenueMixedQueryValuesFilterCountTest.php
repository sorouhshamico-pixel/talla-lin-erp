<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueMixedQueryValuesFilterCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_mixed_empty_and_valid_scalar_query_values_count_only_valid_revenue_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index', [
            'collection_method' => '',
            'collection_status' => 'collected',
            'archive_status' => '',
            'date_from' => '',
            'date_to' => '',
            'page' => 3,
        ]));

        $response->assertOk();

        $response->assertSee('data-testid="revenue-page-status-bar"', false);
        $response->assertSee('فلترة نشطة');
        $response->assertSee('data-page-active-filter-count="1"', false);
        $response->assertSee('data-page-has-active-filters="yes"', false);

        $response->assertSee('data-testid="revenue-active-filter-count-card"', false);
        $response->assertSee('data-active-filter-count="1"', false);
        $response->assertSee('data-testid="revenue-active-filter-count">1</strong>', false);

        $response->assertSee('data-testid="revenue-active-filter-labels-card"', false);
        $response->assertSee('data-filter-key="collection_status"', false);
        $response->assertSee('حالة التحصيل');
        $response->assertSee('محصل');

        $response->assertSee('data-testid="revenue-active-filter-alert"', false);
        $response->assertSee('data-active-filter-alert-count="1"', false);

        $response->assertDontSee('data-filter-key="collection_method"', false);
        $response->assertDontSee('data-filter-key="archive_status"', false);
        $response->assertDontSee('data-filter-key="date_from"', false);
        $response->assertDontSee('data-filter-key="date_to"', false);
        $response->assertDontSee('data-filter-key="page"', false);
    }

    public function test_mixed_empty_and_valid_array_query_values_count_each_key_once(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index', [
            'status_group' => ['', 'urgent'],
            'source_group' => ['', 'website'],
            'empty_group' => ['', null],
            'page' => 7,
        ]));

        $response->assertOk();

        $response->assertSee('data-testid="revenue-page-status-bar"', false);
        $response->assertSee('فلترة نشطة');
        $response->assertSee('data-page-active-filter-count="2"', false);
        $response->assertSee('data-page-has-active-filters="yes"', false);

        $response->assertSee('data-testid="revenue-active-filter-count-card"', false);
        $response->assertSee('data-active-filter-count="2"', false);
        $response->assertSee('data-testid="revenue-active-filter-count">2</strong>', false);

        $response->assertSee('data-testid="revenue-active-filter-labels-card"', false);
        $response->assertSee('data-filter-key="status_group"', false);
        $response->assertSee('urgent');
        $response->assertSee('data-filter-key="source_group"', false);
        $response->assertSee('website');

        $response->assertSee('data-testid="revenue-active-filter-alert"', false);
        $response->assertSee('data-active-filter-alert-count="2"', false);

        $response->assertDontSee('data-filter-key="empty_group"', false);
        $response->assertDontSee('data-filter-key="page"', false);
        $response->assertDontSee('data-active-filter-alert-count="3"', false);
    }
}
