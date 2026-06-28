<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseWithAttachmentQuickFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_expenses_index_shows_with_attachment_quick_filter_card(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $response->assertSee('data-testid="expense-with-attachment-quick-filter-card"', false);
        $response->assertSee('data-quick-filter-card="expense"', false);
        $response->assertSee('data-quick-filter-style="unified"', false);
        $response->assertSee('مصروفات بها مرفق');
        $response->assertSee('عرض المصروفات التي بها مرفق');
        $response->assertSee('has_attachment=1', false);
    }

    public function test_with_attachment_quick_filter_preserves_current_filters_and_overrides_values(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'has_attachment' => '0',
            'date_to' => '2026-01-31',
            'page' => '3',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        preg_match(
            '/<a\b(?=[^>]*data-testid="expense-with-attachment-quick-filter")(?=[^>]*href="([^"]+)")[^>]*>/',
            $content,
            $matches
        );

        $this->assertNotEmpty($matches, 'With attachment expense quick filter link was not found.');

        $href = html_entity_decode($matches[1]);

        $this->assertStringContainsString('payment_method=cash', $href);
        $this->assertStringContainsString('payment_status=paid', $href);
        $this->assertStringContainsString('date_to=2026-01-31', $href);
        $this->assertStringContainsString('page=3', $href);
        $this->assertStringContainsString('has_attachment=1', $href);
        $this->assertStringNotContainsString('has_attachment=0', $href);
    }

    public function test_with_attachment_quick_filter_appears_after_missing_attachment_quick_filter_before_paid_filter(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $content = $response->getContent();

        $missingAttachmentQuickFilterPosition = strpos($content, 'data-testid="expense-missing-attachment-quick-filter-card"');
        $withAttachmentQuickFilterPosition = strpos($content, 'data-testid="expense-with-attachment-quick-filter-card"');
        $paidQuickFilterPosition = strpos($content, 'data-testid="expense-paid-quick-filter-card"');

        $this->assertNotFalse($missingAttachmentQuickFilterPosition);
        $this->assertNotFalse($withAttachmentQuickFilterPosition);
        $this->assertNotFalse($paidQuickFilterPosition);

        $this->assertLessThan($withAttachmentQuickFilterPosition, $missingAttachmentQuickFilterPosition);
        $this->assertLessThan($paidQuickFilterPosition, $withAttachmentQuickFilterPosition);
    }

    private function actingAsOwner(): void
    {
        $user = User::query()->firstOrFail();

        $this->actingAs($user);
    }
}
