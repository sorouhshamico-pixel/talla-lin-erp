<?php

namespace App\Http\Controllers;

use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Services\InventoryStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class InventoryController extends Controller
{
    public function index(): View
    {
        $balances = InventoryBalance::query()
            ->with(['branch', 'warehouse', 'product', 'variant'])
            ->orderBy('warehouse_id')
            ->orderBy('product_id')
            ->get();

        $recentMovements = InventoryMovement::query()
            ->with(['branch', 'warehouse', 'product', 'variant'])
            ->latest('occurred_at')
            ->latest('id')
            ->limit(10)
            ->get();

        return view('inventory.index', [
            'balances' => $balances,
            'recentMovements' => $recentMovements,
        ]);
    }

    public function createMovement(): View
    {
        $warehouses = Warehouse::query()
            ->with('branch')
            ->where('is_active', true)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get();

        $variants = ProductVariant::query()
            ->with('product')
            ->where('is_active', true)
            ->orderBy('sku')
            ->get();

        return view('inventory.create-movement', [
            'warehouses' => $warehouses,
            'variants' => $variants,
        ]);
    }

    public function storeMovement(Request $request, InventoryStockService $stockService): RedirectResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'type' => ['required', 'string', 'in:purchase,adjustment,damage,return'],
            'direction' => ['required', 'string', 'in:in,out'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $warehouse = Warehouse::query()->findOrFail($data['warehouse_id']);
        $variant = ProductVariant::query()->findOrFail($data['product_variant_id']);

        try {
            $stockService->applyMovement(
                warehouse: $warehouse,
                variant: $variant,
                type: $data['type'],
                direction: $data['direction'],
                quantity: (float) $data['quantity'],
                unitCost: isset($data['unit_cost']) ? (float) $data['unit_cost'] : null,
                referenceType: 'manual',
                referenceNumber: $data['reference_number'] ?? null,
                notes: $data['notes'] ?? null
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors([
                    'movement' => $exception->getMessage(),
                ])
                ->withInput();
        }

        return redirect()
            ->route('inventory.index')
            ->with('success', 'تم تسجيل حركة المخزون بنجاح.');
    }
}
