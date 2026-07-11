<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewClearActiveLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_active_saved_view_banner_shows_clear_link_without_saved_view_id(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض نشط قابل للإلغاء',
            'filters' => [
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-aging.index', [
            'saved_view_id' => $savedView->id,
            'aging_bucket' => 'without_due_date',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="active-saved-view-banner"', false);
        $response->assertSee('data-testid="active-saved-view-clear-link"', false);
        $response->assertSee('عرض التقرير بدون العرض المحفوظ');

        $content = $response->getContent();

        $this->assertIsString($content);
        $this->assertMatchesRegularExpression(
            '/href="([^"]+)"[^>]*data-testid="active-saved-view-clear-link"/',
            $content
        );

        preg_match('/href="([^"]+)"[^>]*data-testid="active-saved-view-clear-link"/', $content, $matches);

        $clearUrl = html_entity_decode($matches[1]);

        $this->assertStringContainsString('aging_bucket=without_due_date', $clearUrl);
        $this->assertStringNotContainsString('saved_view_id=', $clearUrl);
    }

    public function test_clear_link_is_not_shown_without_active_saved_view(): void
    {
        $user = User::query()->firstOrFail();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض غير نشط',
            'filters' => [
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-aging.index', [
            'aging_bucket' => 'without_due_date',
        ]));

        $response->assertOk();
        $response->assertDontSee('data-testid="active-saved-view-banner"', false);
        $response->assertDontSee('data-testid="active-saved-view-clear-link"', false);
    }
}
