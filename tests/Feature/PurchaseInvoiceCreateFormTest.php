<?php

namespace Tests\Feature;

use App\Models\InventoryBalance;
use App\Models\ProductVariant;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseInvoiceCreateFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_create_purchase_invoice_page(): void
    {
        $this->get('/purchase-invoices/create')->assertRedirect('/login');
    }

    public function test_owner_can_view_create_purchase_invoice_page(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();

        $response = $this->actingAs($admin)->get('/purchase-invoices/create');

        $response->assertOk();
        $response->assertSee('فاتورة شراء جديدة');
        $response->assertSee('مورد تجربة');
        $response->assertSee('المستودع الرئيسي');
        $response->assertSee('TL-ABAYA-001-BLK-M');
    }

    public function test_owner_can_create_purchase_invoice_from_form(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $supplier = Supplier::query()->where('phone', '0559000000')->firstOrFail();
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN-WH')->firstOrFail();
        $variant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-M')->firstOrFail();

        $beforeBalance = InventoryBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_variant_id', $variant->id)
            ->firstOrFail();

        $response = $this->actingAs($admin)->post('/purchase-invoices', [
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'warehouse_id' => $warehouse->id,
            'invoice_number' => 'PINV-FORM-001',
            'notes' => 'فاتورة شراء من النموذج.',
            'product_variant_id' => $variant->id,
            'quantity' => 4,
            'unit_cost' => 120,
            'discount_amount' => 0,
            'tax_rate' => 15,
        ]);

        $invoice = PurchaseInvoice::query()
            ->where('invoice_number', 'PINV-FORM-001')
            ->firstOrFail();

        $response->assertRedirect('/purchase-invoices/' . $invoice->id);

        $this->assertEquals('draft', $invoice->status);
        $this->assertEquals('unpaid', $invoice->payment_status);
        $this->assertEquals(480.0, (float) $invoice->subtotal);
        $this->assertEquals(72.0, (float) $invoice->tax_total);
        $this->assertEquals(552.0, (float) $invoice->grand_total);
        $this->assertEquals(552.0, (float) $invoice->remaining_amount);
        $this->assertEquals(1, $invoice->items()->count());

        $afterBalance = InventoryBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_variant_id', $variant->id)
            ->firstOrFail();

        $this->assertEquals(
            (float) $beforeBalance->quantity_on_hand,
            (float) $afterBalance->quantity_on_hand
        );

        $this->assertDatabaseHas('purchase_invoice_items', [
            'purchase_invoice_id' => $invoice->id,
            'product_variant_id' => $variant->id,
            'quantity' => 4,
            'unit_cost' => 120.00,
            'line_total' => 552.00,
        ]);
    }

    public function test_create_purchase_invoice_form_rejects_zero_quantity(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $supplier = Supplier::query()->where('phone', '0559000000')->firstOrFail();
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN-WH')->firstOrFail();
        $variant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-M')->firstOrFail();

        $response = $this->actingAs($admin)
            ->from('/purchase-invoices/create')
            ->post('/purchase-invoices', [
                'supplier_id' => $supplier->id,
                'branch_id' => $branch->id,
                'warehouse_id' => $warehouse->id,
                'invoice_number' => 'PINV-FORM-ZERO',
                'product_variant_id' => $variant->id,
                'quantity' => 0,
                'unit_cost' => 120,
                'discount_amount' => 0,
                'tax_rate' => 15,
            ]);

        $response->assertRedirect('/purchase-invoices/create');
        $response->assertSessionHasErrors('quantity');
    }

    public function test_create_purchase_invoice_form_rejects_warehouse_from_different_branch(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $supplier = Supplier::query()->where('phone', '0559000000')->firstOrFail();
        $onlineBranch = Branch::query()->where('code', 'ONLINE')->firstOrFail();
        $mainWarehouse = Warehouse::query()->where('code', 'MAIN-WH')->firstOrFail();
        $variant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-M')->firstOrFail();

        $response = $this->actingAs($admin)
            ->from('/purchase-invoices/create')
            ->post('/purchase-invoices', [
                'supplier_id' => $supplier->id,
                'branch_id' => $onlineBranch->id,
                'warehouse_id' => $mainWarehouse->id,
                'invoice_number' => 'PINV-BRANCH-WH-ERROR',
                'product_variant_id' => $variant->id,
                'quantity' => 1,
                'unit_cost' => 120,
                'discount_amount' => 0,
                'tax_rate' => 15,
            ]);

        $response->assertRedirect('/purchase-invoices/create');
        $response->assertSessionHasErrors('purchase_invoice');
    }
}
