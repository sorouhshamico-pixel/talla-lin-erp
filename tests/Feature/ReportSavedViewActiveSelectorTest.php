<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewActiveSelectorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_sales_invoice_saved_view_selector_marks_active_saved_view(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض مبيعات نشط',
            'filters' => [
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-aging.index', [
            'saved_view_id' => $savedView->id,
        ]));

        $response->assertOk();
        $response->assertSee('active-saved-view-row', false);
        $response->assertSee('data-testid="sales-invoice-aging-saved-view-active-badge"', false);
        $response->assertSee('saved_view_id=' . $savedView->id, false);
        $response->assertSee('عرض مبيعات نشط');
    }

    public function test_customer_aging_saved_view_selector_marks_active_saved_view(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'customer-sales-invoice-aging',
            'name' => 'عرض عملاء نشط',
            'filters' => [
                'aging_bucket' => 'not_due',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.index', [
            'saved_view_id' => $savedView->id,
        ]));

        $response->assertOk();
        $response->assertSee('active-saved-view-row', false);
        $response->assertSee('data-testid="customer-aging-saved-view-active-badge"', false);
        $response->assertSee('saved_view_id=' . $savedView->id, false);
        $response->assertSee('عرض عملاء نشط');
    }

    public function test_supplier_aging_saved_view_selector_marks_active_saved_view(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'supplier-purchase-invoice-aging',
            'name' => 'عرض موردين نشط',
            'filters' => [
                'aging_bucket' => 'overdue_1_30',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.index', [
            'saved_view_id' => $savedView->id,
        ]));

        $response->assertOk();
        $response->assertSee('active-saved-view-row', false);
        $response->assertSee('data-testid="supplier-aging-saved-view-active-badge"', false);
        $response->assertSee('saved_view_id=' . $savedView->id, false);
        $response->assertSee('عرض موردين نشط');
    }

    public function test_customer_drilldown_saved_view_selector_marks_active_saved_view(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'customer-sales-invoice-aging-drilldown',
            'name' => 'عرض تفاصيل عميل نشط',
            'filters' => [
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.drilldown', [
            'saved_view_id' => $savedView->id,
        ]));

        $response->assertOk();
        $response->assertSee('active-saved-view-row', false);
        $response->assertSee('data-testid="customer-aging-drilldown-saved-view-active-badge"', false);
        $response->assertSee('saved_view_id=' . $savedView->id, false);
    }

    public function test_supplier_drilldown_saved_view_selector_marks_active_saved_view(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'supplier-purchase-invoice-aging-drilldown',
            'name' => 'عرض تفاصيل مورد نشط',
            'filters' => [
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.drilldown', [
            'saved_view_id' => $savedView->id,
        ]));

        $response->assertOk();
        $response->assertSee('active-saved-view-row', false);
        $response->assertSee('data-testid="supplier-aging-drilldown-saved-view-active-badge"', false);
        $response->assertSee('saved_view_id=' . $savedView->id, false);
    }
}
