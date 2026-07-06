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

        if ($request->input('collection_status') === 'overdue') {
            $invoicesQuery
                ->where('remaining_amount', '>', 0)
                ->whereDate('due_at', '<', now()->toDateString());
        }

        if ($request->filled('payment_status')) {
            $invoicesQuery->where('payment_status', $request->input('payment_status'));
        }

        if ($request->filled('issued_from')) {
            $invoicesQuery->whereDate('issued_at', '>=', $request->input('issued_from'));
        }

        if ($request->filled('issued_to')) {
            $invoicesQuery->whereDate('issued_at', '<=', $request->input('issued_to'));
        }

        $salesInvoiceSummary = [
            'count' => (clone $invoicesQuery)->count(),
            'grand_total' => round((float) (clone $invoicesQuery)->sum('grand_total'), 2),
            'paid_amount' => round((float) (clone $invoicesQuery)->sum('paid_amount'), 2),
            'remaining_amount' => round((float) (clone $invoicesQuery)->sum('remaining_amount'), 2),
            'outstanding_count' => (clone $invoicesQuery)->where('remaining_amount', '>', 0)->count(),
            'paid_count' => (clone $invoicesQuery)->where('payment_status', 'paid')->count(),
        ];

        $invoices = $invoicesQuery
            ->latest('issued_at')
            ->latest('id')
            ->get();

        $customers = Customer::query()
            ->orderBy('name')
            ->get();

        return view('sales-invoices.index', [
            'invoices' => $invoices,
            'customers' => $customers,
            'salesInvoiceSummary' => $salesInvoiceSummary,
            'customerFilter' => $request->input('customer_id'),
            'collectionStatusFilter' => $request->input('collection_status'),
            'paymentStatusFilter' => $request->input('payment_status'),
            'issuedFromFilter' => $request->input('issued_from'),
            'issuedToFilter' => $request->input('issued_to'),
        ]);
    }


    public function export(Request $request)
    {
        $invoicesQuery = SalesInvoice::query()
            ->with(['customer', 'branch', 'user']);

        if ($request->filled('customer_id')) {
            $invoicesQuery->where('customer_id', $request->input('customer_id'));
        }

        if ($request->input('collection_status') === 'outstanding') {
            $invoicesQuery->where('remaining_amount', '>', 0);
        }

        if ($request->input('collection_status') === 'overdue') {
            $invoicesQuery
                ->where('remaining_amount', '>', 0)
                ->whereDate('due_at', '<', now()->toDateString());
        }

        if ($request->filled('payment_status')) {
            $invoicesQuery->where('payment_status', $request->input('payment_status'));
        }

        if ($request->filled('issued_from')) {
            $invoicesQuery->whereDate('issued_at', '>=', $request->input('issued_from'));
        }

        if ($request->filled('issued_to')) {
            $invoicesQuery->whereDate('issued_at', '<=', $request->input('issued_to'));
        }

        $invoices = $invoicesQuery
            ->latest('issued_at')
            ->latest('id')
            ->get();

        $customerFilterLabel = 'all';

        if ($request->filled('customer_id')) {
            $customerFilterModel = Customer::query()->find($request->input('customer_id'));
            $customerFilterLabel = $customerFilterModel
                ? $customerFilterModel->name . ' #' . $customerFilterModel->id
                : (string) $request->input('customer_id');
        }

        $paymentStatusLabels = [
            'unpaid' => 'غير مدفوعة',
            'partial' => 'مدفوعة جزئيًا',
            'paid' => 'مدفوعة بالكامل',
        ];

        $collectionStatusLabels = [
            'outstanding' => 'فواتير ذات مبالغ متبقية',
            'overdue' => 'فواتير متأخرة التحصيل',
        ];

        $exportFilters = [
            'customer_id' => $customerFilterLabel,
            'payment_status' => $request->filled('payment_status')
                ? ($paymentStatusLabels[$request->input('payment_status')] ?? $request->input('payment_status'))
                : 'all',
            'collection_status' => $request->filled('collection_status')
                ? ($collectionStatusLabels[$request->input('collection_status')] ?? $request->input('collection_status'))
                : 'all',
            'issued_from' => $request->input('issued_from') ?: 'all',
            'issued_to' => $request->input('issued_to') ?: 'all',
        ];

        $fileName = 'sales-invoices-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($invoices, $exportFilters): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['تقرير فواتير المبيعات']);
            fputcsv($handle, ['تاريخ إنشاء التقرير', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, ['فلتر العميل', $exportFilters['customer_id']]);
            fputcsv($handle, ['فلتر حالة الدفع', $exportFilters['payment_status']]);
            fputcsv($handle, ['فلتر حالة التحصيل', $exportFilters['collection_status']]);
            fputcsv($handle, ['من تاريخ', $exportFilters['issued_from']]);
            fputcsv($handle, ['إلى تاريخ', $exportFilters['issued_to']]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'رقم الفاتورة',
                'التاريخ',
                'العميل',
                'الفرع',
                'حالة الفاتورة',
                'حالة الدفع',
                'الإجمالي',
                'المدفوع',
                'المتبقي',
                'تاريخ الاستحقاق',
                'ملاحظات',
            ]);

            foreach ($invoices as $invoice) {
                fputcsv($handle, [
                    $invoice->invoice_number,
                    $invoice->issued_at?->format('Y-m-d'),
                    $invoice->customer?->name ?? '',
                    $invoice->branch?->name_ar ?? $invoice->branch?->name ?? $invoice->branch?->name_en ?? '',
                    $invoice->displayStatus(),
                    $invoice->displayPaymentStatus(),
                    number_format((float) $invoice->grand_total, 2, '.', ''),
                    number_format((float) $invoice->paid_amount, 2, '.', ''),
                    number_format((float) $invoice->remaining_amount, 2, '.', ''),
                    $invoice->due_at?->format('Y-m-d'),
                    $invoice->notes,
                ]);
            }

            fputcsv($handle, []);

            fputcsv($handle, [
                'إجمالي النتائج',
                '',
                '',
                '',
                '',
                '',
                number_format((float) $invoices->sum('grand_total'), 2, '.', ''),
                number_format((float) $invoices->sum('paid_amount'), 2, '.', ''),
                number_format((float) $invoices->sum('remaining_amount'), 2, '.', ''),
                '',
                'عدد الفواتير: ' . $invoices->count(),
            ]);

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
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
