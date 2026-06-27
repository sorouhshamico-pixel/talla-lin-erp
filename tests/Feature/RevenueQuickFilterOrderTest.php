<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueQuickFilterOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenue_quick_filter_cards_are_shown_in_expected_order(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index'));

        $response->assertOk();

        $content = $response->getContent();

        $uncollectedPosition = strpos($content, 'data-testid="revenue-uncollected-quick-filter-card"');
        $activePosition = strpos($content, 'data-testid="revenue-active-quick-filter-card"');
        $archivedPosition = strpos($content, 'data-testid="revenue-archived-quick-filter-card"');
        $clearArchivePosition = strpos($content, 'data-testid="revenue-clear-archive-filter-card"');
        $filterFormPosition = strpos($content, 'فلترة الإيرادات');

        $this->assertNotFalse($uncollectedPosition);
        $this->assertNotFalse($activePosition);
        $this->assertNotFalse($archivedPosition);
        $this->assertNotFalse($clearArchivePosition);
        $this->assertNotFalse($filterFormPosition);

        $this->assertLessThan($activePosition, $uncollectedPosition);
        $this->assertLessThan($archivedPosition, $activePosition);
        $this->assertLessThan($clearArchivePosition, $archivedPosition);
        $this->assertLessThan($filterFormPosition, $clearArchivePosition);
    }
}
