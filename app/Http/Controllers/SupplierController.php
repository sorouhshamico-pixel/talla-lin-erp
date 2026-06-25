<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        $suppliers = Supplier::query()
            ->withCount('purchaseInvoices')
            ->orderBy('id')
            ->get();

        return view('suppliers.index', [
            'suppliers' => $suppliers,
        ]);
    }
}
