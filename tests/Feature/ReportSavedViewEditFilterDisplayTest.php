<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ReportSavedView;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportSavedViewEditFilterDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_edit_page_shows_readable_filter_labels_and_values(): void
    {
        $user = User::query()->firstOrFail();
        $customer = Customer::query()->orderBy('id')->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض فلاتر مقروءة',
            'filters' => [
                'customer_id' => $customer->id,
                'aging_bucket' => 'without_due_date',
                'payment_status' => 'paid',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reports.saved-views.edit', $savedView->id));

        $response->assertOk();
        $response->assertSee('data-testid="report-saved-view-edit-filter-label"', false);
        $response->assertSee('العميل');
        $response->assertSee('شريحة العمر');
        $response->assertSee('بدون تاريخ استحقاق');
        $response->assertSee('حالة الدفع');
        $response->assertSee('مدفوعة');
        $response->assertDontSee('customer_id');
        $response->assertDontSee('aging_bucket');
        $response->assertDontSee('payment_status');
    }

    public function test_edit_page_shows_supplier_and_branch_filter_labels(): void
    {
        $user = User::query()->firstOrFail();
        $supplier = Supplier::query()->orderBy('id')->firstOrFail();
        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'supplier-purchase-invoice-aging-drilldown',
            'name' => 'عرض مورد وفرع',
            'filters' => [
                'supplier_id' => $supplier->id,
                'branch_id' => $branchId,
                'as_of_date' => '2026-07-31',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reports.saved-views.edit', $savedView->id));

        $response->assertOk();
        $response->assertSee('المورد');
        $response->assertSee('الفرع');
        $response->assertSee('حتى تاريخ');
        $response->assertSee('2026-07-31');
        $response->assertDontSee('supplier_id');
        $response->assertDontSee('branch_id');
        $response->assertDontSee('as_of_date');
    }
}
