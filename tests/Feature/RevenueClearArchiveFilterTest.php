<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueClearArchiveFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenues_index_shows_clear_archive_filter_link(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index'));

        $response->assertOk();

        $response->assertSee('data-testid="revenue-clear-archive-filter-card"', false);
        $response->assertSee('إلغاء فلتر الأرشفة');
        $response->assertSee('عرض كل حالات الأرشفة');
        $response->assertSee('data-testid="revenue-clear-archive-filter"', false);
    }

    public function test_clear_archive_filter_link_preserves_non_archive_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index', [
            'collection_method' => 'cash',
            'collection_status' => 'collected',
            'archive_status' => 'archived',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        preg_match(
            '/<a\s+[^>]*href="([^"]+)"[^>]*data-testid="revenue-clear-archive-filter"[^>]*>/s',
            $content,
            $matches
        );

        $this->assertNotEmpty($matches, 'Clear archive filter link was not found.');

        $href = html_entity_decode($matches[1]);

        $this->assertStringContainsString('collection_method=cash', $href);
        $this->assertStringContainsString('collection_status=collected', $href);
        $this->assertStringNotContainsString('archive_status=archived', $href);
        $this->assertStringNotContainsString('archived=1', $href);
    }
}
