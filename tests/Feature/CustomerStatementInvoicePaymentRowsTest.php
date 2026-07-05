<?php

namespace Tests\Feature;

use App\Models\SalesInvoice;
use App\Models\User;
use App\Services\InventoryStockService;
use App\Services\SalesInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerStatementInvoicePaymentRowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_statement_page_displays_invoice_payment_rows_and_running_balance(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = SalesInvoice::query()->where('invoice_number', 'INV-DEMO-001')->firstOrFail();

        $invoiceService = app(SalesInvoiceService::class);

        $invoiceService->issueInvoice($invoice, app(InventoryStockService::class));

        $invoice->refresh();

        $invoiceService->recordPayment(
            invoice: $invoice,
            user: $admin,
            amount: 300,
            method: 'bank_transfer',
            referenceNumber: 'PAY-STMT-PAGE-001',
            notes: 'دفعة لاختبار عرض كشف حساب العميل.'
        );

        $response = $this->actingAs($admin)
            ->get(route('customers.statement', $invoice->customer_id));

        $response->assertOk();

        $response->assertSee('كشف حساب العميل');
        $response->assertSee('فاتورة بيع');
        $response->assertSee('فاتورة بيع رقم INV-DEMO-001');
        $response->assertSee('دفعة');
        $response->assertSee('دفعة على فاتورة رقم INV-DEMO-001');
        $response->assertSee('PAY-STMT-PAGE-001');

        $response->assertSee('851.00');
        $response->assertSee('300.00');
        $response->assertSee('551.00');

        $response->assertSeeInOrder([
            'فاتورة بيع رقم INV-DEMO-001',
            '851.00',
            'دفعة على فاتورة رقم INV-DEMO-001',
            '300.00',
            '551.00',
        ]);
    }
}
