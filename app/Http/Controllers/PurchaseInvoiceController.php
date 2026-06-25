<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Services\InventoryStockService;
use App\Services\PurchaseInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class PurchaseInvoiceController extends Controller
{
    public function index(): View
    {
        $invoices = PurchaseInvoice::query()
            ->with(['supplier', 'branch', 'warehouse', 'user'])
            ->latest('invoice_date')
            ->latest('id')
            ->get();

        return view('purchase-invoices.index', [
            'invoices' => $invoices,
        ]);
    }

    public function show(PurchaseInvoice $purchaseInvoice): View
    {
        $purchaseInvoice->load([
            'company',
            'branch',
            'warehouse',
            'supplier',
            'user',
            'items.product',
            'items.variant',
        ]);

        return view('purchase-invoices.show', [
            'invoice' => $purchaseInvoice,
        ]);
    }

    public function receive(
        PurchaseInvoice $purchaseInvoice,
        PurchaseInvoiceService $purchaseInvoiceService,
        InventoryStockService $stockService
    ): RedirectResponse {
        try {
            $purchaseInvoiceService->receiveInvoice($purchaseInvoice, $stockService);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'receive' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('purchase-invoices.show', $purchaseInvoice)
            ->with('success', 'تم استلام فاتورة الشراء وزيادة المخزون بنجاح.');
    }
}
