<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueQuickFilterCardStyleTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenue_quick_filter_cards_use_unified_style_markers(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index'));

        $response->assertOk();

        $content = $response->getContent();

        $this->assertSame(4, substr_count($content, 'data-quick-filter-card="revenue"'));
        $this->assertSame(4, substr_count($content, 'data-quick-filter-style="unified"'));

        foreach ([
            'revenue-uncollected-quick-filter-card',
            'revenue-active-quick-filter-card',
            'revenue-archived-quick-filter-card',
            'revenue-clear-archive-filter-card',
        ] as $testId) {
            $this->assertStringContainsString('data-testid="' . $testId . '"', $content);
        }
    }

    public function test_revenue_quick_filter_cards_keep_expected_order_and_links(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index', [
            'collection_method' => 'cash',
            'collection_status' => 'collected',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $uncollectedPosition = strpos($content, 'data-testid="revenue-uncollected-quick-filter-card"');
        $activePosition = strpos($content, 'data-testid="revenue-active-quick-filter-card"');
        $archivedPosition = strpos($content, 'data-testid="revenue-archived-quick-filter-card"');
        $clearArchivePosition = strpos($content, 'data-testid="revenue-clear-archive-filter-card"');

        $this->assertNotFalse($uncollectedPosition);
        $this->assertNotFalse($activePosition);
        $this->assertNotFalse($archivedPosition);
        $this->assertNotFalse($clearArchivePosition);

        $this->assertLessThan($activePosition, $uncollectedPosition);
        $this->assertLessThan($archivedPosition, $activePosition);
        $this->assertLessThan($clearArchivePosition, $archivedPosition);

        $this->assertStringContainsString('collection_method=cash', $content);
        $this->assertStringContainsString('collection_status=collected', $content);
        $this->assertStringContainsString('archive_status=active', $content);
        $this->assertStringContainsString('archive_status=archived', $content);
    }
}
