<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewApplyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_saved_views_index_shows_apply_link(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض قابل للتطبيق',
            'filters' => [
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reports.saved-views.index'));

        $response->assertOk();
        $response->assertSee('data-testid="report-saved-view-apply-link"', false);
        $response->assertSee(route('reports.saved-views.apply', $savedView->id), false);
    }

    public function test_user_can_apply_saved_view_and_redirect_to_report_with_filters(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض قابل للتطبيق',
            'filters' => [
                'customer_id' => '1',
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reports.saved-views.apply', $savedView->id));

        $response->assertRedirect();

        $location = $response->headers->get('Location');

        $this->assertIsString($location);
        $this->assertStringContainsString('aging_bucket=without_due_date', urldecode($location));
        $this->assertStringContainsString('customer_id=1', urldecode($location));
    }

    public function test_user_cannot_apply_another_users_saved_view(): void
    {
        $user = User::query()->firstOrFail();
        $otherUser = User::factory()->create();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $otherUser->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض مستخدم آخر',
            'filters' => [
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-views.apply', $savedView->id))
            ->assertNotFound();
    }

    public function test_apply_unknown_report_redirects_back_to_management_page(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'unknown-report',
            'name' => 'عرض غير معروف',
            'filters' => [],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reports.saved-views.apply', $savedView->id));

        $response->assertRedirect(route('reports.saved-views.index'));
        $response->assertSessionHas('status', 'لا يمكن تطبيق هذا العرض لأن مسار التقرير غير معروف.');
    }
}
