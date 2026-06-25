<?php

namespace Tests\Feature;

use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoiceIssueTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_issue_draft_invoice_and_stock_is_deducted(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = SalesInvoice::query()->where('invoice_number', 'INV-DEMO-001')->firstOrFail();

        $mediumVariant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-M')->firstOrFail();
        $largeVariant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-L')->firstOrFail();

        $response = $this->actingAs($admin)->post('/sales-invoices/' . $invoice->id . '/issue');

        $response->assertRedirect('/sales-invoices/' . $invoice->id);

        $invoice->refresh();

        $this->assertEquals('issued', $invoice->status);

        $mediumBalance = InventoryBalance::query()
            ->where('product_variant_id', $mediumVariant->id)
            ->firstOrFail();

        $largeBalance = InventoryBalance::query()
            ->where('product_variant_id', $largeVariant->id)
            ->firstOrFail();

        $this->assertEquals(10.0, (float) $mediumBalance->quantity_on_hand);
        $this->assertEquals(2.0, (float) $mediumBalance->quantity_reserved);
        $this->assertEquals(8.0, $mediumBalance->availableQuantity());

        $this->assertEquals(7.0, (float) $largeBalance->quantity_on_hand);
        $this->assertEquals(1.0, (float) $largeBalance->quantity_reserved);
        $this->assertEquals(6.0, $largeBalance->availableQuantity());

        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $mediumVariant->id,
            'type' => 'sale',
            'direction' => 'out',
            'reference_number' => 'INV-DEMO-001',
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $largeVariant->id,
            'type' => 'sale',
            'direction' => 'out',
            'reference_number' => 'INV-DEMO-001',
        ]);

        $this->assertEquals(2, InventoryMovement::query()->where('type', 'sale')->count());
    }

    public function test_invoice_cannot_be_issued_twice(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = SalesInvoice::query()->where('invoice_number', 'INV-DEMO-001')->firstOrFail();

        $this->actingAs($admin)->post('/sales-invoices/' . $invoice->id . '/issue');

        $response = $this->actingAs($admin)
            ->from('/sales-invoices/' . $invoice->id)
            ->post('/sales-invoices/' . $invoice->id . '/issue');

        $response->assertRedirect('/sales-invoices/' . $invoice->id);
        $response->assertSessionHasErrors('issue');

        $this->assertEquals(2, InventoryMovement::query()->where('type', 'sale')->count());
    }

    public function test_invoice_issue_fails_when_stock_is_not_enough(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = SalesInvoice::query()->where('invoice_number', 'INV-DEMO-001')->firstOrFail();

        foreach ($invoice->items as $item) {
            $item->forceFill([
                'quantity' => 999,
            ])->save();
        }

        $response = $this->actingAs($admin)
            ->from('/sales-invoices/' . $invoice->id)
            ->post('/sales-invoices/' . $invoice->id . '/issue');

        $response->assertRedirect('/sales-invoices/' . $invoice->id);
        $response->assertSessionHasErrors('issue');

        $invoice->refresh();

        $this->assertEquals('draft', $invoice->status);
        $this->assertEquals(0, InventoryMovement::query()->where('type', 'sale')->count());
    }
}
