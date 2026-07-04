<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use Illuminate\Http\Request;

class SalesOrderController extends Controller
{
    public function index()
    {
        $salesOrders = SalesOrder::query()
            ->with(['customer', 'quotation'])
            ->latest()
            ->paginate(15);

        return view('sales-orders.index', compact('salesOrders'));
    }


    public function updateStatus(Request $request, SalesOrder $salesOrder)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:draft,confirmed,in_progress,completed,cancelled'],
        ]);

        $salesOrder->update([
            'status' => $validated['status'],
        ]);

        return redirect()->route('sales-orders.show', $salesOrder);
    }

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load(['customer', 'quotation', 'items']);

        return view('sales-orders.show', compact('salesOrder'));
    }
}
