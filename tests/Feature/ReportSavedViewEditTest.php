<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ReportSavedView;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_saved_views_index_shows_edit_link(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض قابل للتعديل',
            'filters' => [
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reports.saved-views.index'));

        $response->assertOk();
        $response->assertSee('data-testid="report-saved-view-edit-link"', false);
        $response->assertSee(route('reports.saved-views.edit', $savedView->id), false);
    }

    public function test_user_can_open_saved_view_edit_page(): void
    {
        $user = User::query()->firstOrFail();
        $customer = Customer::query()->orderBy('id')->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض قابل للتعديل',
            'filters' => [
                'customer_id' => $customer->id,
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reports.saved-views.edit', $savedView->id));

        $response->assertOk();
        $response->assertSee('data-testid="report-saved-view-edit-form"', false);
        $response->assertSee(route('reports.saved-views.update', $savedView->id), false);
        $response->assertSee('value="عرض قابل للتعديل"', false);
        $response->assertSee('تقرير أعمار فواتير المبيعات');
        $response->assertSee('customer_id');
        $response->assertSee((string) $customer->id);
        $response->assertSee('aging_bucket');
        $response->assertSee('without_due_date');
    }

    public function test_user_can_update_saved_view_name_and_default_state(): void
    {
        $user = User::query()->firstOrFail();

        $existingDefault = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'العرض الافتراضي القديم',
            'filters' => [
                'aging_bucket' => 'not_due',
            ],
            'is_default' => true,
        ]);

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'اسم قديم',
            'filters' => [
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->patch(route('reports.saved-views.update', $savedView->id), [
            'name' => 'اسم محدث',
            'is_default' => '1',
        ]);

        $response->assertRedirect(route('reports.saved-views.index'));

        $this->assertDatabaseHas('report_saved_views', [
            'id' => $savedView->id,
            'name' => 'اسم محدث',
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('report_saved_views', [
            'id' => $existingDefault->id,
            'is_default' => false,
        ]);
    }

    public function test_user_cannot_edit_another_users_saved_view(): void
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
            ->get(route('reports.saved-views.edit', $savedView->id))
            ->assertNotFound();

        $this->actingAs($user)
            ->patch(route('reports.saved-views.update', $savedView->id), [
                'name' => 'محاولة تعديل',
            ])
            ->assertNotFound();
    }

    public function test_saved_view_update_requires_name(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض قابل للتعديل',
            'filters' => [],
            'is_default' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('reports.saved-views.edit', $savedView->id))
            ->patch(route('reports.saved-views.update', $savedView->id), [
                'name' => '',
            ]);

        $response->assertRedirect(route('reports.saved-views.edit', $savedView->id));
        $response->assertSessionHasErrors('name');
    }
}
