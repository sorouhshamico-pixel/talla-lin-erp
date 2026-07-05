<?php

namespace Tests\Feature;

use App\Models\SalesInvoice;
use App\Models\User;
use App\Services\InventoryStockService;
use App\Services\SalesInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SalesInvoicePaymentHistoryOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_invoice_show_page_displays_latest_payment_first(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = SalesInvoice::query()->where('invoice_number', 'INV-DEMO-001')->firstOrFail();

        $invoiceService = app(SalesInvoiceService::class);

        $invoiceService->issueInvoice($invoice, app(InventoryStockService::class));

        Carbon::setTestNow(Carbon::parse('2026-07-05 09:00:00'));

        $invoiceService->recordPayment(
            invoice: $invoice->refresh(),
            user: $admin,
            amount: 100,
            method: 'cash',
            referenceNumber: 'PAY-ORDER-OLD',
            notes: 'دفعة أقدم لاختبار ترتيب سجل الدفعات.'
        );

        Carbon::setTestNow(Carbon::parse('2026-07-06 09:00:00'));

        $invoiceService->recordPayment(
            invoice: $invoice->refresh(),
            user: $admin,
            amount: 200,
            method: 'bank_transfer',
            referenceNumber: 'PAY-ORDER-NEW',
            notes: 'دفعة أحدث يجب أن تظهر أولًا.'
        );

        Carbon::setTestNow();

        $response = $this->actingAs($admin)
            ->get('/sales-invoices/' . $invoice->id);

        $response->assertOk();
        $response->assertSee('سجل الدفعات');
        $response->assertSeeInOrder([
            'PAY-ORDER-NEW',
            'PAY-ORDER-OLD',
        ]);
        $response->assertSeeInOrder([
            '2026-07-06 09:00',
            '2026-07-05 09:00',
        ]);
    }
}
