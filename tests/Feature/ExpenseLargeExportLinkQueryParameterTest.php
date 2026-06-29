<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseLargeExportLinkQueryParameterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_large_unpaid_summary_export_link_has_clean_query_parameters(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'large_amount' => '0',
            'from_date' => '2026-01-01',
            'to_date' => '2026-01-31',
            'page' => 7,
        ]));

        $response->assertOk();

        $href = $this->extractHref(
            $response->getContent(),
            'expense-large-unpaid-summary-export'
        );

        $this->assertStringContainsString(route('expenses.export-large-unpaid'), $href);

        $this->assertQueryParameterExistsOnce($href, 'payment_method', 'cash');
        $this->assertQueryParameterExistsOnce($href, 'from_date', '2026-01-01');
        $this->assertQueryParameterExistsOnce($href, 'to_date', '2026-01-31');
        $this->assertQueryParameterExistsOnce($href, 'large_amount', '1');
        $this->assertQueryParameterExistsOnce($href, 'payment_status', 'unpaid');

        $this->assertQueryParameterMissing($href, 'page');
    }

    public function test_large_paid_summary_export_link_has_clean_query_parameters(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'large_amount' => '0',
            'from_date' => '2026-01-01',
            'to_date' => '2026-01-31',
            'page' => 7,
        ]));

        $response->assertOk();

        $href = $this->extractHref(
            $response->getContent(),
            'expense-large-paid-summary-export'
        );

        $this->assertStringContainsString(route('expenses.export-large-paid'), $href);

        $this->assertQueryParameterExistsOnce($href, 'payment_method', 'cash');
        $this->assertQueryParameterExistsOnce($href, 'from_date', '2026-01-01');
        $this->assertQueryParameterExistsOnce($href, 'to_date', '2026-01-31');
        $this->assertQueryParameterExistsOnce($href, 'large_amount', '1');
        $this->assertQueryParameterExistsOnce($href, 'payment_status', 'paid');

        $this->assertQueryParameterMissing($href, 'page');
    }

    private function extractHref(string $content, string $testId): string
    {
        preg_match(
            '/<a(?=[^>]*data-testid="' . preg_quote($testId, '/') . '")[^>]*href="([^"]+)"/',
            $content,
            $matches
        );

        $this->assertNotEmpty($matches, "Link with data-testid [{$testId}] was not found.");

        return html_entity_decode($matches[1]);
    }

    private function assertQueryParameterExistsOnce(string $href, string $key, string $expectedValue): void
    {
        $query = parse_url($href, PHP_URL_QUERY);

        $this->assertIsString($query, "URL does not contain a query string: {$href}");

        $pairs = array_filter(explode('&', $query));
        $values = [];

        foreach ($pairs as $pair) {
            [$pairKey, $pairValue] = array_pad(explode('=', $pair, 2), 2, '');

            if (urldecode($pairKey) === $key) {
                $values[] = urldecode($pairValue);
            }
        }

        $this->assertCount(1, $values, "Query parameter [{$key}] should exist exactly once in URL: {$href}");
        $this->assertSame($expectedValue, $values[0], "Query parameter [{$key}] has unexpected value.");
    }

    private function assertQueryParameterMissing(string $href, string $key): void
    {
        $query = parse_url($href, PHP_URL_QUERY);

        if (! is_string($query) || $query === '') {
            $this->assertTrue(true);

            return;
        }

        $pairs = array_filter(explode('&', $query));

        foreach ($pairs as $pair) {
            [$pairKey] = array_pad(explode('=', $pair, 2), 2, '');

            $this->assertNotSame(
                $key,
                urldecode($pairKey),
                "Query parameter [{$key}] should not exist in URL: {$href}"
            );
        }
    }

    private function actingAsOwner(): void
    {
        $user = User::query()->firstOrFail();

        $this->actingAs($user);
    }
}
