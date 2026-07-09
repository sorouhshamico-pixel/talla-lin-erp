<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ReportSavedView;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerSalesInvoiceAgingReportDefaultSavedViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_customer_aging_report_applies_default_saved_view_when_opened_without_filters(): void
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

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.index'));

        $response->assertOk();

        $preference = DB::table('user_report_filter_preferences')
            ->where('user_id', $user->id)
            ->where('report_key', 'customer-sales-invoice-aging')
            ->first();

        $this->assertNotNull($preference);

        $filters = json_decode($preference->filters, true);

        $this->assertSame($customer->id, $filters['customer_id']);
        $this->assertSame('without_due_date', $filters['aging_bucket']);
    }

    public function test_customer_aging_report_explicit_filters_override_default_saved_view(): void
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

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.index', [
            'aging_bucket' => 'not_due',
        ]));

        $response->assertOk();

        $preference = DB::table('user_report_filter_preferences')
            ->where('user_id', $user->id)
            ->where('report_key', 'customer-sales-invoice-aging')
            ->first();

        $this->assertNotNull($preference);

        $filters = json_decode($preference->filters, true);
        $nonEmptyFilters = array_filter($filters, fn ($value) => $value !== null && $value !== '');

        $this->assertSame('not_due', $filters['aging_bucket']);
        $this->assertArrayNotHasKey('customer_id', $nonEmptyFilters);
    }

    public function test_customer_aging_report_reset_filters_does_not_apply_default_saved_view(): void
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

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.index', [
            'reset_filters' => 1,
        ]));

        $response->assertOk();

        $this->assertDatabaseMissing('user_report_filter_preferences', [
            'user_id' => $user->id,
            'report_key' => 'customer-sales-invoice-aging',
        ]);
    }
}
