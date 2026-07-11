<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ReportSavedView;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerSalesInvoiceAgingDrilldownSavedViewSelectorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_customer_aging_drilldown_shows_empty_saved_views_selector(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.drilldown'));

        $response->assertOk();
        $response->assertSee('data-testid="customer-aging-drilldown-saved-views-selector"', false);
        $response->assertSee('data-testid="customer-aging-drilldown-saved-views-empty"', false);
        $response->assertSee('لا توجد عروض محفوظة لهذه التفاصيل حتى الآن.');
        $response->assertSee(route('reports.saved-views.index'), false);
    }

    public function test_customer_aging_drilldown_lists_saved_views_for_current_report(): void
    {
        $user = User::query()->firstOrFail();
        $customer = Customer::query()->orderBy('id')->firstOrFail();
        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'customer-sales-invoice-aging-drilldown',
            'name' => 'عرض افتراضي لتفاصيل العملاء',
            'filters' => [
                'customer_id' => $customer->id,
                'branch_id' => $branchId,
                'as_of_date' => '2026-07-31',
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => true,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'customer-sales-invoice-aging',
            'name' => 'عرض لا يخص التفاصيل الحالية',
            'filters' => [
                'customer_id' => $customer->id,
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.drilldown', [
            'aging_bucket' => 'not_due',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="customer-aging-drilldown-saved-views-list"', false);
        $response->assertSee('data-testid="customer-aging-drilldown-saved-view-open-link"', false);
        $response->assertSee('عرض افتراضي لتفاصيل العملاء');
        $response->assertSee('data-testid="customer-aging-drilldown-saved-view-default-badge"', false);
        $response->assertSee('customer_id=' . $customer->id, false);
        $response->assertSee('branch_id=' . $branchId, false);
        $response->assertSee('as_of_date=2026-07-31', false);
        $response->assertSee('aging_bucket=without_due_date', false);
        $response->assertDontSee('عرض لا يخص التفاصيل الحالية');
    }
}
