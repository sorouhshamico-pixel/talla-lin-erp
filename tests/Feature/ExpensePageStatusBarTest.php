<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpensePageStatusBarTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_shows_status_bar_without_active_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $response->assertSee('data-testid="expense-page-status-bar"', false);
        $response->assertSee('حالة صفحة المصروفات');
        $response->assertSee('بدون فلاتر نشطة');
        $response->assertSee('data-page-active-filter-count="0"', false);
        $response->assertSee('data-page-has-active-filters="no"', false);
        $response->assertSee('data-testid="expense-page-status-filter-count"', false);
    }

    public function test_expenses_index_status_bar_counts_active_filters_and_ignores_page(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'status' => 'paid',
            'page' => 4,
        ]));

        $response->assertOk();

        $response->assertSee('data-testid="expense-page-status-bar"', false);
        $response->assertSee('فلترة نشطة');
        $response->assertSee('data-page-active-filter-count="2"', false);
        $response->assertSee('data-page-has-active-filters="yes"', false);
        $response->assertDontSee('data-page-active-filter-count="3"', false);
    }

    public function test_expenses_index_status_bar_ignores_empty_query_values(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => '',
            'status' => '',
            'date_from' => '',
            'date_to' => '',
            'page' => 2,
        ]));

        $response->assertOk();

        $response->assertSee('data-testid="expense-page-status-bar"', false);
        $response->assertSee('بدون فلاتر نشطة');
        $response->assertSee('data-page-active-filter-count="0"', false);
        $response->assertSee('data-page-has-active-filters="no"', false);
    }
}
