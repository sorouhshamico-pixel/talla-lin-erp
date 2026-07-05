<?php

namespace Tests\Feature;

use App\Models\SalesInvoice;
use App\Models\User;
use App\Services\InventoryStockService;
use App\Services\SalesInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoicePaymentFormLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_create_page_displays_remaining_amount_as_max_payment_limit(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = SalesInvoice::query()->where('invoice_number', 'INV-DEMO-001')->firstOrFail();

        app(SalesInvoiceService::class)->issueInvoice($invoice, app(InventoryStockService::class));

        $response = $this->actingAs($admin)
            ->get('/sales-invoices/' . $invoice->id . '/payments/create');

        $response->assertOk();
        $response->assertSee('max="851.00"', false);
        $response->assertSee('لا يمكن أن تتجاوز الدفعة المبلغ المتبقي');
        $response->assertSee('851.00 ريال');
    }
}
