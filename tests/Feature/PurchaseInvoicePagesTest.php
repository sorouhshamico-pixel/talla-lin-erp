<?php

namespace Tests\Feature;

use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\PurchaseInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseInvoicePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_suppliers_or_purchase_invoices_pages(): void
    {
        $this->get('/suppliers')->assertRedirect('/login');
        $this->get('/purchase-invoices')->assertRedirect('/login');
    }

    public function test_owner_can_view_suppliers_page(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();

        $response = $this->actingAs($admin)->get('/suppliers');

        $response->assertOk();
        $response->assertSee('الموردون');
        $response->assertSee('مورد تجربة');
        $response->assertSee('0559000000');
    }

    public function test_owner_can_view_purchase_invoices_page(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();

        $response = $this->actingAs($admin)->get('/purchase-invoices');

        $response->assertOk();
        $response->assertSee('فواتير الشراء');
        $response->assertSee('PINV-DEMO-001');
        $response->assertSee('مورد تجربة');
        $response->assertSee('414.00');
    }

    public function test_owner_can_view_purchase_invoice_details_page(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = PurchaseInvoice::query()->where('invoice_number', 'PINV-DEMO-001')->firstOrFail();

        $response = $this->actingAs($admin)->get('/purchase-invoices/' . $invoice->id);

        $response->assertOk();
        $response->assertSee('تفاصيل فاتورة الشراء');
        $response->assertSee('PINV-DEMO-001');
        $response->assertSee('مورد تجربة');
        $response->assertSee('TL-ABAYA-001-BLK-M');
        $response->assertSee('414.00');
        $response->assertSee('استلام الفاتورة');
    }

    public function test_owner_can_receive_purchase_invoice_from_page(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = PurchaseInvoice::query()->where('invoice_number', 'PINV-DEMO-001')->firstOrFail();

        $mediumVariant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-M')->firstOrFail();
        $largeVariant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-L')->firstOrFail();

        $response = $this->actingAs($admin)->post('/purchase-invoices/' . $invoice->id . '/receive');

        $response->assertRedirect('/purchase-invoices/' . $invoice->id);

        $invoice->refresh();

        $this->assertEquals('received', $invoice->status);

        $mediumBalance = InventoryBalance::query()
            ->where('product_variant_id', $mediumVariant->id)
            ->firstOrFail();

        $largeBalance = InventoryBalance::query()
            ->where('product_variant_id', $largeVariant->id)
            ->firstOrFail();

        $this->assertEquals(14.0, (float) $mediumBalance->quantity_on_hand);
        $this->assertEquals(9.0, (float) $largeBalance->quantity_on_hand);

        $this->assertEquals(2, InventoryMovement::query()
            ->where('type', 'purchase')
            ->where('reference_number', 'PINV-DEMO-001')
            ->count());
    }
}
