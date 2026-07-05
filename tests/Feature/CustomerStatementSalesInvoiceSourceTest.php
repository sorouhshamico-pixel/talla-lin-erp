<?php

namespace Tests\Feature;

use App\Models\SalesInvoice;
use App\Models\User;
use App\Services\InventoryStockService;
use App\Services\PartyStatementService;
use App\Services\SalesInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerStatementSalesInvoiceSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_statement_uses_sales_invoices_and_invoice_payments(): void
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
            referenceNumber: 'PAY-STMT-001',
            notes: 'دفعة لاختبار كشف حساب العميل.'
        );

        $statement = app(PartyStatementService::class)->customerStatement($invoice->customer_id);

        $this->assertTrue($statement['has_data_source']);
        $this->assertSame('sales_invoices', $statement['source_table']);
        $this->assertSame(2, $statement['count']);
        $this->assertEquals(851.0, (float) $statement['total_debit']);
        $this->assertEquals(300.0, (float) $statement['total_credit']);
        $this->assertEquals(551.0, (float) $statement['balance']);

        $rows = $statement['rows']->values();

        $this->assertSame('فاتورة بيع', $rows[0]['type']);
        $this->assertStringContainsString('INV-DEMO-001', $rows[0]['description']);
        $this->assertEquals(851.0, (float) $rows[0]['debit']);
        $this->assertEquals(0.0, (float) $rows[0]['credit']);
        $this->assertEquals(851.0, (float) $rows[0]['balance']);

        $this->assertSame('دفعة', $rows[1]['type']);
        $this->assertStringContainsString('PAY-STMT-001', $rows[1]['description']);
        $this->assertEquals(0.0, (float) $rows[1]['debit']);
        $this->assertEquals(300.0, (float) $rows[1]['credit']);
        $this->assertEquals(551.0, (float) $rows[1]['balance']);
    }

    public function test_customer_statement_date_filters_apply_to_sales_invoice_source(): void
    {
        $this->seed();

        $invoice = SalesInvoice::query()->where('invoice_number', 'INV-DEMO-001')->firstOrFail();

        $statement = app(PartyStatementService::class)->customerStatement(
            $invoice->customer_id,
            now()->addDay()->toDateString(),
            now()->addDays(2)->toDateString()
        );

        $this->assertSame('sales_invoices', $statement['source_table']);
        $this->assertSame(0, $statement['count']);
        $this->assertEquals(0.0, (float) $statement['total_debit']);
        $this->assertEquals(0.0, (float) $statement['total_credit']);
        $this->assertEquals(0.0, (float) $statement['balance']);
    }
}
