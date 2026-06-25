<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ProductVariant;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\InventoryStockService;
use App\Services\PurchaseInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function create(): View
    {
        $suppliers = Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $branches = Branch::query()
            ->where('is_active', true)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get();

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

        return view('purchase-invoices.create', [
            'suppliers' => $suppliers,
            'branches' => $branches,
            'warehouses' => $warehouses,
            'variants' => $variants,
        ]);
    }

    public function store(Request $request, PurchaseInvoiceService $purchaseInvoiceService): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $supplier = Supplier::query()->findOrFail($data['supplier_id']);
        $branch = Branch::query()->findOrFail($data['branch_id']);
        $warehouse = Warehouse::query()->findOrFail($data['warehouse_id']);

        try {
            $invoice = $purchaseInvoiceService->createDraftInvoice(
                supplier: $supplier,
                branch: $branch,
                warehouse: $warehouse,
                user: $request->user(),
                invoiceNumber: $data['invoice_number'] ?: null,
                notes: $data['notes'] ?? null,
                items: [
                    [
                        'product_variant_id' => $data['product_variant_id'],
                        'quantity' => (float) $data['quantity'],
                        'unit_cost' => (float) $data['unit_cost'],
                        'discount_amount' => (float) ($data['discount_amount'] ?? 0),
                        'tax_rate' => (float) $data['tax_rate'],
                    ],
                ]
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors([
                    'purchase_invoice' => $exception->getMessage(),
                ])
                ->withInput();
        }

        return redirect()
            ->route('purchase-invoices.show', $invoice)
            ->with('success', 'تم إنشاء فاتورة الشراء بنجاح.');
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
            'payments.user',
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

    public function createPayment(PurchaseInvoice $purchaseInvoice): View
    {
        $purchaseInvoice->load(['supplier', 'branch', 'warehouse']);

        return view('purchase-invoices.create-payment', [
            'invoice' => $purchaseInvoice,
        ]);
    }

    public function storePayment(
        Request $request,
        PurchaseInvoice $purchaseInvoice,
        PurchaseInvoiceService $purchaseInvoiceService
    ): RedirectResponse {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', 'in:cash,card,bank_transfer,online,other'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $purchaseInvoiceService->recordPayment(
                invoice: $purchaseInvoice,
                user: $request->user(),
                amount: (float) $data['amount'],
                method: $data['method'],
                referenceNumber: $data['reference_number'] ?? null,
                notes: $data['notes'] ?? null
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors([
                    'payment' => $exception->getMessage(),
                ])
                ->withInput();
        }

        return redirect()
            ->route('purchase-invoices.show', $purchaseInvoice)
            ->with('success', 'تم تسجيل دفعة المورد بنجاح.');
    }
}
