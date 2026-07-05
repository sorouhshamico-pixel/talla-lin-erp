<?php

namespace Tests\Feature;

use App\Models\SalesInvoice;
use App\Models\User;
use App\Services\InventoryStockService;
use App\Services\SalesInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoicePaymentErrorDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_invoice_show_page_displays_payment_error_for_draft_invoice_payment_page_access(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = SalesInvoice::query()->where('invoice_number', 'INV-DEMO-001')->firstOrFail();

        $response = $this->actingAs($admin)
            ->followingRedirects()
            ->get('/sales-invoices/' . $invoice->id . '/payments/create');

        $response->assertOk();
        $response->assertSee('لا يمكن تسجيل دفعة على فاتورة غير معتمدة.');
    }

    public function test_sales_invoice_show_page_displays_payment_error_for_fully_paid_invoice_payment_page_access(): void
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
            amount: (float) $invoice->remaining_amount,
            method: 'cash',
            referenceNumber: 'PAY-FULL-ERROR-DISPLAY',
            notes: 'دفعة كاملة لاختبار عرض خطأ منع الدفع بعد السداد الكامل.'
        );

        $response = $this->actingAs($admin)
            ->followingRedirects()
            ->get('/sales-invoices/' . $invoice->id . '/payments/create');

        $response->assertOk();
        $response->assertSee('لا يمكن تسجيل دفعة على فاتورة مدفوعة بالكامل.');
    }
}
