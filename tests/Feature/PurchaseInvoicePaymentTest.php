<?php

namespace Tests\Feature;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoicePayment;
use App\Models\User;
use App\Services\InventoryStockService;
use App\Services\PurchaseInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseInvoicePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_purchase_payment_page(): void
    {
        $this->seed();

        $invoice = PurchaseInvoice::query()->where('invoice_number', 'PINV-DEMO-001')->firstOrFail();

        $this->get('/purchase-invoices/' . $invoice->id . '/payments/create')
            ->assertRedirect('/login');
    }

    public function test_owner_can_view_purchase_payment_page_for_received_invoice(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = PurchaseInvoice::query()->where('invoice_number', 'PINV-DEMO-001')->firstOrFail();

        app(PurchaseInvoiceService::class)->receiveInvoice($invoice, app(InventoryStockService::class));

        $response = $this->actingAs($admin)->get('/purchase-invoices/' . $invoice->id . '/payments/create');

        $response->assertOk();
        $response->assertSee('تسجيل دفعة للمورد');
        $response->assertSee('PINV-DEMO-001');
        $response->assertSee('414.00');
    }

    public function test_owner_can_record_partial_purchase_payment(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = PurchaseInvoice::query()->where('invoice_number', 'PINV-DEMO-001')->firstOrFail();

        app(PurchaseInvoiceService::class)->receiveInvoice($invoice, app(InventoryStockService::class));

        $response = $this->actingAs($admin)->post('/purchase-invoices/' . $invoice->id . '/payments', [
            'amount' => 100,
            'method' => 'cash',
            'reference_number' => 'PPAY-001',
            'notes' => 'دفعة جزئية للمورد.',
        ]);

        $response->assertRedirect('/purchase-invoices/' . $invoice->id);

        $invoice->refresh();

        $this->assertEquals(100.0, (float) $invoice->paid_amount);
        $this->assertEquals(314.0, (float) $invoice->remaining_amount);
        $this->assertEquals('partial', $invoice->payment_status);

        $this->assertDatabaseHas('purchase_invoice_payments', [
            'purchase_invoice_id' => $invoice->id,
            'amount' => 100.00,
            'method' => 'cash',
            'reference_number' => 'PPAY-001',
        ]);
    }

    public function test_owner_can_record_full_purchase_payment_after_partial_payment(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = PurchaseInvoice::query()->where('invoice_number', 'PINV-DEMO-001')->firstOrFail();

        app(PurchaseInvoiceService::class)->receiveInvoice($invoice, app(InventoryStockService::class));

        $this->actingAs($admin)->post('/purchase-invoices/' . $invoice->id . '/payments', [
            'amount' => 100,
            'method' => 'cash',
            'reference_number' => 'PPAY-001',
        ]);

        $response = $this->actingAs($admin)->post('/purchase-invoices/' . $invoice->id . '/payments', [
            'amount' => 314,
            'method' => 'bank_transfer',
            'reference_number' => 'PPAY-002',
        ]);

        $response->assertRedirect('/purchase-invoices/' . $invoice->id);

        $invoice->refresh();

        $this->assertEquals(414.0, (float) $invoice->paid_amount);
        $this->assertEquals(0.0, (float) $invoice->remaining_amount);
        $this->assertEquals('paid', $invoice->payment_status);
        $this->assertEquals(2, PurchaseInvoicePayment::query()->where('purchase_invoice_id', $invoice->id)->count());
    }

    public function test_purchase_payment_greater_than_remaining_amount_is_rejected(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = PurchaseInvoice::query()->where('invoice_number', 'PINV-DEMO-001')->firstOrFail();

        app(PurchaseInvoiceService::class)->receiveInvoice($invoice, app(InventoryStockService::class));

        $response = $this->actingAs($admin)
            ->from('/purchase-invoices/' . $invoice->id . '/payments/create')
            ->post('/purchase-invoices/' . $invoice->id . '/payments', [
                'amount' => 9999,
                'method' => 'cash',
                'reference_number' => 'PPAY-INVALID',
            ]);

        $response->assertRedirect('/purchase-invoices/' . $invoice->id . '/payments/create');
        $response->assertSessionHasErrors('payment');

        $invoice->refresh();

        $this->assertEquals(0.0, (float) $invoice->paid_amount);
        $this->assertEquals(414.0, (float) $invoice->remaining_amount);
        $this->assertEquals('unpaid', $invoice->payment_status);
    }

    public function test_purchase_payment_on_draft_invoice_is_rejected(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = PurchaseInvoice::query()->where('invoice_number', 'PINV-DEMO-001')->firstOrFail();

        $response = $this->actingAs($admin)
            ->from('/purchase-invoices/' . $invoice->id . '/payments/create')
            ->post('/purchase-invoices/' . $invoice->id . '/payments', [
                'amount' => 100,
                'method' => 'cash',
                'reference_number' => 'PPAY-DRAFT',
            ]);

        $response->assertRedirect('/purchase-invoices/' . $invoice->id . '/payments/create');
        $response->assertSessionHasErrors('payment');

        $this->assertEquals(0, PurchaseInvoicePayment::query()->count());
    }
}
