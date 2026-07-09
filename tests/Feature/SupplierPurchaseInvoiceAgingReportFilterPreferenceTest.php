<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use App\Services\ReportFilterPreferenceService;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierPurchaseInvoiceAgingReportFilterPreferenceTest extends TestCase
{
    use RefreshDatabase;

    private const REPORT_KEY = 'supplier-purchase-invoice-aging';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_supplier_aging_report_saves_submitted_filters_as_user_preferences(): void
    {
        $user = User::query()->firstOrFail();
        $supplier = Supplier::query()->orderBy('id')->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.index', [
            'supplier_id' => $supplier->id,
            'aging_bucket' => 'overdue_1_30',
        ]));

        $response->assertOk();

        $this->assertSame([
            'supplier_id' => $supplier->id,
            'aging_bucket' => 'overdue_1_30',
        ], app(ReportFilterPreferenceService::class)->get($user, self::REPORT_KEY));
    }

    public function test_supplier_aging_report_reuses_saved_filter_preferences_when_no_filters_are_submitted(): void
    {
        $user = User::query()->firstOrFail();
        $supplier = Supplier::query()->orderBy('id')->firstOrFail();

        app(ReportFilterPreferenceService::class)->save($user, self::REPORT_KEY, [
            'supplier_id' => $supplier->id,
            'aging_bucket' => 'overdue_1_30',
        ]);

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.index'));

        $response->assertOk();
        $response->assertSee($supplier->name);
        $response->assertSee('متأخرة 1 إلى 30');
        $response->assertSee(e(route('reports.supplier-purchase-invoice-aging.print', [
            'supplier_id' => $supplier->id,
            'aging_bucket' => 'overdue_1_30',
        ])), false);
        $response->assertSee(e(route('reports.supplier-purchase-invoice-aging.export', [
            'supplier_id' => $supplier->id,
            'aging_bucket' => 'overdue_1_30',
        ])), false);
        $response->assertSee(e(route('reports.supplier-purchase-invoice-aging.drilldown', [
            'supplier_id' => $supplier->id,
            'aging_bucket' => 'overdue_1_30',
        ])), false);
    }

    public function test_supplier_aging_report_reset_clears_saved_filter_preferences(): void
    {
        $user = User::query()->firstOrFail();
        $supplier = Supplier::query()->orderBy('id')->firstOrFail();
        $service = app(ReportFilterPreferenceService::class);

        $service->save($user, self::REPORT_KEY, [
            'supplier_id' => $supplier->id,
            'aging_bucket' => 'overdue_1_30',
        ]);

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.index', [
            'reset_filters' => 1,
        ]));

        $response->assertOk();

        $this->assertSame([], $service->get($user, self::REPORT_KEY));
        $response->assertSee(e(route('reports.supplier-purchase-invoice-aging.print')), false);
        $response->assertSee(e(route('reports.supplier-purchase-invoice-aging.export')), false);
    }

    public function test_supplier_aging_report_print_and_export_can_use_saved_filter_preferences(): void
    {
        $user = User::query()->firstOrFail();
        $supplier = Supplier::query()->orderBy('id')->firstOrFail();

        app(ReportFilterPreferenceService::class)->save($user, self::REPORT_KEY, [
            'supplier_id' => $supplier->id,
            'aging_bucket' => 'overdue_1_30',
        ]);

        $printResponse = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.print'));

        $printResponse->assertOk();
        $printResponse->assertSee($supplier->name);

        $exportResponse = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.export'));

        $exportResponse->assertOk();

        $content = $exportResponse->streamedContent();

        $this->assertStringContainsString($supplier->name, $content);
    }
}
