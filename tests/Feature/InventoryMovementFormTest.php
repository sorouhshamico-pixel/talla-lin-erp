<?php

namespace Tests\Feature;

use App\Models\InventoryBalance;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryMovementFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_create_inventory_movement_page(): void
    {
        $this->get('/inventory/movements/create')->assertRedirect('/login');
    }

    public function test_owner_can_view_create_inventory_movement_page(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();

        $response = $this->actingAs($admin)->get('/inventory/movements/create');

        $response->assertOk();
        $response->assertSee('حركة مخزون جديدة');
        $response->assertSee('المستودع الرئيسي');
        $response->assertSee('TL-ABAYA-001-BLK-M');
    }

    public function test_owner_can_store_incoming_inventory_movement_from_form(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN-WH')->firstOrFail();
        $variant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-M')->firstOrFail();

        $response = $this->actingAs($admin)->post('/inventory/movements', [
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'type' => 'purchase',
            'direction' => 'in',
            'quantity' => 6,
            'unit_cost' => 120,
            'reference_number' => 'FORM-PURCHASE-001',
            'notes' => 'اختبار من النموذج.',
        ]);

        $response->assertRedirect('/inventory');

        $balance = InventoryBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_variant_id', $variant->id)
            ->firstOrFail();

        $this->assertEquals(18.0, (float) $balance->quantity_on_hand);
        $this->assertEquals(2.0, (float) $balance->quantity_reserved);
        $this->assertEquals(16.0, $balance->availableQuantity());

        $this->assertDatabaseHas('inventory_movements', [
            'reference_number' => 'FORM-PURCHASE-001',
            'type' => 'purchase',
            'direction' => 'in',
        ]);
    }

    public function test_owner_can_store_outgoing_inventory_movement_from_form(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN-WH')->firstOrFail();
        $variant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-M')->firstOrFail();

        $response = $this->actingAs($admin)->post('/inventory/movements', [
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'type' => 'damage',
            'direction' => 'out',
            'quantity' => 4,
            'unit_cost' => 120,
            'reference_number' => 'FORM-DAMAGE-001',
            'notes' => 'اختبار تالف من النموذج.',
        ]);

        $response->assertRedirect('/inventory');

        $balance = InventoryBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_variant_id', $variant->id)
            ->firstOrFail();

        $this->assertEquals(8.0, (float) $balance->quantity_on_hand);
        $this->assertEquals(2.0, (float) $balance->quantity_reserved);
        $this->assertEquals(6.0, $balance->availableQuantity());

        $this->assertDatabaseHas('inventory_movements', [
            'reference_number' => 'FORM-DAMAGE-001',
            'type' => 'damage',
            'direction' => 'out',
        ]);
    }

    public function test_form_rejects_outgoing_quantity_greater_than_available_stock(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN-WH')->firstOrFail();
        $variant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-M')->firstOrFail();

        $response = $this->actingAs($admin)
            ->from('/inventory/movements/create')
            ->post('/inventory/movements', [
                'warehouse_id' => $warehouse->id,
                'product_variant_id' => $variant->id,
                'type' => 'damage',
                'direction' => 'out',
                'quantity' => 999,
                'unit_cost' => 120,
                'reference_number' => 'FORM-DAMAGE-INVALID',
                'notes' => 'كمية أكبر من المتاح.',
            ]);

        $response->assertRedirect('/inventory/movements/create');
        $response->assertSessionHasErrors('movement');
    }
}
