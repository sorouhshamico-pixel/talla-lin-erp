<?php

namespace Tests\Feature;

use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Services\InventoryStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class InventoryStockServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_service_can_add_quantity_to_existing_balance(): void
    {
        $this->seed();

        $warehouse = Warehouse::query()->where('code', 'MAIN-WH')->firstOrFail();
        $variant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-M')->firstOrFail();

        $service = new InventoryStockService();

        $movement = $service->applyMovement(
            warehouse: $warehouse,
            variant: $variant,
            type: 'purchase',
            direction: 'in',
            quantity: 5,
            unitCost: 120,
            referenceType: 'manual',
            referenceNumber: 'PURCHASE-TEST-001',
            notes: 'اختبار إضافة كمية.'
        );

        $balance = InventoryBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_variant_id', $variant->id)
            ->firstOrFail();

        $this->assertEquals(17.0, (float) $balance->quantity_on_hand);
        $this->assertEquals(2.0, (float) $balance->quantity_reserved);
        $this->assertEquals(15.0, $balance->availableQuantity());

        $this->assertEquals('purchase', $movement->type);
        $this->assertEquals('in', $movement->direction);
        $this->assertEquals(5.0, (float) $movement->quantity);

        $this->assertDatabaseHas('inventory_movements', [
            'reference_number' => 'PURCHASE-TEST-001',
            'type' => 'purchase',
            'direction' => 'in',
        ]);
    }

    public function test_stock_service_can_remove_quantity_from_available_stock(): void
    {
        $this->seed();

        $warehouse = Warehouse::query()->where('code', 'MAIN-WH')->firstOrFail();
        $variant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-M')->firstOrFail();

        $service = new InventoryStockService();

        $movement = $service->applyMovement(
            warehouse: $warehouse,
            variant: $variant,
            type: 'damage',
            direction: 'out',
            quantity: 3,
            unitCost: 120,
            referenceType: 'manual',
            referenceNumber: 'DAMAGE-TEST-001',
            notes: 'اختبار إخراج تالف.'
        );

        $balance = InventoryBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_variant_id', $variant->id)
            ->firstOrFail();

        $this->assertEquals(9.0, (float) $balance->quantity_on_hand);
        $this->assertEquals(2.0, (float) $balance->quantity_reserved);
        $this->assertEquals(7.0, $balance->availableQuantity());

        $this->assertEquals('damage', $movement->type);
        $this->assertEquals('out', $movement->direction);
        $this->assertEquals(3.0, (float) $movement->quantity);
    }

    public function test_stock_service_prevents_removing_more_than_available_quantity(): void
    {
        $this->seed();

        $warehouse = Warehouse::query()->where('code', 'MAIN-WH')->firstOrFail();
        $variant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-M')->firstOrFail();

        $service = new InventoryStockService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('الكمية المطلوبة أكبر من الكمية المتاحة في المخزون.');

        $service->applyMovement(
            warehouse: $warehouse,
            variant: $variant,
            type: 'damage',
            direction: 'out',
            quantity: 999,
            unitCost: 120,
            referenceType: 'manual',
            referenceNumber: 'DAMAGE-TEST-INVALID',
            notes: 'اختبار كمية أكبر من المتاح.'
        );
    }

    public function test_stock_service_can_create_new_balance_for_variant_without_previous_balance(): void
    {
        $this->seed();

        $warehouse = Warehouse::query()->where('code', 'MAIN-WH')->firstOrFail();

        $variant = ProductVariant::query()->create([
            'product_id' => ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-M')->firstOrFail()->product_id,
            'sku' => 'TL-ABAYA-001-BLK-XL',
            'color' => 'أسود',
            'size' => 'XL',
            'sale_price' => 250,
            'cost_price' => 120,
            'is_active' => true,
        ]);

        $service = new InventoryStockService();

        $service->applyMovement(
            warehouse: $warehouse,
            variant: $variant,
            type: 'purchase',
            direction: 'in',
            quantity: 4,
            unitCost: 120,
            referenceType: 'manual',
            referenceNumber: 'PURCHASE-TEST-XL',
            notes: 'اختبار إنشاء رصيد جديد.'
        );

        $balance = InventoryBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_variant_id', $variant->id)
            ->firstOrFail();

        $this->assertEquals(4.0, (float) $balance->quantity_on_hand);
        $this->assertEquals(0.0, (float) $balance->quantity_reserved);
        $this->assertEquals(4.0, $balance->availableQuantity());

        $this->assertEquals(3, InventoryBalance::query()->count());
        $this->assertEquals(3, InventoryMovement::query()->where('type', 'purchase')->orWhere('type', 'opening_balance')->count());
    }
}
