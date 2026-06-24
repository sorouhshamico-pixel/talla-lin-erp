<?php

namespace App\Http\Controllers;

use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use Illuminate\View\View;

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
}
