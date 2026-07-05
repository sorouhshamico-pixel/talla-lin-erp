<?php

namespace Tests\Feature;

use App\Models\SalesInvoice;
use App\Models\User;
use App\Services\InventoryStockService;
use App\Services\SalesInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoicePaymentNotesDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_invoice_show_page_displays_payment_notes_in_payment_history(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = SalesInvoice::query()->where('invoice_number', 'INV-DEMO-001')->firstOrFail();

        $invoiceService = app(SalesInvoiceService::class);

        $invoiceService->issueInvoice($invoice, app(InventoryStockService::class));

        $invoiceService->recordPayment(
            invoice: $invoice->refresh(),
            user: $admin,
            amount: 300,
            method: 'bank_transfer',
            referenceNumber: 'PAY-NOTES-001',
            notes: 'دفعة تحويل بنكي من العميل.'
        );

        $response = $this->actingAs($admin)
            ->get('/sales-invoices/' . $invoice->id);

        $response->assertOk();
        $response->assertSee('سجل الدفعات');
        $response->assertSee('ملاحظات');
        $response->assertSee('PAY-NOTES-001');
        $response->assertSee('تحويل بنكي');
        $response->assertSee('دفعة تحويل بنكي من العميل.');
    }
}
