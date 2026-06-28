<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseUnpaidPaymentStatusQuickFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_shows_unpaid_payment_status_quick_filter_card(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $response->assertSee('data-testid="expense-unpaid-quick-filter-card"', false);
        $response->assertSee('data-quick-filter-card="expense"', false);
        $response->assertSee('data-quick-filter-style="unified"', false);
        $response->assertSee('مصروفات غير مدفوعة');
        $response->assertSee('عرض المصروفات غير المدفوعة');
        $response->assertSee('data-testid="expense-unpaid-quick-filter"', false);
        $response->assertSee('payment_status=unpaid', false);
    }

    public function test_unpaid_payment_status_quick_filter_preserves_current_expense_filters_and_overrides_payment_status(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'date_to' => '2026-01-31',
            'page' => 3,
        ]));

        $response->assertOk();

        $content = $response->getContent();

        preg_match(
            '/<a\s+[^>]*href="([^"]+)"[^>]*data-testid="expense-unpaid-quick-filter"[^>]*>/s',
            $content,
            $matches
        );

        $this->assertNotEmpty($matches, 'Unpaid expense quick filter link was not found.');

        $href = html_entity_decode($matches[1]);

        $this->assertStringContainsString('payment_method=cash', $href);
        $this->assertStringContainsString('date_to=2026-01-31', $href);
        $this->assertStringContainsString('page=3', $href);
        $this->assertStringContainsString('payment_status=unpaid', $href);
        $this->assertStringNotContainsString('payment_status=paid', $href);
    }

    public function test_unpaid_payment_status_quick_filter_appears_after_paid_quick_filter(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $paidQuickFilterPosition = strpos($content, 'data-testid="expense-paid-quick-filter-card"');
        $unpaidQuickFilterPosition = strpos($content, 'data-testid="expense-unpaid-quick-filter-card"');

        $this->assertNotFalse($paidQuickFilterPosition);
        $this->assertNotFalse($unpaidQuickFilterPosition);

        $this->assertLessThan($unpaidQuickFilterPosition, $paidQuickFilterPosition);
    }
}
