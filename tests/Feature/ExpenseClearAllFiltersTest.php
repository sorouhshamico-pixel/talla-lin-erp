<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseClearAllFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_shows_clear_all_filters_shortcut(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $response->assertSee('data-testid="expense-clear-all-filters-card"', false);
        $response->assertSee('مسح كل فلاتر المصروفات');
        $response->assertSee('مسح كل الفلاتر');
        $response->assertSee('data-testid="expense-clear-all-filters"', false);
    }

    public function test_clear_all_expense_filters_link_removes_all_query_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'status' => 'paid',
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
            'has_attachment' => '0',
            'page' => 2,
        ]));

        $response->assertOk();

        $content = $response->getContent();

        preg_match(
            '/<a\s+[^>]*href="([^"]+)"[^>]*data-testid="expense-clear-all-filters"[^>]*>/s',
            $content,
            $matches
        );

        $this->assertNotEmpty($matches, 'Clear all expense filters link was not found.');

        $href = html_entity_decode($matches[1]);

        $this->assertStringEndsWith('/expenses', $href);
        $this->assertStringNotContainsString('payment_method=', $href);
        $this->assertStringNotContainsString('status=', $href);
        $this->assertStringNotContainsString('date_from=', $href);
        $this->assertStringNotContainsString('date_to=', $href);
        $this->assertStringNotContainsString('has_attachment=', $href);
        $this->assertStringNotContainsString('page=', $href);
        $this->assertStringNotContainsString('?', $href);
    }
}
