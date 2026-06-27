<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueActiveFilterAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenues_index_does_not_show_active_filter_alert_without_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index'));

        $response->assertOk();

        $response->assertDontSee('data-testid="revenue-active-filter-alert"', false);
        $response->assertDontSee('تنبيه: توجد فلاتر نشطة');
        $response->assertDontSee('data-active-filter-alert-count="0"', false);
    }

    public function test_revenues_index_shows_active_filter_alert_when_filters_are_applied(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index', [
            'collection_method' => 'cash',
            'collection_status' => 'collected',
            'archive_status' => 'archived',
        ]));

        $response->assertOk();

        $response->assertSee('data-testid="revenue-active-filter-alert"', false);
        $response->assertSee('تنبيه: توجد فلاتر نشطة');
        $response->assertSee('النتائج المعروضة الآن لا تمثل كل الإيرادات');
        $response->assertSee('data-active-filter-alert-count="3"', false);
        $response->assertSee('data-testid="revenue-active-filter-alert-count"', false);
    }

    public function test_active_filter_alert_ignores_page_and_appears_before_filter_summary_cards(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index', [
            'collection_method' => 'cash',
            'collection_status' => 'collected',
            'page' => 5,
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $statusBarPosition = strpos($content, 'data-testid="revenue-page-status-bar"');
        $alertPosition = strpos($content, 'data-testid="revenue-active-filter-alert"');
        $activeFilterCountPosition = strpos($content, 'data-testid="revenue-active-filter-count-card"');
        $filterLabelsPosition = strpos($content, 'data-testid="revenue-active-filter-labels-card"');

        $this->assertNotFalse($statusBarPosition);
        $this->assertNotFalse($alertPosition);
        $this->assertNotFalse($activeFilterCountPosition);
        $this->assertNotFalse($filterLabelsPosition);

        $this->assertStringContainsString('data-active-filter-alert-count="2"', $content);
        $this->assertStringNotContainsString('data-active-filter-alert-count="3"', $content);

        $this->assertLessThan($alertPosition, $statusBarPosition);
        $this->assertLessThan($activeFilterCountPosition, $alertPosition);
        $this->assertLessThan($filterLabelsPosition, $alertPosition);
    }
}
