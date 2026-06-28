<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseQuickFilterDuplicateQueryParameterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_expense_quick_filter_links_do_not_duplicate_query_parameters(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'has_attachment' => '0',
            'large_amount' => '0',
            'date_to' => '2026-01-31',
            'page' => '3',
        ]));

        $response->assertOk();

        $links = $this->extractExpenseQuickFilterLinks($response->getContent());

        $this->assertCount(14, $links);

        $queryKeys = [
            'payment_method',
            'payment_status',
            'has_attachment',
            'large_amount',
            'date_to',
            'page',
        ];

        foreach ($links as $testId => $href) {
            foreach ($queryKeys as $queryKey) {
                $this->assertQueryParameterIsNotDuplicated($href, $queryKey, $testId);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function extractExpenseQuickFilterLinks(string $content): array
    {
        preg_match_all(
            '/<a\b(?=[^>]*data-testid="(expense-[a-z0-9-]+-quick-filter)")(?=[^>]*href="([^"]+)")[^>]*>/',
            $content,
            $matches,
            PREG_SET_ORDER
        );

        $links = [];

        foreach ($matches as $match) {
            $links[$match[1]] = html_entity_decode($match[2]);
        }

        return $links;
    }

    private function assertQueryParameterIsNotDuplicated(string $href, string $queryKey, string $testId): void
    {
        $query = parse_url($href, PHP_URL_QUERY) ?? '';

        preg_match_all(
            '/(?:^|&)' . preg_quote($queryKey, '/') . '=/',
            $query,
            $matches
        );

        $this->assertLessThanOrEqual(
            1,
            count($matches[0]),
            "{$testId} should not duplicate {$queryKey} in query string: {$href}"
        );
    }

    private function actingAsOwner(): void
    {
        $user = User::query()->firstOrFail();

        $this->actingAs($user);
    }
}
