<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseFilterQueryNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_query_parameter_is_not_counted_as_active_expense_filter(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'page' => 6,
        ]));

        $response->assertOk();

        $response->assertSee('data-testid="expense-page-status-bar"', false);
        $response->assertSee('بدون فلاتر نشطة');
        $response->assertSee('data-page-active-filter-count="0"', false);
        $response->assertSee('data-page-has-active-filters="no"', false);

        $response->assertDontSee('data-testid="expense-active-filter-alert"', false);

        $response->assertSee('data-testid="expense-active-filter-count-card"', false);
        $response->assertSee('data-active-filter-count="0"', false);
        $response->assertSee('data-testid="expense-active-filter-count">0</strong>', false);

        $response->assertSee('data-testid="expense-active-filter-labels-card"', false);
        $response->assertSee('data-testid="expense-no-active-filter-labels"', false);
        $response->assertSee('لا توجد فلاتر مصروفات نشطة حاليًا');

        $response->assertDontSee('data-filter-key="page"', false);
    }

    public function test_empty_expense_query_values_are_not_counted_as_active_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => '',
            'status' => '',
            'date_from' => '',
            'date_to' => '',
            'has_attachment' => '',
            'page' => 2,
        ]));

        $response->assertOk();

        $response->assertSee('data-testid="expense-page-status-bar"', false);
        $response->assertSee('بدون فلاتر نشطة');
        $response->assertSee('data-page-active-filter-count="0"', false);
        $response->assertSee('data-page-has-active-filters="no"', false);

        $response->assertDontSee('data-testid="expense-active-filter-alert"', false);
        $response->assertDontSee('تنبيه: توجد فلاتر نشطة');

        $response->assertSee('data-testid="expense-active-filter-count-card"', false);
        $response->assertSee('data-active-filter-count="0"', false);

        $response->assertSee('data-testid="expense-active-filter-labels-card"', false);
        $response->assertSee('data-testid="expense-no-active-filter-labels"', false);

        $response->assertDontSee('data-filter-key="payment_method"', false);
        $response->assertDontSee('data-filter-key="status"', false);
        $response->assertDontSee('data-filter-key="date_from"', false);
        $response->assertDontSee('data-filter-key="date_to"', false);
        $response->assertDontSee('data-filter-key="has_attachment"', false);
        $response->assertDontSee('data-filter-key="page"', false);
    }

    public function test_mixed_empty_and_valid_expense_query_values_count_only_valid_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'status' => '',
            'date_from' => '',
            'date_to' => '2026-01-31',
            'page' => 5,
        ]));

        $response->assertOk();

        $response->assertSee('data-testid="expense-page-status-bar"', false);
        $response->assertSee('فلترة نشطة');
        $response->assertSee('data-page-active-filter-count="2"', false);
        $response->assertSee('data-page-has-active-filters="yes"', false);

        $response->assertSee('data-testid="expense-active-filter-alert"', false);
        $response->assertSee('data-active-filter-alert-count="2"', false);

        $response->assertSee('data-testid="expense-active-filter-count-card"', false);
        $response->assertSee('data-active-filter-count="2"', false);
        $response->assertSee('data-testid="expense-active-filter-count">2</strong>', false);

        $response->assertSee('data-testid="expense-active-filter-labels-card"', false);

        $response->assertSee('data-filter-key="payment_method"', false);
        $response->assertSee('طريقة الدفع');
        $response->assertSee('نقدًا');

        $response->assertSee('data-filter-key="date_to"', false);
        $response->assertSee('إلى تاريخ');
        $response->assertSee('2026-01-31');

        $response->assertDontSee('data-filter-key="status"', false);
        $response->assertDontSee('data-filter-key="date_from"', false);
        $response->assertDontSee('data-filter-key="page"', false);
        $response->assertDontSee('data-active-filter-alert-count="3"', false);
    }
}
