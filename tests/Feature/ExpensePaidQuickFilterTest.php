<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpensePaidQuickFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_shows_paid_quick_filter_card(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $response->assertSee('data-testid="expense-paid-quick-filter-card"', false);
        $response->assertSee('data-quick-filter-card="expense"', false);
        $response->assertSee('data-quick-filter-style="unified"', false);
        $response->assertSee('مصروفات مدفوعة');
        $response->assertSee('عرض المصروفات المدفوعة');
        $response->assertSee('data-testid="expense-paid-quick-filter"', false);
        $response->assertSee('payment_status=paid', false);
    }

    public function test_paid_quick_filter_preserves_current_expense_filters_and_overrides_payment_status(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'date_to' => '2026-01-31',
            'page' => 3,
        ]));

        $response->assertOk();

        $content = $response->getContent();

        preg_match(
            '/<a\s+[^>]*href="([^"]+)"[^>]*data-testid="expense-paid-quick-filter"[^>]*>/s',
            $content,
            $matches
        );

        $this->assertNotEmpty($matches, 'Paid expense quick filter link was not found.');

        $href = html_entity_decode($matches[1]);

        $this->assertStringContainsString('payment_method=cash', $href);
        $this->assertStringContainsString('date_to=2026-01-31', $href);
        $this->assertStringContainsString('page=3', $href);
        $this->assertStringContainsString('payment_status=paid', $href);
        $this->assertStringNotContainsString('payment_status=unpaid', $href);
    }

    public function test_paid_quick_filter_appears_after_missing_attachment_quick_filter(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $missingAttachmentQuickFilterPosition = strpos($content, 'data-testid="expense-missing-attachment-quick-filter-card"');
        $paidQuickFilterPosition = strpos($content, 'data-testid="expense-paid-quick-filter-card"');

        $this->assertNotFalse($missingAttachmentQuickFilterPosition);
        $this->assertNotFalse($paidQuickFilterPosition);

        $this->assertLessThan($paidQuickFilterPosition, $missingAttachmentQuickFilterPosition);
    }
}
