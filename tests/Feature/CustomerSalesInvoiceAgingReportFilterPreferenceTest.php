<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Services\ReportFilterPreferenceService;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerSalesInvoiceAgingReportFilterPreferenceTest extends TestCase
{
    use RefreshDatabase;

    private const REPORT_KEY = 'customer-sales-invoice-aging';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_customer_aging_report_saves_submitted_filters_as_user_preferences(): void
    {
        $user = User::query()->firstOrFail();
        $customer = Customer::query()->orderBy('id')->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.index', [
            'customer_id' => $customer->id,
            'aging_bucket' => 'overdue_1_30',
        ]));

        $response->assertOk();

        $this->assertSame([
            'customer_id' => $customer->id,
            'aging_bucket' => 'overdue_1_30',
        ], app(ReportFilterPreferenceService::class)->get($user, self::REPORT_KEY));
    }

    public function test_customer_aging_report_reuses_saved_filter_preferences_when_no_filters_are_submitted(): void
    {
        $user = User::query()->firstOrFail();
        $customer = Customer::query()->orderBy('id')->firstOrFail();

        app(ReportFilterPreferenceService::class)->save($user, self::REPORT_KEY, [
            'customer_id' => $customer->id,
            'aging_bucket' => 'overdue_1_30',
        ]);

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.index'));

        $response->assertOk();
        $response->assertSee('value="' . $customer->id . '" selected', false);
        $response->assertSee('value="overdue_1_30" selected', false);
        $response->assertSee(e(route('reports.customer-sales-invoice-aging.print', [
            'customer_id' => $customer->id,
            'aging_bucket' => 'overdue_1_30',
        ])), false);
        $response->assertSee(e(route('reports.customer-sales-invoice-aging.export', [
            'customer_id' => $customer->id,
            'aging_bucket' => 'overdue_1_30',
        ])), false);
        $response->assertSee(e(route('reports.customer-sales-invoice-aging.drilldown', [
            'customer_id' => $customer->id,
            'aging_bucket' => 'overdue_1_30',
        ])), false);
    }

    public function test_customer_aging_report_reset_clears_saved_filter_preferences(): void
    {
        $user = User::query()->firstOrFail();
        $customer = Customer::query()->orderBy('id')->firstOrFail();
        $service = app(ReportFilterPreferenceService::class);

        $service->save($user, self::REPORT_KEY, [
            'customer_id' => $customer->id,
            'aging_bucket' => 'overdue_1_30',
        ]);

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.index', [
            'reset_filters' => 1,
        ]));

        $response->assertOk();

        $this->assertSame([], $service->get($user, self::REPORT_KEY));
        $response->assertSee(e(route('reports.customer-sales-invoice-aging.print')), false);
        $response->assertSee(e(route('reports.customer-sales-invoice-aging.export')), false);
    }

    public function test_customer_aging_report_print_and_export_can_use_saved_filter_preferences(): void
    {
        $user = User::query()->firstOrFail();
        $customer = Customer::query()->orderBy('id')->firstOrFail();

        app(ReportFilterPreferenceService::class)->save($user, self::REPORT_KEY, [
            'customer_id' => $customer->id,
            'aging_bucket' => 'overdue_1_30',
        ]);

        $printResponse = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.print'));

        $printResponse->assertOk();
        $printResponse->assertSee($customer->name);

        $exportResponse = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.export'));

        $exportResponse->assertOk();

        $content = $exportResponse->streamedContent();

        $this->assertStringContainsString($customer->name, $content);
    }
}
