<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoiceCreateFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_create_sales_invoice_page(): void
    {
        $this->get('/sales-invoices/create')->assertRedirect('/login');
    }

    public function test_owner_can_view_create_sales_invoice_page(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();

        $response = $this->actingAs($admin)->get('/sales-invoices/create');

        $response->assertOk();
        $response->assertSee('فاتورة بيع جديدة');
        $response->assertSee('عميلة تجربة');
        $response->assertSee('TL-ABAYA-001-BLK-M');
    }

    public function test_owner_can_create_sales_invoice_from_form(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $customer = Customer::query()->where('phone', '0500000000')->firstOrFail();
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();
        $variant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-M')->firstOrFail();

        $response = $this->actingAs($admin)->post('/sales-invoices', [
            'customer_id' => $customer->id,
            'branch_id' => $branch->id,
            'invoice_number' => 'INV-FORM-001',
            'notes' => 'فاتورة من النموذج.',
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'unit_price' => 250,
            'discount_amount' => 0,
            'tax_rate' => 15,
        ]);

        $invoice = SalesInvoice::query()
            ->where('invoice_number', 'INV-FORM-001')
            ->firstOrFail();

        $response->assertRedirect('/sales-invoices/' . $invoice->id);

        $this->assertEquals('draft', $invoice->status);
        $this->assertEquals(500.0, (float) $invoice->subtotal);
        $this->assertEquals(75.0, (float) $invoice->tax_total);
        $this->assertEquals(575.0, (float) $invoice->grand_total);
        $this->assertEquals(575.0, (float) $invoice->remaining_amount);
        $this->assertEquals(1, $invoice->items()->count());

        $this->assertDatabaseHas('sales_invoice_items', [
            'sales_invoice_id' => $invoice->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'unit_price' => 250.00,
            'line_total' => 575.00,
        ]);
    }

    public function test_create_sales_invoice_form_rejects_zero_quantity(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $customer = Customer::query()->where('phone', '0500000000')->firstOrFail();
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();
        $variant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-M')->firstOrFail();

        $response = $this->actingAs($admin)
            ->from('/sales-invoices/create')
            ->post('/sales-invoices', [
                'customer_id' => $customer->id,
                'branch_id' => $branch->id,
                'invoice_number' => 'INV-FORM-ZERO',
                'product_variant_id' => $variant->id,
                'quantity' => 0,
                'unit_price' => 250,
                'discount_amount' => 0,
                'tax_rate' => 15,
            ]);

        $response->assertRedirect('/sales-invoices/create');
        $response->assertSessionHasErrors('quantity');
    }
}
