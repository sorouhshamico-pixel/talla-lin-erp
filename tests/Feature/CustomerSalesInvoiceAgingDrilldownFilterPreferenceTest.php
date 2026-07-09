<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Services\ReportFilterPreferenceService;
use Carbon\Carbon;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerSalesInvoiceAgingDrilldownFilterPreferenceTest extends TestCase
{
    use RefreshDatabase;

    private const REPORT_KEY = 'customer-sales-invoice-aging-drilldown';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-06 10:00:00');

        $this->seed(InitialSetupSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_customer_aging_drilldown_saves_submitted_filters_as_user_preferences(): void
    {
        $user = User::query()->firstOrFail();
        $customer = Customer::query()->orderBy('id')->firstOrFail();
        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.drilldown', [
            'customer_id' => $customer->id,
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-31',
            'aging_bucket' => 'without_due_date',
        ]));

        $response->assertOk();

        $this->assertSame([
            'customer_id' => $customer->id,
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-31',
            'aging_bucket' => 'without_due_date',
        ], app(ReportFilterPreferenceService::class)->get($user, self::REPORT_KEY));
    }

    public function test_customer_aging_drilldown_reuses_saved_filter_preferences_when_no_filters_are_submitted(): void
    {
        $user = User::query()->firstOrFail();
        $customer = Customer::query()->orderBy('id')->firstOrFail();
        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');

        app(ReportFilterPreferenceService::class)->save($user, self::REPORT_KEY, [
            'customer_id' => $customer->id,
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-31',
            'aging_bucket' => 'without_due_date',
        ]);

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.drilldown'));

        $response->assertOk();
        $response->assertSee($customer->name);
        $response->assertSee('value="2026-07-31"', false);
        $response->assertSee('بدون تاريخ استحقاق');
        $response->assertSee(e(route('reports.customer-sales-invoice-aging.drilldown.export', [
            'customer_id' => $customer->id,
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-31',
            'aging_bucket' => 'without_due_date',
        ])), false);
    }

    public function test_customer_aging_drilldown_reset_clears_saved_filter_preferences(): void
    {
        $user = User::query()->firstOrFail();
        $customer = Customer::query()->orderBy('id')->firstOrFail();
        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');
        $service = app(ReportFilterPreferenceService::class);

        $service->save($user, self::REPORT_KEY, [
            'customer_id' => $customer->id,
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-31',
            'aging_bucket' => 'without_due_date',
        ]);

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.drilldown', [
            'reset_filters' => 1,
        ]));

        $response->assertOk();

        $this->assertSame([], $service->get($user, self::REPORT_KEY));
        $response->assertSee('value="2026-07-06"', false);
        $response->assertSee(e(route('reports.customer-sales-invoice-aging.drilldown.export')), false);
    }

    public function test_customer_aging_drilldown_export_can_use_saved_filter_preferences(): void
    {
        $user = User::query()->firstOrFail();
        $customer = Customer::query()->orderBy('id')->firstOrFail();
        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');

        app(ReportFilterPreferenceService::class)->save($user, self::REPORT_KEY, [
            'customer_id' => $customer->id,
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-31',
            'aging_bucket' => 'without_due_date',
        ]);

        $exportResponse = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.drilldown.export'));

        $exportResponse->assertOk();

        $content = $exportResponse->streamedContent();

        $this->assertStringContainsString($customer->name, $content);
        $this->assertStringContainsString('2026-07-31', $content);
        $this->assertStringContainsString('بدون تاريخ استحقاق', $content);
    }
}
