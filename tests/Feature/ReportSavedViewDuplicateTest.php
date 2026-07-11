<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewDuplicateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_saved_views_index_shows_duplicate_action(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض قابل للنسخ',
            'filters' => [
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reports.saved-views.index'));

        $response->assertOk();
        $response->assertSee('data-testid="report-saved-view-duplicate-form"', false);
        $response->assertSee(route('reports.saved-views.duplicate', $savedView->id), false);
    }

    public function test_user_can_duplicate_saved_view(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض أصلي',
            'filters' => [
                'customer_id' => '1',
                'aging_bucket' => 'not_due',
            ],
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)->post(route('reports.saved-views.duplicate', $savedView->id));

        $response->assertRedirect(route('reports.saved-views.index'));

        $this->assertDatabaseHas('report_saved_views', [
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض أصلي - نسخة',
            'is_default' => false,
        ]);

        $duplicate = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('name', 'عرض أصلي - نسخة')
            ->firstOrFail();

        $this->assertSame($savedView->filters, $duplicate->filters);

        $savedView->refresh();

        $this->assertTrue((bool) $savedView->is_default);
    }

    public function test_user_cannot_duplicate_another_users_saved_view(): void
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
            ->post(route('reports.saved-views.duplicate', $savedView->id))
            ->assertNotFound();

        $this->assertSame(1, ReportSavedView::query()->count());
    }

    public function test_duplicate_name_is_truncated_to_safe_length(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => str_repeat('أ', 140),
            'filters' => [],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->post(route('reports.saved-views.duplicate', $savedView->id));

        $response->assertRedirect(route('reports.saved-views.index'));

        $duplicate = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->whereKeyNot($savedView->id)
            ->firstOrFail();

        $this->assertLessThanOrEqual(120, mb_strlen($duplicate->name));
    }
}
