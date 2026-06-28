<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseActiveFilterCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_shows_zero_active_filters_without_query_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $response->assertSee('data-testid="expense-active-filter-count-card"', false);
        $response->assertSee('عدد فلاتر المصروفات النشطة');
        $response->assertSee('data-active-filter-count="0"', false);
        $response->assertSee('data-testid="expense-active-filter-count">0</strong>', false);
    }

    public function test_expenses_index_counts_active_filters_and_ignores_page_and_empty_values(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'status' => 'paid',
            'date_from' => '',
            'date_to' => '',
            'page' => 5,
        ]));

        $response->assertOk();

        $response->assertSee('data-testid="expense-active-filter-count-card"', false);
        $response->assertSee('data-active-filter-count="2"', false);
        $response->assertSee('data-testid="expense-active-filter-count">2</strong>', false);
        $response->assertDontSee('data-active-filter-count="3"', false);
        $response->assertDontSee('data-active-filter-count="4"', false);
    }

    public function test_expense_active_filter_count_card_appears_after_status_and_alert_when_filters_exist(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $statusBarPosition = strpos($content, 'data-testid="expense-page-status-bar"');
        $alertPosition = strpos($content, 'data-testid="expense-active-filter-alert"');
        $countCardPosition = strpos($content, 'data-testid="expense-active-filter-count-card"');

        $this->assertNotFalse($statusBarPosition);
        $this->assertNotFalse($alertPosition);
        $this->assertNotFalse($countCardPosition);

        $this->assertLessThan($alertPosition, $statusBarPosition);
        $this->assertLessThan($countCardPosition, $alertPosition);
    }
}
