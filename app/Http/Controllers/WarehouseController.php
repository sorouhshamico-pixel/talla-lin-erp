<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(): View
    {
        $warehouses = Warehouse::query()
            ->with(['company', 'branch'])
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get();

        return view('warehouses.index', [
            'warehouses' => $warehouses,
        ]);
    }
}
