<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueFilterSystemSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenue_filter_system_summary_is_rendered_in_expected_order_with_active_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index', [
            'collection_method' => 'cash',
            'collection_status' => 'collected',
            'archive_status' => 'archived',
            'page' => 9,
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $statusBarPosition = strpos($content, 'data-testid="revenue-page-status-bar"');
        $activeFilterAlertPosition = strpos($content, 'data-testid="revenue-active-filter-alert"');
        $activeFilterCountPosition = strpos($content, 'data-testid="revenue-active-filter-count-card"');
        $activeFilterLabelsPosition = strpos($content, 'data-testid="revenue-active-filter-labels-card"');
        $clearAllFiltersPosition = strpos($content, 'data-testid="revenue-clear-all-filters-card"');
        $uncollectedQuickFilterPosition = strpos($content, 'data-testid="revenue-uncollected-quick-filter-card"');
        $activeQuickFilterPosition = strpos($content, 'data-testid="revenue-active-quick-filter-card"');
        $archivedQuickFilterPosition = strpos($content, 'data-testid="revenue-archived-quick-filter-card"');
        $clearArchiveFilterPosition = strpos($content, 'data-testid="revenue-clear-archive-filter-card"');
        $filterFormPosition = strpos($content, 'فلترة الإيرادات');

        foreach ([
            $statusBarPosition,
            $activeFilterAlertPosition,
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

        $this->assertLessThan($activeFilterAlertPosition, $statusBarPosition);
        $this->assertLessThan($activeFilterCountPosition, $activeFilterAlertPosition);
        $this->assertLessThan($activeFilterLabelsPosition, $activeFilterCountPosition);
        $this->assertLessThan($clearAllFiltersPosition, $activeFilterLabelsPosition);
        $this->assertLessThan($uncollectedQuickFilterPosition, $clearAllFiltersPosition);
        $this->assertLessThan($activeQuickFilterPosition, $uncollectedQuickFilterPosition);
        $this->assertLessThan($archivedQuickFilterPosition, $activeQuickFilterPosition);
        $this->assertLessThan($clearArchiveFilterPosition, $archivedQuickFilterPosition);
        $this->assertLessThan($filterFormPosition, $clearArchiveFilterPosition);

        $this->assertStringContainsString('data-page-active-filter-count="3"', $content);
        $this->assertStringContainsString('data-page-has-active-filters="yes"', $content);
        $this->assertStringContainsString('data-active-filter-alert-count="3"', $content);
        $this->assertStringContainsString('data-active-filter-count="3"', $content);

        $this->assertStringContainsString('data-filter-key="collection_method"', $content);
        $this->assertStringContainsString('data-filter-key="collection_status"', $content);
        $this->assertStringContainsString('data-filter-key="archive_status"', $content);
        $this->assertStringNotContainsString('data-filter-key="page"', $content);

        $this->assertStringContainsString('نقدًا', $content);
        $this->assertStringContainsString('محصل', $content);
        $this->assertStringContainsString('مؤرشف', $content);

        $this->assertStringContainsString('data-testid="revenue-clear-all-filters"', $content);
        $this->assertSame(4, substr_count($content, 'data-quick-filter-card="revenue"'));
        $this->assertSame(4, substr_count($content, 'data-quick-filter-style="unified"'));
    }

    public function test_revenue_filter_system_summary_stays_idle_without_real_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index', [
            'collection_method' => '',
            'collection_status' => '',
            'archive_status' => '',
            'page' => 2,
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString('data-testid="revenue-page-status-bar"', $content);
        $this->assertStringContainsString('بدون فلاتر نشطة', $content);
        $this->assertStringContainsString('data-page-active-filter-count="0"', $content);
        $this->assertStringContainsString('data-page-has-active-filters="no"', $content);

        $this->assertStringContainsString('data-testid="revenue-active-filter-count-card"', $content);
        $this->assertStringContainsString('data-active-filter-count="0"', $content);
        $this->assertStringContainsString('data-testid="revenue-active-filter-count">0</strong>', $content);

        $this->assertStringContainsString('data-testid="revenue-active-filter-labels-card"', $content);
        $this->assertStringContainsString('data-testid="revenue-no-active-filter-labels"', $content);
        $this->assertStringContainsString('لا توجد فلاتر نشطة حاليًا', $content);

        $this->assertStringNotContainsString('data-testid="revenue-active-filter-alert"', $content);
        $this->assertStringNotContainsString('تنبيه: توجد فلاتر نشطة', $content);
        $this->assertStringNotContainsString('data-filter-key="collection_method"', $content);
        $this->assertStringNotContainsString('data-filter-key="collection_status"', $content);
        $this->assertStringNotContainsString('data-filter-key="archive_status"', $content);
        $this->assertStringNotContainsString('data-filter-key="page"', $content);
    }
}
