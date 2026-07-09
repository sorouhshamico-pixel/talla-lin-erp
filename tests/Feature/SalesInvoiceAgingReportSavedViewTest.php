<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ReportSavedView;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoiceAgingReportSavedViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_sales_invoice_aging_report_shows_save_view_form(): void
    {
        $user = User::query()->firstOrFail();
        $customer = Customer::query()->orderBy('id')->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-aging.index', [
            'customer_id' => $customer->id,
            'payment_status' => 'partial',
            'aging_bucket' => 'without_due_date',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-aging-save-view-form"', false);
        $response->assertSee(route('reports.sales-invoice-aging.saved-views.store'), false);
        $response->assertSee('value="' . $customer->id . '"', false);
        $response->assertSee('value="partial"', false);
        $response->assertSee('value="without_due_date"', false);
    }

    public function test_user_can_save_sales_invoice_aging_filters_as_named_view(): void
    {
        $user = User::query()->firstOrFail();
        $customer = Customer::query()->orderBy('id')->firstOrFail();

        $response = $this->actingAs($user)->post(route('reports.sales-invoice-aging.saved-views.store'), [
            'name' => 'متابعة التحصيل الجزئي',
            'customer_id' => $customer->id,
            'payment_status' => 'partial',
            'aging_bucket' => 'without_due_date',
            'is_default' => '1',
        ]);

        $response->assertRedirect(route('reports.sales-invoice-aging.index', [
            'customer_id' => $customer->id,
            'payment_status' => 'partial',
            'aging_bucket' => 'without_due_date',
        ]));

        $savedView = ReportSavedView::query()->firstOrFail();

        $this->assertSame($user->id, $savedView->user_id);
        $this->assertSame('sales-invoice-aging', $savedView->report_key);
        $this->assertSame('متابعة التحصيل الجزئي', $savedView->name);
        $this->assertTrue($savedView->is_default);
        $this->assertSame([
            'customer_id' => $customer->id,
            'payment_status' => 'partial',
            'aging_bucket' => 'without_due_date',
        ], $savedView->filters);
    }

    public function test_sales_invoice_aging_saved_view_requires_name(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this
            ->actingAs($user)
            ->from(route('reports.sales-invoice-aging.index'))
            ->post(route('reports.sales-invoice-aging.saved-views.store'), [
                'name' => '',
                'payment_status' => 'partial',
            ]);

        $response->assertRedirect(route('reports.sales-invoice-aging.index'));
        $response->assertSessionHasErrors('name');
    }
}
