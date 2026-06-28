<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseQuickFilterOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_quick_filter_cards_are_rendered_in_expected_order(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'page' => 3,
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $clearAllFiltersPosition = strpos($content, 'data-testid="expense-clear-all-filters-card"');
        $missingAttachmentQuickFilterPosition = strpos($content, 'data-testid="expense-missing-attachment-quick-filter-card"');
        $paidQuickFilterPosition = strpos($content, 'data-testid="expense-paid-quick-filter-card"');
        $unpaidQuickFilterPosition = strpos($content, 'data-testid="expense-unpaid-quick-filter-card"');
        $largeAmountQuickFilterPosition = strpos($content, 'data-testid="expense-large-amount-quick-filter-card"');
        $largeUnpaidQuickFilterPosition = strpos($content, 'data-testid="expense-large-unpaid-quick-filter-card"');
        $smallAmountQuickFilterPosition = strpos($content, 'data-testid="expense-small-amount-quick-filter-card"');
        $smallUnpaidQuickFilterPosition = strpos($content, 'data-testid="expense-small-unpaid-quick-filter-card"');
        $smallPaidQuickFilterPosition = strpos($content, 'data-testid="expense-small-paid-quick-filter-card"');
        $pageHeaderPosition = strpos($content, 'class="page-header"');

        foreach ([
            $clearAllFiltersPosition,
            $missingAttachmentQuickFilterPosition,
            $paidQuickFilterPosition,
            $unpaidQuickFilterPosition,
            $largeAmountQuickFilterPosition,
            $largeUnpaidQuickFilterPosition,
            $smallAmountQuickFilterPosition,
            $smallUnpaidQuickFilterPosition,
            $smallPaidQuickFilterPosition,
            $pageHeaderPosition,
        ] as $position) {
            $this->assertNotFalse($position);
        }

        $this->assertLessThan($missingAttachmentQuickFilterPosition, $clearAllFiltersPosition);
        $this->assertLessThan($paidQuickFilterPosition, $missingAttachmentQuickFilterPosition);
        $this->assertLessThan($unpaidQuickFilterPosition, $paidQuickFilterPosition);
        $this->assertLessThan($largeAmountQuickFilterPosition, $unpaidQuickFilterPosition);
        $this->assertLessThan($largeUnpaidQuickFilterPosition, $largeAmountQuickFilterPosition);
        $this->assertLessThan($smallAmountQuickFilterPosition, $largeUnpaidQuickFilterPosition);
        $this->assertLessThan($smallUnpaidQuickFilterPosition, $smallAmountQuickFilterPosition);
        $this->assertLessThan($smallPaidQuickFilterPosition, $smallUnpaidQuickFilterPosition);
        $this->assertLessThan($pageHeaderPosition, $smallPaidQuickFilterPosition);
    }

    public function test_expense_quick_filter_cards_keep_unified_style_and_expected_links(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'date_to' => '2026-01-31',
            'page' => 4,
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $this->assertSame(8, substr_count($content, 'data-quick-filter-card="expense"'));
        $this->assertSame(8, substr_count($content, 'data-quick-filter-style="unified"'));

        $this->assertStringContainsString('data-testid="expense-missing-attachment-quick-filter"', $content);
        $this->assertStringContainsString('data-testid="expense-paid-quick-filter"', $content);
        $this->assertStringContainsString('data-testid="expense-unpaid-quick-filter"', $content);
        $this->assertStringContainsString('data-testid="expense-large-amount-quick-filter"', $content);
        $this->assertStringContainsString('data-testid="expense-large-unpaid-quick-filter"', $content);
        $this->assertStringContainsString('data-testid="expense-small-amount-quick-filter"', $content);
        $this->assertStringContainsString('data-testid="expense-small-unpaid-quick-filter"', $content);
        $this->assertStringContainsString('data-testid="expense-small-paid-quick-filter"', $content);

        $this->assertStringContainsString('has_attachment=0', $content);
        $this->assertStringContainsString('payment_status=paid', $content);
        $this->assertStringContainsString('payment_status=unpaid', $content);
        $this->assertStringContainsString('large_amount=1', $content);
        $this->assertStringContainsString('large_amount=0', $content);

        $this->assertStringContainsString('payment_method=cash', $content);
        $this->assertStringContainsString('date_to=2026-01-31', $content);
        $this->assertStringContainsString('page=4', $content);
    }
}
