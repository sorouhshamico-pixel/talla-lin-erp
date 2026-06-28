<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseActiveFilterLabelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_shows_no_active_filter_labels_without_query_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $response->assertSee('data-testid="expense-active-filter-labels-card"', false);
        $response->assertSee('فلاتر المصروفات النشطة الحالية');
        $response->assertSee('data-testid="expense-no-active-filter-labels"', false);
        $response->assertSee('لا توجد فلاتر مصروفات نشطة حاليًا');
    }

    public function test_expenses_index_shows_current_active_filter_labels_and_ignores_page_and_empty_values(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'status' => 'paid',
            'date_from' => '',
            'date_to' => '',
            'page' => 3,
        ]));

        $response->assertOk();

        $response->assertSee('data-testid="expense-active-filter-labels-card"', false);

        $response->assertSee('data-filter-key="payment_method"', false);
        $response->assertSee('طريقة الدفع');
        $response->assertSee('نقدًا');

        $response->assertSee('data-filter-key="status"', false);
        $response->assertSee('حالة المصروف');
        $response->assertSee('مدفوع');

        $response->assertDontSee('data-filter-key="date_from"', false);
        $response->assertDontSee('data-filter-key="date_to"', false);
        $response->assertDontSee('data-filter-key="page"', false);
    }

    public function test_expense_active_filter_labels_card_appears_after_active_filter_count_card(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $countCardPosition = strpos($content, 'data-testid="expense-active-filter-count-card"');
        $labelsCardPosition = strpos($content, 'data-testid="expense-active-filter-labels-card"');

        $this->assertNotFalse($countCardPosition);
        $this->assertNotFalse($labelsCardPosition);

        $this->assertLessThan($labelsCardPosition, $countCardPosition);
    }
}
