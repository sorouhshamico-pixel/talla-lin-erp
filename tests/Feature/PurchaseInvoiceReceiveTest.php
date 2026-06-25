<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryStockService;
use App\Services\PurchaseInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PurchaseInvoiceReceiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_invoice_can_be_received_and_stock_is_increased(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN-WH')->firstOrFail();

        $supplier = Supplier::query()->create([
            'company_id' => $branch->company_id,
            'name' => 'مورد استلام',
            'phone' => '0551000000',
            'city' => 'الرياض',
            'is_active' => true,
        ]);

        $mediumVariant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-M')->firstOrFail();
        $largeVariant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-L')->firstOrFail();

        $invoice = app(PurchaseInvoiceService::class)->createDraftInvoice(
            supplier: $supplier,
            branch: $branch,
            warehouse: $warehouse,
            user: $admin,
            invoiceNumber: 'PINV-RECEIVE-001',
            items: [
                [
                    'product_variant_id' => $mediumVariant->id,
                    'quantity' => 5,
                    'unit_cost' => 120,
                    'discount_amount' => 0,
                    'tax_rate' => 15,
                ],
                [
                    'product_variant_id' => $largeVariant->id,
                    'quantity' => 3,
                    'unit_cost' => 120,
                    'discount_amount' => 0,
                    'tax_rate' => 15,
                ],
            ]
        );

        app(PurchaseInvoiceService::class)->receiveInvoice(
            invoice: $invoice,
            stockService: app(InventoryStockService::class)
        );

        $invoice->refresh();

        $this->assertEquals('received', $invoice->status);

        $mediumBalance = InventoryBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_variant_id', $mediumVariant->id)
            ->firstOrFail();

        $largeBalance = InventoryBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_variant_id', $largeVariant->id)
            ->firstOrFail();

        $this->assertEquals(17.0, (float) $mediumBalance->quantity_on_hand);
        $this->assertEquals(2.0, (float) $mediumBalance->quantity_reserved);
        $this->assertEquals(15.0, $mediumBalance->availableQuantity());

        $this->assertEquals(11.0, (float) $largeBalance->quantity_on_hand);
        $this->assertEquals(1.0, (float) $largeBalance->quantity_reserved);
        $this->assertEquals(10.0, $largeBalance->availableQuantity());

        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $mediumVariant->id,
            'type' => 'purchase',
            'direction' => 'in',
            'reference_number' => 'PINV-RECEIVE-001',
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $largeVariant->id,
            'type' => 'purchase',
            'direction' => 'in',
            'reference_number' => 'PINV-RECEIVE-001',
        ]);

        $this->assertEquals(2, InventoryMovement::query()->where('type', 'purchase')->count());
    }

    public function test_purchase_invoice_cannot_be_received_twice(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN-WH')->firstOrFail();
        $variant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-M')->firstOrFail();

        $supplier = Supplier::query()->create([
            'company_id' => $branch->company_id,
            'name' => 'مورد منع التكرار',
            'phone' => '0551000001',
            'city' => 'الرياض',
            'is_active' => true,
        ]);

        $invoice = app(PurchaseInvoiceService::class)->createDraftInvoice(
            supplier: $supplier,
            branch: $branch,
            warehouse: $warehouse,
            user: $admin,
            invoiceNumber: 'PINV-RECEIVE-TWICE',
            items: [
                [
                    'product_variant_id' => $variant->id,
                    'quantity' => 2,
                    'unit_cost' => 120,
                    'tax_rate' => 15,
                ],
            ]
        );

        app(PurchaseInvoiceService::class)->receiveInvoice($invoice, app(InventoryStockService::class));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('لا يمكن اعتماد فاتورة شراء غير مسودة.');

        app(PurchaseInvoiceService::class)->receiveInvoice($invoice->refresh(), app(InventoryStockService::class));
    }

    public function test_receiving_purchase_invoice_can_create_balance_for_new_variant(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN-WH')->firstOrFail();

        $baseVariant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-M')->firstOrFail();

        $newVariant = ProductVariant::query()->create([
            'product_id' => $baseVariant->product_id,
            'sku' => 'TL-ABAYA-001-BLK-XXL',
            'color' => 'أسود',
            'size' => 'XXL',
            'sale_price' => 250,
            'cost_price' => 120,
            'is_active' => true,
        ]);

        $supplier = Supplier::query()->create([
            'company_id' => $branch->company_id,
            'name' => 'مورد مقاس جديد',
            'phone' => '0551000002',
            'city' => 'الرياض',
            'is_active' => true,
        ]);

        $invoice = app(PurchaseInvoiceService::class)->createDraftInvoice(
            supplier: $supplier,
            branch: $branch,
            warehouse: $warehouse,
            user: $admin,
            invoiceNumber: 'PINV-NEW-VARIANT',
            items: [
                [
                    'product_variant_id' => $newVariant->id,
                    'quantity' => 4,
                    'unit_cost' => 120,
                    'tax_rate' => 15,
                ],
            ]
        );

        app(PurchaseInvoiceService::class)->receiveInvoice($invoice, app(InventoryStockService::class));

        $balance = InventoryBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_variant_id', $newVariant->id)
            ->firstOrFail();

        $this->assertEquals(4.0, (float) $balance->quantity_on_hand);
        $this->assertEquals(0.0, (float) $balance->quantity_reserved);
        $this->assertEquals(4.0, $balance->availableQuantity());

        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $newVariant->id,
            'type' => 'purchase',
            'direction' => 'in',
            'reference_number' => 'PINV-NEW-VARIANT',
        ]);
    }
}
