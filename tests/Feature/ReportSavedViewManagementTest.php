<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Services\ReportSavedViewService;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_user_can_view_report_saved_views(): void
    {
        $user = User::query()->firstOrFail();

        app(ReportSavedViewService::class)->save($user, 'sales-invoice-aging', 'متابعة التحصيل', [
            'payment_status' => 'partial',
            'aging_bucket' => 'without_due_date',
        ], true);

        $response = $this->actingAs($user)->get(route('reports.saved-views.index'));

        $response->assertOk();
        $response->assertSee('data-testid="report-saved-views-page"', false);
        $response->assertSee('متابعة التحصيل');
        $response->assertSee('تقرير أعمار ذمم فواتير المبيعات');
        $response->assertSee('مدفوعة جزئيًا');
        $response->assertSee('بدون تاريخ استحقاق');
        $response->assertSee('data-testid="report-saved-view-open-link"', false);
        $response->assertSee('payment_status=partial', false);
        $response->assertSee('data-testid="report-saved-view-default-badge"', false);
    }

    public function test_user_can_make_saved_view_default(): void
    {
        $user = User::query()->firstOrFail();
        $service = app(ReportSavedViewService::class);

        $first = $service->save($user, 'sales-invoice-aging', 'عرض أول', [
            'payment_status' => 'partial',
        ], true);

        $second = $service->save($user, 'sales-invoice-aging', 'عرض ثاني', [
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($user)->patch(route('reports.saved-views.make-default', $second));

        $response->assertRedirect(route('reports.saved-views.index'));

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
    }

    public function test_user_can_delete_single_saved_view(): void
    {
        $user = User::query()->firstOrFail();
        $service = app(ReportSavedViewService::class);

        $first = $service->save($user, 'sales-invoice-aging', 'عرض أول', [
            'payment_status' => 'partial',
        ]);

        $second = $service->save($user, 'sales-invoice-aging', 'عرض ثاني', [
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($user)->delete(route('reports.saved-views.destroy', $first));

        $response->assertRedirect(route('reports.saved-views.index'));

        $this->assertDatabaseMissing('report_saved_views', [
            'id' => $first->id,
        ]);

        $this->assertDatabaseHas('report_saved_views', [
            'id' => $second->id,
        ]);
    }

    public function test_user_can_delete_all_saved_views(): void
    {
        $user = User::query()->firstOrFail();
        $service = app(ReportSavedViewService::class);

        $service->save($user, 'sales-invoice-aging', 'عرض أول', [
            'payment_status' => 'partial',
        ]);

        $service->save($user, 'supplier-purchase-invoice-aging', 'عرض موردين', [
            'aging_bucket' => 'overdue_1_30',
        ]);

        $response = $this->actingAs($user)->delete(route('reports.saved-views.destroy-all'));

        $response->assertRedirect(route('reports.saved-views.index'));

        $this->assertSame(0, ReportSavedView::query()->where('user_id', $user->id)->count());
    }

    public function test_user_cannot_delete_another_users_saved_view(): void
    {
        $users = User::query()->orderBy('id')->take(2)->get();

        if ($users->count() < 2) {
            $users->push(User::factory()->create());
        }

        $owner = $users[0];
        $other = $users[1];

        $savedView = app(ReportSavedViewService::class)->save($owner, 'sales-invoice-aging', 'خاص', [
            'payment_status' => 'partial',
        ]);

        $response = $this->actingAs($other)->delete(route('reports.saved-views.destroy', $savedView));

        $response->assertNotFound();

        $this->assertDatabaseHas('report_saved_views', [
            'id' => $savedView->id,
        ]);
    }
}
