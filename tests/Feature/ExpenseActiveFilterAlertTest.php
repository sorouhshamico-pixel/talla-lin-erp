<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseActiveFilterAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_does_not_show_active_filter_alert_without_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $response->assertSee('data-testid="expense-page-status-bar"', false);
        $response->assertSee('بدون فلاتر نشطة');
        $response->assertDontSee('data-testid="expense-active-filter-alert"', false);
        $response->assertDontSee('تنبيه: توجد فلاتر نشطة');
        $response->assertDontSee('data-active-filter-alert-count="0"', false);
    }

    public function test_expenses_index_shows_active_filter_alert_when_filters_are_applied(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'status' => 'paid',
        ]));

        $response->assertOk();

        $response->assertSee('data-testid="expense-active-filter-alert"', false);
        $response->assertSee('تنبيه: توجد فلاتر نشطة');
        $response->assertSee('النتائج المعروضة الآن لا تمثل كل المصروفات');
        $response->assertSee('data-active-filter-alert-count="2"', false);
        $response->assertSee('data-testid="expense-active-filter-alert-count"', false);
    }

    public function test_expense_active_filter_alert_ignores_page_and_empty_values(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'status' => '',
            'date_from' => '',
            'page' => 5,
        ]));

        $response->assertOk();

        $response->assertSee('data-testid="expense-active-filter-alert"', false);
        $response->assertSee('data-active-filter-alert-count="1"', false);
        $response->assertDontSee('data-active-filter-alert-count="2"', false);
        $response->assertDontSee('data-active-filter-alert-count="3"', false);
    }

    public function test_expense_active_filter_alert_appears_after_status_bar(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $statusBarPosition = strpos($content, 'data-testid="expense-page-status-bar"');
        $alertPosition = strpos($content, 'data-testid="expense-active-filter-alert"');

        $this->assertNotFalse($statusBarPosition);
        $this->assertNotFalse($alertPosition);

        $this->assertLessThan($alertPosition, $statusBarPosition);
    }
}
