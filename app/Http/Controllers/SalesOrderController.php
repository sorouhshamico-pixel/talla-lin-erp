<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;

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

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load(['customer', 'quotation', 'items']);

        return view('sales-orders.show', compact('salesOrder'));
    }
}
