<?php

namespace App\Http\Controllers;

use App\Models\SalesInvoice;
use Illuminate\View\View;

class SalesInvoiceController extends Controller
{
    public function index(): View
    {
        $invoices = SalesInvoice::query()
            ->with(['customer', 'branch', 'user'])
            ->latest('issued_at')
            ->latest('id')
            ->get();

        return view('sales-invoices.index', [
            'invoices' => $invoices,
        ]);
    }

    public function show(SalesInvoice $salesInvoice): View
    {
        $salesInvoice->load([
            'company',
            'branch',
            'customer',
            'user',
            'items.product',
            'items.variant',
        ]);

        return view('sales-invoices.show', [
            'invoice' => $salesInvoice,
        ]);
    }
}
