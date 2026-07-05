<?php

namespace Tests\Feature;

use App\Models\SalesInvoice;
use App\Models\User;
use App\Services\InventoryStockService;
use App\Services\SalesInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoicePaymentAccessGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_cannot_open_payment_page_for_draft_invoice(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = SalesInvoice::query()->where('invoice_number', 'INV-DEMO-001')->firstOrFail();

        $response = $this->actingAs($admin)
            ->get('/sales-invoices/' . $invoice->id . '/payments/create');

        $response->assertRedirect('/sales-invoices/' . $invoice->id);
        $response->assertSessionHasErrors('payment');
    }

    public function test_owner_cannot_open_payment_page_for_fully_paid_invoice(): void
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
            referenceNumber: 'PAY-FULL-GUARD',
            notes: 'دفعة كاملة لاختبار منع فتح صفحة الدفع بعد السداد الكامل.'
        );

        $invoice->refresh();

        $this->assertEquals('paid', $invoice->payment_status);
        $this->assertEquals(0.0, (float) $invoice->remaining_amount);

        $response = $this->actingAs($admin)
            ->get('/sales-invoices/' . $invoice->id . '/payments/create');

        $response->assertRedirect('/sales-invoices/' . $invoice->id);
        $response->assertSessionHasErrors('payment');
    }
}
