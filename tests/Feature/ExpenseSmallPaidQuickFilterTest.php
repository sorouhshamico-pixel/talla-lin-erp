<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseSmallPaidQuickFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_shows_small_paid_quick_filter_card(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $response->assertSee('data-testid="expense-small-paid-quick-filter-card"', false);
        $response->assertSee('data-quick-filter-card="expense"', false);
        $response->assertSee('data-quick-filter-style="unified"', false);
        $response->assertSee('مصروفات صغيرة مدفوعة');
        $response->assertSee('عرض المصروفات الصغيرة المدفوعة');
        $response->assertSee('data-testid="expense-small-paid-quick-filter"', false);
        $response->assertSee('large_amount=0', false);
        $response->assertSee('payment_status=paid', false);
    }

    public function test_small_paid_quick_filter_preserves_current_filters_and_overrides_values(): void
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
            '/<a\s+[^>]*href="([^"]+)"[^>]*data-testid="expense-small-paid-quick-filter"[^>]*>/s',
            $content,
            $matches
        );

        $this->assertNotEmpty($matches, 'Small paid expense quick filter link was not found.');

        $href = html_entity_decode($matches[1]);

        $this->assertStringContainsString('payment_method=cash', $href);
        $this->assertStringContainsString('date_to=2026-01-31', $href);
        $this->assertStringContainsString('page=3', $href);
        $this->assertStringContainsString('large_amount=0', $href);
        $this->assertStringContainsString('payment_status=paid', $href);
        $this->assertStringNotContainsString('large_amount=1', $href);
        $this->assertStringNotContainsString('payment_status=unpaid', $href);
    }

    public function test_small_paid_quick_filter_appears_after_small_unpaid_quick_filter_before_page_header(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $smallUnpaidQuickFilterPosition = strpos($content, 'data-testid="expense-small-unpaid-quick-filter-card"');
        $smallPaidQuickFilterPosition = strpos($content, 'data-testid="expense-small-paid-quick-filter-card"');
        $pageHeaderPosition = strpos($content, 'class="page-header"');

        $this->assertNotFalse($smallUnpaidQuickFilterPosition);
        $this->assertNotFalse($smallPaidQuickFilterPosition);
        $this->assertNotFalse($pageHeaderPosition);

        $this->assertLessThan($smallPaidQuickFilterPosition, $smallUnpaidQuickFilterPosition);
        $this->assertLessThan($pageHeaderPosition, $smallPaidQuickFilterPosition);
    }
}
