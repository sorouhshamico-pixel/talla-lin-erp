<?php

namespace Tests\Feature;

use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_inventory_balances_and_movements_are_created_successfully(): void
    {
        $this->seed();

        $mediumVariant = ProductVariant::query()
            ->where('sku', 'TL-ABAYA-001-BLK-M')
            ->firstOrFail();

        $largeVariant = ProductVariant::query()
            ->where('sku', 'TL-ABAYA-001-BLK-L')
            ->firstOrFail();

        $mediumBalance = InventoryBalance::query()
            ->where('product_variant_id', $mediumVariant->id)
            ->first();

        $largeBalance = InventoryBalance::query()
            ->where('product_variant_id', $largeVariant->id)
            ->first();

        $this->assertNotNull($mediumBalance);
        $this->assertNotNull($largeBalance);

        $this->assertEquals(12.0, (float) $mediumBalance->quantity_on_hand);
        $this->assertEquals(2.0, (float) $mediumBalance->quantity_reserved);
        $this->assertEquals(10.0, $mediumBalance->availableQuantity());
        $this->assertFalse($mediumBalance->isBelowReorderLevel());

        $this->assertEquals(8.0, (float) $largeBalance->quantity_on_hand);
        $this->assertEquals(1.0, (float) $largeBalance->quantity_reserved);
        $this->assertEquals(7.0, $largeBalance->availableQuantity());

        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $mediumVariant->id,
            'type' => 'opening_balance',
            'direction' => 'in',
            'reference_number' => 'OPENING-MAIN-WH-M',
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $largeVariant->id,
            'type' => 'opening_balance',
            'direction' => 'in',
            'reference_number' => 'OPENING-MAIN-WH-L',
        ]);

        $this->assertEquals(2, InventoryBalance::query()->count());
        $this->assertEquals(2, InventoryMovement::query()->count());
    }

    public function test_guest_cannot_view_inventory_page(): void
    {
        $this->get('/inventory')->assertRedirect('/login');
    }

    public function test_owner_can_view_inventory_page(): void
    {
        $this->seed();

        $admin = User::query()
            ->where('email', 'admin@tallalin.local')
            ->firstOrFail();

        $response = $this->actingAs($admin)->get('/inventory');

        $response->assertOk();
        $response->assertSee('المخزون');
        $response->assertSee('أرصدة المخزون');
        $response->assertSee('آخر حركات المخزون');
        $response->assertSee('عباية لين كلاسيك');
        $response->assertSee('TL-ABAYA-001-BLK-M');
        $response->assertSee('المستودع الرئيسي');
    }
}
