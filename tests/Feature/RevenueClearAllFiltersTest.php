<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueClearAllFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenues_index_shows_clear_all_filters_shortcut(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index'));

        $response->assertOk();

        $response->assertSee('data-testid="revenue-clear-all-filters-card"', false);
        $response->assertSee('مسح كل فلاتر الإيرادات');
        $response->assertSee('مسح كل الفلاتر');
        $response->assertSee('data-testid="revenue-clear-all-filters"', false);
    }

    public function test_clear_all_filters_link_removes_all_query_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index', [
            'collection_method' => 'cash',
            'collection_status' => 'collected',
            'archive_status' => 'archived',
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
            'page' => 2,
        ]));

        $response->assertOk();

        $content = $response->getContent();

        preg_match(
            '/<a\s+[^>]*href="([^"]+)"[^>]*data-testid="revenue-clear-all-filters"[^>]*>/s',
            $content,
            $matches
        );

        $this->assertNotEmpty($matches, 'Clear all revenue filters link was not found.');

        $href = html_entity_decode($matches[1]);

        $this->assertStringEndsWith('/revenues', $href);
        $this->assertStringNotContainsString('collection_method=', $href);
        $this->assertStringNotContainsString('collection_status=', $href);
        $this->assertStringNotContainsString('archive_status=', $href);
        $this->assertStringNotContainsString('date_from=', $href);
        $this->assertStringNotContainsString('date_to=', $href);
        $this->assertStringNotContainsString('page=', $href);
        $this->assertStringNotContainsString('?', $href);
    }
}
