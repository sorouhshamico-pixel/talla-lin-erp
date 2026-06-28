<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseSmallAmountQuickFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_shows_small_amount_quick_filter_card(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $response->assertSee('data-testid="expense-small-amount-quick-filter-card"', false);
        $response->assertSee('data-quick-filter-card="expense"', false);
        $response->assertSee('data-quick-filter-style="unified"', false);
        $response->assertSee('مصروفات صغيرة');
        $response->assertSee('عرض المصروفات الصغيرة');
        $response->assertSee('data-testid="expense-small-amount-quick-filter"', false);
        $response->assertSee('large_amount=0', false);
    }

    public function test_small_amount_quick_filter_preserves_current_filters_and_overrides_large_amount(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'large_amount' => '1',
            'date_to' => '2026-01-31',
            'page' => 3,
        ]));

        $response->assertOk();

        $content = $response->getContent();

        preg_match(
            '/<a\s+[^>]*href="([^"]+)"[^>]*data-testid="expense-small-amount-quick-filter"[^>]*>/s',
            $content,
            $matches
        );

        $this->assertNotEmpty($matches, 'Small amount expense quick filter link was not found.');

        $href = html_entity_decode($matches[1]);

        $this->assertStringContainsString('payment_method=cash', $href);
        $this->assertStringContainsString('payment_status=unpaid', $href);
        $this->assertStringContainsString('date_to=2026-01-31', $href);
        $this->assertStringContainsString('page=3', $href);
        $this->assertStringContainsString('large_amount=0', $href);
        $this->assertStringNotContainsString('large_amount=1', $href);
    }

    public function test_small_amount_quick_filter_appears_after_large_unpaid_quick_filter_before_page_header(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $largeUnpaidQuickFilterPosition = strpos($content, 'data-testid="expense-large-unpaid-quick-filter-card"');
        $smallAmountQuickFilterPosition = strpos($content, 'data-testid="expense-small-amount-quick-filter-card"');
        $pageHeaderPosition = strpos($content, 'class="page-header"');

        $this->assertNotFalse($largeUnpaidQuickFilterPosition);
        $this->assertNotFalse($smallAmountQuickFilterPosition);
        $this->assertNotFalse($pageHeaderPosition);

        $this->assertLessThan($smallAmountQuickFilterPosition, $largeUnpaidQuickFilterPosition);
        $this->assertLessThan($pageHeaderPosition, $smallAmountQuickFilterPosition);
    }
}
