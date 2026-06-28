<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseLargeUnpaidUnifiedQuickFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_shows_large_unpaid_unified_quick_filter_card(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $response->assertSee('data-testid="expense-large-unpaid-quick-filter-card"', false);
        $response->assertSee('data-quick-filter-card="expense"', false);
        $response->assertSee('data-quick-filter-style="unified"', false);
        $response->assertSee('المصاريف الكبيرة غير المدفوعة');
        $response->assertSee('عرض المصروفات الكبيرة غير المدفوعة');
        $response->assertSee('data-testid="expense-large-unpaid-quick-filter"', false);
        $response->assertSee('large_amount=1', false);
        $response->assertSee('payment_status=unpaid', false);
    }

    public function test_large_unpaid_unified_quick_filter_preserves_current_filters_and_overrides_values(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'large_amount' => '0',
            'date_to' => '2026-01-31',
            'page' => 3,
        ]));

        $response->assertOk();

        $content = $response->getContent();

        preg_match(
            '/<a\s+[^>]*href="([^"]+)"[^>]*data-testid="expense-large-unpaid-quick-filter"[^>]*>/s',
            $content,
            $matches
        );

        $this->assertNotEmpty($matches, 'Large unpaid expense quick filter link was not found.');

        $href = html_entity_decode($matches[1]);

        $this->assertStringContainsString('payment_method=cash', $href);
        $this->assertStringContainsString('date_to=2026-01-31', $href);
        $this->assertStringContainsString('page=3', $href);
        $this->assertStringContainsString('large_amount=1', $href);
        $this->assertStringContainsString('payment_status=unpaid', $href);
        $this->assertStringNotContainsString('large_amount=0', $href);
        $this->assertStringNotContainsString('payment_status=paid', $href);
    }

    public function test_large_unpaid_unified_quick_filter_appears_after_large_amount_quick_filter_before_page_header(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $largeAmountQuickFilterPosition = strpos($content, 'data-testid="expense-large-amount-quick-filter-card"');
        $largeUnpaidQuickFilterPosition = strpos($content, 'data-testid="expense-large-unpaid-quick-filter-card"');
        $pageHeaderPosition = strpos($content, 'class="page-header"');

        $this->assertNotFalse($largeAmountQuickFilterPosition);
        $this->assertNotFalse($largeUnpaidQuickFilterPosition);
        $this->assertNotFalse($pageHeaderPosition);

        $this->assertLessThan($largeUnpaidQuickFilterPosition, $largeAmountQuickFilterPosition);
        $this->assertLessThan($pageHeaderPosition, $largeUnpaidQuickFilterPosition);
    }
}
