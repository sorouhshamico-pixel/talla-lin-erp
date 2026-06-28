<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseQuickFilterUniquenessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_expense_quick_filter_card_test_ids_are_unique_and_ordered(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $content = $response->getContent();

        preg_match_all('/data-testid="(expense-[a-z0-9-]+-quick-filter-card)"/', $content, $matches);

        $cardTestIds = $matches[1];

        $expectedCardTestIds = [
            'expense-missing-attachment-quick-filter-card',
            'expense-with-attachment-quick-filter-card',
            'expense-missing-unpaid-quick-filter-card',
            'expense-paid-quick-filter-card',
            'expense-unpaid-quick-filter-card',
            'expense-large-amount-quick-filter-card',
            'expense-large-unpaid-quick-filter-card',
            'expense-small-amount-quick-filter-card',
            'expense-small-unpaid-quick-filter-card',
            'expense-small-paid-quick-filter-card',
            'expense-large-paid-quick-filter-card',
        ];

        $this->assertSame($expectedCardTestIds, $cardTestIds);
        $this->assertSame($cardTestIds, array_values(array_unique($cardTestIds)));
    }

    public function test_expense_quick_filter_link_test_ids_are_unique_and_ordered(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $content = $response->getContent();

        preg_match_all('/data-testid="(expense-[a-z0-9-]+-quick-filter)"/', $content, $matches);

        $linkTestIds = $matches[1];

        $expectedLinkTestIds = [
            'expense-missing-attachment-quick-filter',
            'expense-with-attachment-quick-filter',
            'expense-missing-unpaid-quick-filter',
            'expense-paid-quick-filter',
            'expense-unpaid-quick-filter',
            'expense-large-amount-quick-filter',
            'expense-large-unpaid-quick-filter',
            'expense-small-amount-quick-filter',
            'expense-small-unpaid-quick-filter',
            'expense-small-paid-quick-filter',
            'expense-large-paid-quick-filter',
        ];

        $this->assertSame($expectedLinkTestIds, $linkTestIds);
        $this->assertSame($linkTestIds, array_values(array_unique($linkTestIds)));
    }

    public function test_expense_quick_filter_cards_render_before_page_header(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $content = $response->getContent();

        $pageHeaderPosition = strpos($content, 'class="page-header"');

        $this->assertNotFalse($pageHeaderPosition);

        $cardTestIds = [
            'expense-missing-attachment-quick-filter-card',
            'expense-with-attachment-quick-filter-card',
            'expense-missing-unpaid-quick-filter-card',
            'expense-paid-quick-filter-card',
            'expense-unpaid-quick-filter-card',
            'expense-large-amount-quick-filter-card',
            'expense-large-unpaid-quick-filter-card',
            'expense-small-amount-quick-filter-card',
            'expense-small-unpaid-quick-filter-card',
            'expense-small-paid-quick-filter-card',
            'expense-large-paid-quick-filter-card',
        ];

        $previousPosition = -1;

        foreach ($cardTestIds as $cardTestId) {
            $position = strpos($content, 'data-testid="'.$cardTestId.'"');

            $this->assertNotFalse($position, "{$cardTestId} was not found.");
            $this->assertGreaterThan($previousPosition, $position, "{$cardTestId} is not in the expected order.");
            $this->assertLessThan($pageHeaderPosition, $position, "{$cardTestId} should render before page header.");

            $previousPosition = $position;
        }
    }

    private function actingAsOwner(): void
    {
        $user = User::query()->firstOrFail();

        $this->actingAs($user);
    }
}
