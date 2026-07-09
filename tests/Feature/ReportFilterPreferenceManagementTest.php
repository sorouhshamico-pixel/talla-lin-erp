<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ReportFilterPreferenceService;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportFilterPreferenceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_user_can_view_saved_report_filter_preferences(): void
    {
        $user = User::query()->firstOrFail();

        app(ReportFilterPreferenceService::class)->save($user, 'sales-invoice-aging', [
            'customer_id' => 1,
            'payment_status' => 'partial',
            'aging_bucket' => 'without_due_date',
        ]);

        $response = $this->actingAs($user)->get(route('reports.filter-preferences.index'));

        $response->assertOk();
        $response->assertSee('data-testid="report-filter-preferences-page"', false);
        $response->assertSee('تقرير أعمار فواتير المبيعات');
        $response->assertSee('حالة الدفع');
        $response->assertSee('partial');
    }

    public function test_user_can_delete_a_single_report_filter_preference(): void
    {
        $user = User::query()->firstOrFail();
        $service = app(ReportFilterPreferenceService::class);

        $service->save($user, 'sales-invoice-aging', [
            'payment_status' => 'partial',
        ]);

        $service->save($user, 'customer-sales-invoice-aging', [
            'aging_bucket' => 'overdue_1_30',
        ]);

        $response = $this->actingAs($user)->delete(route('reports.filter-preferences.destroy', 'sales-invoice-aging'));

        $response->assertRedirect(route('reports.filter-preferences.index'));

        $this->assertSame([], $service->get($user, 'sales-invoice-aging'));
        $this->assertSame([
            'aging_bucket' => 'overdue_1_30',
        ], $service->get($user, 'customer-sales-invoice-aging'));
    }

    public function test_user_can_delete_all_report_filter_preferences(): void
    {
        $user = User::query()->firstOrFail();
        $service = app(ReportFilterPreferenceService::class);

        $service->save($user, 'sales-invoice-aging', [
            'payment_status' => 'partial',
        ]);

        $service->save($user, 'supplier-purchase-invoice-aging', [
            'aging_bucket' => 'without_due_date',
        ]);

        $response = $this->actingAs($user)->delete(route('reports.filter-preferences.destroy-all'));

        $response->assertRedirect(route('reports.filter-preferences.index'));

        $this->assertSame([], $service->get($user, 'sales-invoice-aging'));
        $this->assertSame([], $service->get($user, 'supplier-purchase-invoice-aging'));
    }
}
