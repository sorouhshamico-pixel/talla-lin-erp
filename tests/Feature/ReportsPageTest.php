<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Services\InventoryStockService;
use App\Services\PurchaseInvoiceService;
use App\Services\SalesInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_reports_page(): void
    {
        $this->get('/reports')->assertRedirect('/login');
    }

    public function test_owner_can_view_reports_page(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();

        $response = $this->actingAs($admin)->get('/reports');

        $response->assertOk();
        $response->assertSee('التقارير المالية الأساسية');
        $response->assertSee('تقرير المبيعات');
        $response->assertSee('تقرير المشتريات');
        $response->assertSee('تقرير المخزون');
        $response->assertSee('ربح أولي قبل الضريبة');
        $response->assertSee('تطبيق الفلتر');
        $response->assertSee('كل الفروع');
    }

    public function test_reports_show_sales_purchase_payment_and_profit_totals(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();

        $salesInvoice = SalesInvoice::query()
            ->where('invoice_number', 'INV-DEMO-001')
            ->firstOrFail();

        $purchaseInvoice = PurchaseInvoice::query()
            ->where('invoice_number', 'PINV-DEMO-001')
            ->firstOrFail();

        app(SalesInvoiceService::class)->issueInvoice(
            invoice: $salesInvoice,
            stockService: app(InventoryStockService::class)
        );

        app(SalesInvoiceService::class)->recordPayment(
            invoice: $salesInvoice->refresh(),
            user: $admin,
            amount: 300,
            method: 'cash',
            referenceNumber: 'REPORT-SALE-PAY'
        );

        app(PurchaseInvoiceService::class)->receiveInvoice(
            invoice: $purchaseInvoice,
            stockService: app(InventoryStockService::class)
        );

        app(PurchaseInvoiceService::class)->recordPayment(
            invoice: $purchaseInvoice->refresh(),
            user: $admin,
            amount: 100,
            method: 'cash',
            referenceNumber: 'REPORT-PURCHASE-PAY'
        );

        $response = $this->actingAs($admin)->get('/reports');

        $response->assertOk();

        $response->assertSee('851.00');
        $response->assertSee('300.00');
        $response->assertSee('551.00');

        $response->assertSee('414.00');
        $response->assertSee('100.00');
        $response->assertSee('314.00');

        $response->assertSee('390.00');
        $response->assertSee('200.00');
    }

    public function test_reports_can_be_filtered_by_current_date_and_branch(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $mainBranch = Branch::query()->where('code', 'MAIN')->firstOrFail();

        $salesInvoice = SalesInvoice::query()
            ->where('invoice_number', 'INV-DEMO-001')
            ->firstOrFail();

        $purchaseInvoice = PurchaseInvoice::query()
            ->where('invoice_number', 'PINV-DEMO-001')
            ->firstOrFail();

        app(SalesInvoiceService::class)->issueInvoice(
            invoice: $salesInvoice,
            stockService: app(InventoryStockService::class)
        );

        app(SalesInvoiceService::class)->recordPayment(
            invoice: $salesInvoice->refresh(),
            user: $admin,
            amount: 300,
            method: 'cash',
            referenceNumber: 'REPORT-FILTER-SALE-PAY'
        );

        app(PurchaseInvoiceService::class)->receiveInvoice(
            invoice: $purchaseInvoice,
            stockService: app(InventoryStockService::class)
        );

        app(PurchaseInvoiceService::class)->recordPayment(
            invoice: $purchaseInvoice->refresh(),
            user: $admin,
            amount: 100,
            method: 'cash',
            referenceNumber: 'REPORT-FILTER-PURCHASE-PAY'
        );

        $today = now()->toDateString();

        $response = $this->actingAs($admin)->get('/reports?' . http_build_query([
            'from_date' => $today,
            'to_date' => $today,
            'branch_id' => $mainBranch->id,
        ]));

        $response->assertOk();

        $response->assertSee('851.00');
        $response->assertSee('414.00');
        $response->assertSee('390.00');
        $response->assertSee('200.00');
    }

    public function test_reports_return_zero_when_filtered_to_empty_future_period(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();

        $futureFrom = now()->addYear()->toDateString();
        $futureTo = now()->addYear()->addDay()->toDateString();

        $response = $this->actingAs($admin)->get('/reports?' . http_build_query([
            'from_date' => $futureFrom,
            'to_date' => $futureTo,
        ]));

        $response->assertOk();

        $response->assertSee('0.00');
        $response->assertSee('0');
    }

    public function test_reports_show_inventory_valuation_totals(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();

        $response = $this->actingAs($admin)->get('/reports');

        $response->assertOk();

        $response->assertSee('تقرير المخزون');
        $response->assertSee('عدد المنتجات');
        $response->assertSee('عدد المتغيرات');
        $response->assertSee('الكمية الفعلية');
        $response->assertSee('المتاح للبيع');
        $response->assertSee('قيمة التكلفة');
        $response->assertSee('قيمة البيع');
        $response->assertSee('هامش محتمل');

        $response->assertSee('2,400.00');
        $response->assertSee('5,000.00');
        $response->assertSee('2,600.00');
    }

    public function test_inventory_report_respects_branch_filter(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $onlineBranch = Branch::query()->where('code', 'ONLINE')->firstOrFail();

        $response = $this->actingAs($admin)->get('/reports?' . http_build_query([
            'branch_id' => $onlineBranch->id,
        ]));

        $response->assertOk();

        $response->assertSee('تقرير المخزون');
        $response->assertSee('0.00');
        $response->assertSee('0');
    }
}
