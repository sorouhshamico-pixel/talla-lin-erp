<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseQuickFilterQueryMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_expense_quick_filter_links_preserve_common_filters_and_apply_expected_overrides(): void
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

        $expectedOverrides = [
            'expense-missing-attachment-quick-filter' => [
                'has_attachment' => '0',
            ],
            'expense-with-attachment-quick-filter' => [
                'has_attachment' => '1',
            ],
            'expense-missing-unpaid-quick-filter' => [
                'has_attachment' => '0',
                'payment_status' => 'unpaid',
            ],
            'expense-missing-paid-quick-filter' => [
                'has_attachment' => '0',
                'payment_status' => 'paid',
            ],
            'expense-with-attachment-unpaid-quick-filter' => [
                'has_attachment' => '1',
                'payment_status' => 'unpaid',
            ],
            'expense-with-attachment-paid-quick-filter' => [
                'has_attachment' => '1',
                'payment_status' => 'paid',
            ],
            'expense-paid-quick-filter' => [
                'payment_status' => 'paid',
            ],
            'expense-unpaid-quick-filter' => [
                'payment_status' => 'unpaid',
            ],
            'expense-large-amount-quick-filter' => [
                'large_amount' => '1',
            ],
            'expense-large-unpaid-quick-filter' => [
                'large_amount' => '1',
                'payment_status' => 'unpaid',
            ],
            'expense-small-amount-quick-filter' => [
                'large_amount' => '0',
            ],
            'expense-small-unpaid-quick-filter' => [
                'large_amount' => '0',
                'payment_status' => 'unpaid',
            ],
            'expense-small-paid-quick-filter' => [
                'large_amount' => '0',
                'payment_status' => 'paid',
            ],
            'expense-large-paid-quick-filter' => [
                'large_amount' => '1',
                'payment_status' => 'paid',
            ],
        ];

        $this->assertSame(array_keys($expectedOverrides), array_keys($links));
        $this->assertCount(14, $links);

        foreach ($expectedOverrides as $testId => $overrides) {
            $params = $this->parseHrefQuery($links[$testId]);

            $this->assertSame('cash', $params['payment_method'] ?? null, "{$testId} should preserve payment_method.");
            $this->assertSame('2026-01-31', $params['date_to'] ?? null, "{$testId} should preserve date_to.");
            $this->assertSame('3', $params['page'] ?? null, "{$testId} should preserve page.");

            foreach ($overrides as $key => $expectedValue) {
                $this->assertSame($expectedValue, $params[$key] ?? null, "{$testId} should apply {$key}={$expectedValue}.");
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

    /**
     * @return array<string, string>
     */
    private function parseHrefQuery(string $href): array
    {
        $query = parse_url($href, PHP_URL_QUERY);

        $params = [];

        parse_str($query ?? '', $params);

        return $params;
    }

    private function actingAsOwner(): void
    {
        $user = User::query()->firstOrFail();

        $this->actingAs($user);
    }
}
