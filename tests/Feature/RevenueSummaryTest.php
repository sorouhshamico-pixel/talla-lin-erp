<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenues_index_shows_revenue_summary_card(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('revenues.index'));

        $response->assertOk();

        $response->assertSee('data-testid="revenue-summary"', false);
        $response->assertSee('ملخص الإيرادات');
        $response->assertSee('العدد');
        $response->assertSee('الإجمالي');
        $response->assertSee('الضريبة');
        $response->assertSee('المحصل');
        $response->assertSee('غير المحصل');
    }
}
