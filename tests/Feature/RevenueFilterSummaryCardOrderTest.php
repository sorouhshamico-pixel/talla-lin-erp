<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueFilterSummaryCardOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenue_filter_summary_cards_are_shown_before_quick_filter_actions(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index', [
            'collection_method' => 'cash',
            'collection_status' => 'collected',
            'archive_status' => 'archived',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $activeFilterCountPosition = strpos($content, 'data-testid="revenue-active-filter-count-card"');
        $activeFilterLabelsPosition = strpos($content, 'data-testid="revenue-active-filter-labels-card"');
        $clearAllFiltersPosition = strpos($content, 'data-testid="revenue-clear-all-filters-card"');
        $uncollectedQuickFilterPosition = strpos($content, 'data-testid="revenue-uncollected-quick-filter-card"');
        $activeQuickFilterPosition = strpos($content, 'data-testid="revenue-active-quick-filter-card"');
        $archivedQuickFilterPosition = strpos($content, 'data-testid="revenue-archived-quick-filter-card"');
        $clearArchiveFilterPosition = strpos($content, 'data-testid="revenue-clear-archive-filter-card"');
        $filterFormPosition = strpos($content, 'فلترة الإيرادات');

        foreach ([
            $activeFilterCountPosition,
            $activeFilterLabelsPosition,
            $clearAllFiltersPosition,
            $uncollectedQuickFilterPosition,
            $activeQuickFilterPosition,
            $archivedQuickFilterPosition,
            $clearArchiveFilterPosition,
            $filterFormPosition,
        ] as $position) {
            $this->assertNotFalse($position);
        }

        $this->assertLessThan($activeFilterLabelsPosition, $activeFilterCountPosition);
        $this->assertLessThan($clearAllFiltersPosition, $activeFilterLabelsPosition);
        $this->assertLessThan($uncollectedQuickFilterPosition, $clearAllFiltersPosition);
        $this->assertLessThan($activeQuickFilterPosition, $uncollectedQuickFilterPosition);
        $this->assertLessThan($archivedQuickFilterPosition, $activeQuickFilterPosition);
        $this->assertLessThan($clearArchiveFilterPosition, $archivedQuickFilterPosition);
        $this->assertLessThan($filterFormPosition, $clearArchiveFilterPosition);
    }

    public function test_revenue_filter_summary_card_order_keeps_existing_filter_links(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index', [
            'collection_method' => 'cash',
            'collection_status' => 'collected',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString('collection_method=cash', $content);
        $this->assertStringContainsString('collection_status=collected', $content);
        $this->assertStringContainsString('archive_status=active', $content);
        $this->assertStringContainsString('archive_status=archived', $content);
        $this->assertStringContainsString('data-testid="revenue-clear-all-filters"', $content);
    }
}
