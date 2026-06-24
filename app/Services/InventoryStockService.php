<?php

namespace App\Services;

use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryStockService
{
    public function applyMovement(
        Warehouse $warehouse,
        ProductVariant $variant,
        string $type,
        string $direction,
        float $quantity,
        ?float $unitCost = null,
        ?string $referenceType = null,
        ?string $referenceNumber = null,
        ?string $notes = null
    ): InventoryMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('الكمية يجب أن تكون أكبر من صفر.');
        }

        if (! in_array($direction, ['in', 'out'], true)) {
            throw new InvalidArgumentException('اتجاه الحركة غير صحيح.');
        }

        if (! $warehouse->branch_id) {
            throw new InvalidArgumentException('المستودع غير مرتبط بفرع.');
        }

        return DB::transaction(function () use (
            $warehouse,
            $variant,
            $type,
            $direction,
            $quantity,
            $unitCost,
            $referenceType,
            $referenceNumber,
            $notes
        ) {
            $product = $variant->product()->firstOrFail();

            $balance = InventoryBalance::query()
                ->where('warehouse_id', $warehouse->id)
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = InventoryBalance::query()->create([
                    'company_id' => $warehouse->company_id,
                    'branch_id' => $warehouse->branch_id,
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'reorder_level' => 0,
                ]);
            }

            $currentOnHand = (float) $balance->quantity_on_hand;
            $currentReserved = (float) $balance->quantity_reserved;
            $currentAvailable = $currentOnHand - $currentReserved;

            if ($direction === 'out' && $quantity > $currentAvailable) {
                throw new InvalidArgumentException('الكمية المطلوبة أكبر من الكمية المتاحة في المخزون.');
            }

            $newOnHand = $direction === 'in'
                ? $currentOnHand + $quantity
                : $currentOnHand - $quantity;

            $balance->forceFill([
                'quantity_on_hand' => $newOnHand,
            ])->save();

            return InventoryMovement::query()->create([
                'company_id' => $warehouse->company_id,
                'branch_id' => $warehouse->branch_id,
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'type' => $type,
                'direction' => $direction,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'reference_type' => $referenceType,
                'reference_number' => $referenceNumber,
                'notes' => $notes,
                'occurred_at' => now(),
            ]);
        });
    }
}
