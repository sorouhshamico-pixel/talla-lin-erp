<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseMissingPaidQuickFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_expenses_index_shows_missing_paid_quick_filter_card(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $response->assertSee('data-testid="expense-missing-paid-quick-filter-card"', false);
        $response->assertSee('data-quick-filter-card="expense"', false);
        $response->assertSee('data-quick-filter-style="unified"', false);
        $response->assertSee('مصروفات بدون مرفق ومدفوعة');
        $response->assertSee('عرض المصروفات بدون مرفق والمدفوعة');
        $response->assertSee('has_attachment=0', false);
        $response->assertSee('payment_status=paid', false);
    }

    public function test_missing_paid_quick_filter_preserves_current_filters_and_overrides_values(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'has_attachment' => '1',
            'date_to' => '2026-01-31',
            'page' => '3',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        preg_match(
            '/<a\b(?=[^>]*data-testid="expense-missing-paid-quick-filter")(?=[^>]*href="([^"]+)")[^>]*>/',
            $content,
            $matches
        );

        $this->assertNotEmpty($matches, 'Missing paid expense quick filter link was not found.');

        $href = html_entity_decode($matches[1]);

        $this->assertStringContainsString('payment_method=cash', $href);
        $this->assertStringContainsString('date_to=2026-01-31', $href);
        $this->assertStringContainsString('page=3', $href);
        $this->assertStringContainsString('has_attachment=0', $href);
        $this->assertStringContainsString('payment_status=paid', $href);
        $this->assertStringNotContainsString('has_attachment=1', $href);
        $this->assertStringNotContainsString('payment_status=unpaid', $href);
    }

    public function test_missing_paid_quick_filter_appears_after_missing_unpaid_quick_filter_before_paid_filter(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $content = $response->getContent();

        $missingUnpaidQuickFilterPosition = strpos($content, 'data-testid="expense-missing-unpaid-quick-filter-card"');
        $missingPaidQuickFilterPosition = strpos($content, 'data-testid="expense-missing-paid-quick-filter-card"');
        $paidQuickFilterPosition = strpos($content, 'data-testid="expense-paid-quick-filter-card"');

        $this->assertNotFalse($missingUnpaidQuickFilterPosition);
        $this->assertNotFalse($missingPaidQuickFilterPosition);
        $this->assertNotFalse($paidQuickFilterPosition);

        $this->assertLessThan($missingPaidQuickFilterPosition, $missingUnpaidQuickFilterPosition);
        $this->assertLessThan($paidQuickFilterPosition, $missingPaidQuickFilterPosition);
    }

    private function actingAsOwner(): void
    {
        $user = User::query()->firstOrFail();

        $this->actingAs($user);
    }
}
