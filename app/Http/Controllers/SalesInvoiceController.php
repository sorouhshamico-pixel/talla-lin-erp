<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\SalesInvoice;
use App\Services\InventoryStockService;
use App\Services\SalesInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class SalesInvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $invoicesQuery = SalesInvoice::query()
            ->with(['customer', 'branch', 'user']);

        if ($request->filled('customer_id')) {
            $invoicesQuery->where('customer_id', $request->input('customer_id'));
        }

        if ($request->input('collection_status') === 'outstanding') {
            $invoicesQuery->where('remaining_amount', '>', 0);
        }

        if ($request->filled('payment_status')) {
            $invoicesQuery->where('payment_status', $request->input('payment_status'));
        }

        $invoices = $invoicesQuery
            ->latest('issued_at')
            ->latest('id')
            ->get();

        return view('sales-invoices.index', [
            'invoices' => $invoices,
            'customerFilter' => $request->input('customer_id'),
            'collectionStatusFilter' => $request->input('collection_status'),
            'paymentStatusFilter' => $request->input('payment_status'),
        ]);
    }

    public function create(): View
    {
        $customers = Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $branches = Branch::query()
            ->where('is_active', true)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get();

        $variants = ProductVariant::query()
            ->with('product')
            ->where('is_active', true)
            ->orderBy('sku')
            ->get();

        return view('sales-invoices.create', [
            'customers' => $customers,
            'branches' => $branches,
            'variants' => $variants,
        ]);
    }

    public function store(Request $request, SalesInvoiceService $invoiceService): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $customer = Customer::query()->findOrFail($data['customer_id']);
        $branch = Branch::query()->findOrFail($data['branch_id']);

        try {
            $invoice = $invoiceService->createDraftInvoice(
                customer: $customer,
                branch: $branch,
                user: $request->user(),
                invoiceNumber: $data['invoice_number'] ?: null,
                notes: $data['notes'] ?? null,
                items: [
                    [
                        'product_variant_id' => $data['product_variant_id'],
                        'quantity' => (float) $data['quantity'],
                        'unit_price' => (float) $data['unit_price'],
                        'discount_amount' => (float) ($data['discount_amount'] ?? 0),
                        'tax_rate' => (float) $data['tax_rate'],
                    ],
                ]
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors([
                    'invoice' => $exception->getMessage(),
                ])
                ->withInput();
        }

        return redirect()
            ->route('sales-invoices.show', $invoice)
            ->with('success', 'تم إنشاء فاتورة البيع بنجاح.');
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
            'payments' => fn ($query) => $query
                ->with('user')
                ->latest('paid_at')
                ->latest('id'),
        ]);

        return view('sales-invoices.show', [
            'invoice' => $salesInvoice,
        ]);
    }

    public function issue(
        SalesInvoice $salesInvoice,
        SalesInvoiceService $invoiceService,
        InventoryStockService $stockService
    ): RedirectResponse {
        try {
            $invoiceService->issueInvoice($salesInvoice, $stockService);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'issue' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('sales-invoices.show', $salesInvoice)
            ->with('success', 'تم اعتماد الفاتورة وخصم المخزون بنجاح.');
    }

    public function createPayment(SalesInvoice $salesInvoice): RedirectResponse|View
    {
        if ($salesInvoice->status !== 'issued') {
            return redirect()
                ->route('sales-invoices.show', $salesInvoice)
                ->withErrors([
                    'payment' => 'لا يمكن تسجيل دفعة على فاتورة غير معتمدة.',
                ]);
        }

        if ((float) $salesInvoice->remaining_amount <= 0) {
            return redirect()
                ->route('sales-invoices.show', $salesInvoice)
                ->withErrors([
                    'payment' => 'لا يمكن تسجيل دفعة على فاتورة مدفوعة بالكامل.',
                ]);
        }

        $salesInvoice->load(['customer', 'branch']);

        return view('sales-invoices.create-payment', [
            'invoice' => $salesInvoice,
        ]);
    }

    public function storePayment(
        Request $request,
        SalesInvoice $salesInvoice,
        SalesInvoiceService $invoiceService
    ): RedirectResponse {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', 'in:cash,card,bank_transfer,online,other'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $invoiceService->recordPayment(
                invoice: $salesInvoice,
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
            ->route('sales-invoices.show', $salesInvoice)
            ->with('success', 'تم تسجيل الدفعة بنجاح.');
    }
}
