<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseLargeAmountQuickFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_shows_large_amount_quick_filter_card(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $response->assertSee('data-testid="expense-large-amount-quick-filter-card"', false);
        $response->assertSee('data-quick-filter-card="expense"', false);
        $response->assertSee('data-quick-filter-style="unified"', false);
        $response->assertSee('مصروفات كبيرة');
        $response->assertSee('عرض المصروفات الكبيرة');
        $response->assertSee('data-testid="expense-large-amount-quick-filter"', false);
        $response->assertSee('large_amount=1', false);
    }

    public function test_large_amount_quick_filter_preserves_current_expense_filters_and_overrides_large_amount(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'date_to' => '2026-01-31',
            'large_amount' => '0',
            'page' => 3,
        ]));

        $response->assertOk();

        $content = $response->getContent();

        preg_match(
            '/<a\s+[^>]*href="([^"]+)"[^>]*data-testid="expense-large-amount-quick-filter"[^>]*>/s',
            $content,
            $matches
        );

        $this->assertNotEmpty($matches, 'Large amount expense quick filter link was not found.');

        $href = html_entity_decode($matches[1]);

        $this->assertStringContainsString('payment_method=cash', $href);
        $this->assertStringContainsString('payment_status=paid', $href);
        $this->assertStringContainsString('date_to=2026-01-31', $href);
        $this->assertStringContainsString('page=3', $href);
        $this->assertStringContainsString('large_amount=1', $href);
        $this->assertStringNotContainsString('large_amount=0', $href);
    }

    public function test_large_amount_quick_filter_appears_after_unpaid_quick_filter(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $unpaidQuickFilterPosition = strpos($content, 'data-testid="expense-unpaid-quick-filter-card"');
        $largeAmountQuickFilterPosition = strpos($content, 'data-testid="expense-large-amount-quick-filter-card"');
        $pageHeaderPosition = strpos($content, 'class="page-header"');

        $this->assertNotFalse($unpaidQuickFilterPosition);
        $this->assertNotFalse($largeAmountQuickFilterPosition);
        $this->assertNotFalse($pageHeaderPosition);

        $this->assertLessThan($largeAmountQuickFilterPosition, $unpaidQuickFilterPosition);
        $this->assertLessThan($pageHeaderPosition, $largeAmountQuickFilterPosition);
    }
}
