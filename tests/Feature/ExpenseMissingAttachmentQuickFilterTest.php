<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseMissingAttachmentQuickFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_shows_missing_attachment_quick_filter_card(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $response->assertSee('data-testid="expense-missing-attachment-quick-filter-card"', false);
        $response->assertSee('data-quick-filter-card="expense"', false);
        $response->assertSee('data-quick-filter-style="unified"', false);
        $response->assertSee('مصروفات بدون مرفق');
        $response->assertSee('عرض المصروفات بدون مرفق');
        $response->assertSee('data-testid="expense-missing-attachment-quick-filter"', false);
        $response->assertSee('has_attachment=0', false);
    }

    public function test_missing_attachment_quick_filter_preserves_current_expense_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'status' => 'paid',
            'date_to' => '2026-01-31',
            'page' => 3,
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString('data-testid="expense-missing-attachment-quick-filter-card"', $content);
        $this->assertStringContainsString('data-testid="expense-missing-attachment-quick-filter"', $content);

        $this->assertStringContainsString('payment_method=cash', $content);
        $this->assertStringContainsString('status=paid', $content);
        $this->assertStringContainsString('date_to=2026-01-31', $content);
        $this->assertStringContainsString('page=3', $content);
        $this->assertStringContainsString('has_attachment=0', $content);
    }

    public function test_missing_attachment_quick_filter_appears_after_clear_all_filters_card(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $clearAllFiltersPosition = strpos($content, 'data-testid="expense-clear-all-filters-card"');
        $missingAttachmentQuickFilterPosition = strpos($content, 'data-testid="expense-missing-attachment-quick-filter-card"');

        $this->assertNotFalse($clearAllFiltersPosition);
        $this->assertNotFalse($missingAttachmentQuickFilterPosition);

        $this->assertLessThan($missingAttachmentQuickFilterPosition, $clearAllFiltersPosition);
    }
}
