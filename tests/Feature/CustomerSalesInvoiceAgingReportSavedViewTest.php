<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ReportSavedView;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerSalesInvoiceAgingReportSavedViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_customer_aging_report_shows_save_view_form(): void
    {
        $user = User::query()->firstOrFail();
        $customer = Customer::query()->orderBy('id')->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.index', [
            'customer_id' => $customer->id,
            'aging_bucket' => 'without_due_date',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="customer-aging-save-view-form"', false);
        $response->assertSee(route('reports.customer-sales-invoice-aging.saved-views.store'), false);
        $response->assertSee('value="' . $customer->id . '"', false);
        $response->assertSee('value="without_due_date"', false);
    }

    public function test_user_can_save_customer_aging_filters_as_named_view(): void
    {
        $user = User::query()->firstOrFail();
        $customer = Customer::query()->orderBy('id')->firstOrFail();

        $response = $this->actingAs($user)->post(route('reports.customer-sales-invoice-aging.saved-views.store'), [
            'name' => 'متابعة ذمم العملاء',
            'customer_id' => $customer->id,
            'aging_bucket' => 'without_due_date',
            'is_default' => '1',
        ]);

        $response->assertRedirect(route('reports.customer-sales-invoice-aging.index', [
            'customer_id' => $customer->id,
            'aging_bucket' => 'without_due_date',
        ]));

        $savedView = ReportSavedView::query()->firstOrFail();

        $this->assertSame($user->id, $savedView->user_id);
        $this->assertSame('customer-sales-invoice-aging', $savedView->report_key);
        $this->assertSame('متابعة ذمم العملاء', $savedView->name);
        $this->assertTrue($savedView->is_default);
        $this->assertSame([
            'customer_id' => $customer->id,
            'aging_bucket' => 'without_due_date',
        ], $savedView->filters);
    }

    public function test_customer_aging_saved_view_requires_name(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this
            ->actingAs($user)
            ->from(route('reports.customer-sales-invoice-aging.index'))
            ->post(route('reports.customer-sales-invoice-aging.saved-views.store'), [
                'name' => '',
                'aging_bucket' => 'without_due_date',
            ]);

        $response->assertRedirect(route('reports.customer-sales-invoice-aging.index'));
        $response->assertSessionHasErrors('name');
    }
}
