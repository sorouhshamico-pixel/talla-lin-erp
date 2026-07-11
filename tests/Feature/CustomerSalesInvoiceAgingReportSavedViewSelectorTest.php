<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ReportSavedView;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerSalesInvoiceAgingReportSavedViewSelectorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_customer_aging_report_shows_empty_saved_views_selector(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.index'));

        $response->assertOk();
        $response->assertSee('data-testid="customer-aging-saved-views-selector"', false);
        $response->assertSee('data-testid="customer-aging-saved-views-empty"', false);
        $response->assertSee('لا توجد عروض محفوظة لهذا التقرير حتى الآن.');
        $response->assertSee(route('reports.saved-views.index'), false);
    }

    public function test_customer_aging_report_lists_saved_views_for_current_report(): void
    {
        $user = User::query()->firstOrFail();
        $customer = Customer::query()->orderBy('id')->firstOrFail();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'customer-sales-invoice-aging',
            'name' => 'عرض افتراضي لذمم العملاء',
            'filters' => [
                'customer_id' => $customer->id,
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => true,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض لا يخص التقرير الحالي',
            'filters' => [
                'customer_id' => $customer->id,
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.index', [
            'aging_bucket' => 'not_due',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="customer-aging-saved-views-list"', false);
        $response->assertSee('data-testid="customer-aging-saved-view-open-link"', false);
        $response->assertSee('عرض افتراضي لذمم العملاء');
        $response->assertSee('data-testid="customer-aging-saved-view-default-badge"', false);
        $response->assertSee('customer_id=' . $customer->id, false);
        $response->assertSee('aging_bucket=without_due_date', false);
        $response->assertDontSee('عرض لا يخص التقرير الحالي');
    }
}
