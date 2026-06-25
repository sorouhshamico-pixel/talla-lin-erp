<?php

namespace Tests\Feature;

use App\Models\SalesInvoice;
use App\Models\SalesInvoicePayment;
use App\Models\User;
use App\Services\InventoryStockService;
use App\Services\SalesInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoicePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_payment_page(): void
    {
        $this->seed();

        $invoice = SalesInvoice::query()->where('invoice_number', 'INV-DEMO-001')->firstOrFail();

        $this->get('/sales-invoices/' . $invoice->id . '/payments/create')
            ->assertRedirect('/login');
    }

    public function test_owner_can_view_payment_page_for_issued_invoice(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = SalesInvoice::query()->where('invoice_number', 'INV-DEMO-001')->firstOrFail();

        app(SalesInvoiceService::class)->issueInvoice($invoice, app(InventoryStockService::class));

        $response = $this->actingAs($admin)->get('/sales-invoices/' . $invoice->id . '/payments/create');

        $response->assertOk();
        $response->assertSee('تسجيل دفعة');
        $response->assertSee('INV-DEMO-001');
        $response->assertSee('851.00');
    }

    public function test_owner_can_record_partial_payment(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = SalesInvoice::query()->where('invoice_number', 'INV-DEMO-001')->firstOrFail();

        app(SalesInvoiceService::class)->issueInvoice($invoice, app(InventoryStockService::class));

        $response = $this->actingAs($admin)->post('/sales-invoices/' . $invoice->id . '/payments', [
            'amount' => 300,
            'method' => 'cash',
            'reference_number' => 'PAY-001',
            'notes' => 'دفعة جزئية.',
        ]);

        $response->assertRedirect('/sales-invoices/' . $invoice->id);

        $invoice->refresh();

        $this->assertEquals(300.0, (float) $invoice->paid_amount);
        $this->assertEquals(551.0, (float) $invoice->remaining_amount);
        $this->assertEquals('partial', $invoice->payment_status);

        $this->assertDatabaseHas('sales_invoice_payments', [
            'sales_invoice_id' => $invoice->id,
            'amount' => 300.00,
            'method' => 'cash',
            'reference_number' => 'PAY-001',
        ]);
    }

    public function test_owner_can_record_full_payment_after_partial_payment(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = SalesInvoice::query()->where('invoice_number', 'INV-DEMO-001')->firstOrFail();

        app(SalesInvoiceService::class)->issueInvoice($invoice, app(InventoryStockService::class));

        $this->actingAs($admin)->post('/sales-invoices/' . $invoice->id . '/payments', [
            'amount' => 300,
            'method' => 'cash',
            'reference_number' => 'PAY-001',
        ]);

        $response = $this->actingAs($admin)->post('/sales-invoices/' . $invoice->id . '/payments', [
            'amount' => 551,
            'method' => 'card',
            'reference_number' => 'PAY-002',
        ]);

        $response->assertRedirect('/sales-invoices/' . $invoice->id);

        $invoice->refresh();

        $this->assertEquals(851.0, (float) $invoice->paid_amount);
        $this->assertEquals(0.0, (float) $invoice->remaining_amount);
        $this->assertEquals('paid', $invoice->payment_status);
        $this->assertEquals(2, SalesInvoicePayment::query()->where('sales_invoice_id', $invoice->id)->count());
    }

    public function test_payment_greater_than_remaining_amount_is_rejected(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = SalesInvoice::query()->where('invoice_number', 'INV-DEMO-001')->firstOrFail();

        app(SalesInvoiceService::class)->issueInvoice($invoice, app(InventoryStockService::class));

        $response = $this->actingAs($admin)
            ->from('/sales-invoices/' . $invoice->id . '/payments/create')
            ->post('/sales-invoices/' . $invoice->id . '/payments', [
                'amount' => 9999,
                'method' => 'cash',
                'reference_number' => 'PAY-INVALID',
            ]);

        $response->assertRedirect('/sales-invoices/' . $invoice->id . '/payments/create');
        $response->assertSessionHasErrors('payment');

        $invoice->refresh();

        $this->assertEquals(0.0, (float) $invoice->paid_amount);
        $this->assertEquals(851.0, (float) $invoice->remaining_amount);
        $this->assertEquals('unpaid', $invoice->payment_status);
    }

    public function test_payment_on_draft_invoice_is_rejected(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = SalesInvoice::query()->where('invoice_number', 'INV-DEMO-001')->firstOrFail();

        $response = $this->actingAs($admin)
            ->from('/sales-invoices/' . $invoice->id . '/payments/create')
            ->post('/sales-invoices/' . $invoice->id . '/payments', [
                'amount' => 100,
                'method' => 'cash',
                'reference_number' => 'PAY-DRAFT',
            ]);

        $response->assertRedirect('/sales-invoices/' . $invoice->id . '/payments/create');
        $response->assertSessionHasErrors('payment');

        $this->assertEquals(0, SalesInvoicePayment::query()->count());
    }
}
