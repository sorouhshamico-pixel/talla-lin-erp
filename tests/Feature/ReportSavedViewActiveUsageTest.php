<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewActiveUsageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_apply_saved_view_keeps_saved_view_id_in_report_redirect(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض نشط',
            'filters' => [
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reports.saved-views.apply', $savedView->id));

        $response->assertRedirect();

        $location = urldecode((string) $response->headers->get('Location'));

        $this->assertStringContainsString('aging_bucket=without_due_date', $location);
        $this->assertStringContainsString('saved_view_id=' . $savedView->id, $location);
    }

    public function test_applied_saved_view_is_highlighted_on_report_page(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض نشط للتقرير',
            'filters' => [
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => false,
        ]);

        $response = $this
            ->followingRedirects()
            ->actingAs($user)
            ->get(route('reports.saved-views.apply', $savedView->id));

        $response->assertOk();
        $response->assertSee('data-testid="active-saved-view-banner"', false);
        $response->assertSee('data-testid="active-saved-view-name"', false);
        $response->assertSee('عرض نشط للتقرير');
    }
}
