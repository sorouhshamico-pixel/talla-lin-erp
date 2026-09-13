<?php

namespace Tests\Feature;

use App\Models\PurchaseInvoice;
use App\Models\User;
use App\Services\InventoryStockService;
use App\Services\PartyStatementService;
use App\Services\PurchaseInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierStatementPurchaseInvoiceSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_statement_uses_purchase_invoices_and_invoice_payments(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = PurchaseInvoice::query()->where('invoice_number', 'PINV-DEMO-001')->firstOrFail();

        $invoiceService = app(PurchaseInvoiceService::class);

        $invoiceService->receiveInvoice($invoice, app(InventoryStockService::class));

        $invoice->refresh();

        $grandTotal = (float) $invoice->grand_total;

        $invoiceService->recordPayment(
            invoice: $invoice,
            user: $admin,
            amount: 150,
            method: 'bank_transfer',
            referenceNumber: 'PAY-SUP-STMT-001',
            notes: 'دفعة لاختبار كشف حساب المورد.'
        );

        $statement = app(PartyStatementService::class)->supplierStatement($invoice->supplier_id);

        $this->assertTrue($statement['has_data_source']);
        $this->assertSame('purchase_invoices', $statement['source_table']);
        $this->assertSame(2, $statement['count']);
        $this->assertEquals($grandTotal, (float) $statement['total_debit']);
        $this->assertEquals(150.0, (float) $statement['total_credit']);
        $this->assertEquals(round($grandTotal - 150, 2), (float) $statement['balance']);

        $rows = $statement['rows']->values();

        $this->assertSame('فاتورة شراء', $rows[0]['type']);
        $this->assertStringContainsString('PINV-DEMO-001', $rows[0]['description']);
        $this->assertEquals($grandTotal, (float) $rows[0]['debit']);
        $this->assertEquals(0.0, (float) $rows[0]['credit']);
        $this->assertEquals($grandTotal, (float) $rows[0]['balance']);

        $this->assertSame('دفعة', $rows[1]['type']);
        $this->assertStringContainsString('PAY-SUP-STMT-001', $rows[1]['description']);
        $this->assertEquals(0.0, (float) $rows[1]['debit']);
        $this->assertEquals(150.0, (float) $rows[1]['credit']);
        $this->assertEquals(round($grandTotal - 150, 2), (float) $rows[1]['balance']);
    }

    public function test_supplier_statement_date_filters_apply_to_purchase_invoice_source(): void
    {
        $this->seed();

        $invoice = PurchaseInvoice::query()->where('invoice_number', 'PINV-DEMO-001')->firstOrFail();

        $statement = app(PartyStatementService::class)->supplierStatement(
            $invoice->supplier_id,
            now()->addDay()->toDateString(),
            now()->addDays(2)->toDateString()
        );

        $this->assertSame('purchase_invoices', $statement['source_table']);
        $this->assertSame(0, $statement['count']);
        $this->assertEquals(0.0, (float) $statement['total_debit']);
        $this->assertEquals(0.0, (float) $statement['total_credit']);
        $this->assertEquals(0.0, (float) $statement['balance']);
    }
}
