<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesInvoiceCollectionNote;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SalesInvoiceCollectionFollowUpReportController extends Controller
{
    public function index(Request $request): View
    {
        $today = now()->toDateString();

        $customers = Customer::query()
            ->orderBy('name')
            ->get();

        $baseQuery = SalesInvoiceCollectionNote::query()
            ->with([
                'salesInvoice.customer',
                'salesInvoice.branch',
                'user',
            ])
            ->whereNotNull('follow_up_at')
            ->whereNull('completed_at');

        if ($request->filled('customer_id')) {
            $baseQuery->whereHas('salesInvoice', function ($query) use ($request): void {
                $query->where('customer_id', $request->input('customer_id'));
            });
        }

        if ($request->filled('follow_up_from')) {
            $baseQuery->whereDate('follow_up_at', '>=', $request->input('follow_up_from'));
        }

        if ($request->filled('follow_up_to')) {
            $baseQuery->whereDate('follow_up_at', '<=', $request->input('follow_up_to'));
        }

        $dueQuery = (clone $baseQuery)
            ->whereDate('follow_up_at', '<=', $today)
            ->whereHas('salesInvoice', function ($query): void {
                $query->where('remaining_amount', '>', 0);
            });

        $upcomingQuery = (clone $baseQuery)
            ->whereDate('follow_up_at', '>', $today)
            ->whereHas('salesInvoice', function ($query): void {
                $query->where('remaining_amount', '>', 0);
            });

        $dueNotes = (clone $dueQuery)
            ->orderBy('follow_up_at')
            ->latest('id')
            ->limit(50)
            ->get();

        $summary = [
            'due_notes_count' => (clone $dueQuery)->count(),
            'upcoming_notes_count' => (clone $upcomingQuery)->count(),
            'due_invoices_count' => (clone $dueQuery)->distinct('sales_invoice_id')->count('sales_invoice_id'),
            'due_remaining_total' => round((float) $dueNotes->pluck('salesInvoice')->filter()->unique('id')->sum('remaining_amount'), 2),
        ];

        return view('reports.sales-invoice-collection-follow-ups', [
            'summary' => $summary,
            'dueNotes' => $dueNotes,
            'today' => $today,
            'customers' => $customers,
            'customerFilter' => $request->input('customer_id'),
            'followUpFromFilter' => $request->input('follow_up_from'),
            'followUpToFilter' => $request->input('follow_up_to'),
        ]);
    }
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $today = now()->toDateString();

        $baseQuery = SalesInvoiceCollectionNote::query()
            ->with([
                'salesInvoice.customer',
                'salesInvoice.branch',
                'user',
            ])
            ->whereNotNull('follow_up_at')
            ->whereNull('completed_at');

        if ($request->filled('customer_id')) {
            $baseQuery->whereHas('salesInvoice', function ($query) use ($request): void {
                $query->where('customer_id', $request->input('customer_id'));
            });
        }

        if ($request->filled('follow_up_from')) {
            $baseQuery->whereDate('follow_up_at', '>=', $request->input('follow_up_from'));
        }

        if ($request->filled('follow_up_to')) {
            $baseQuery->whereDate('follow_up_at', '<=', $request->input('follow_up_to'));
        }

        $notes = (clone $baseQuery)
            ->whereDate('follow_up_at', '<=', $today)
            ->whereHas('salesInvoice', function ($query): void {
                $query->where('remaining_amount', '>', 0);
            })
            ->orderBy('follow_up_at')
            ->latest('id')
            ->get();

        $customerFilterLabel = 'all';

        if ($request->filled('customer_id')) {
            $customer = Customer::query()->find($request->input('customer_id'));
            $customerFilterLabel = $customer
                ? $customer->name . ' #' . $customer->id
                : (string) $request->input('customer_id');
        }

        $exportFilters = [
            'customer_id' => $customerFilterLabel,
            'follow_up_from' => $request->input('follow_up_from') ?: 'all',
            'follow_up_to' => $request->input('follow_up_to') ?: 'all',
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];

        $fileName = 'sales-invoice-collection-follow-ups-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($notes, $exportFilters): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['تقرير متابعات تحصيل فواتير المبيعات']);
            fputcsv($handle, ['تاريخ إنشاء التقرير', $exportFilters['generated_at']]);
            fputcsv($handle, ['فلتر العميل', $exportFilters['customer_id']]);
            fputcsv($handle, ['من تاريخ متابعة', $exportFilters['follow_up_from']]);
            fputcsv($handle, ['إلى تاريخ متابعة', $exportFilters['follow_up_to']]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'تاريخ المتابعة',
                'رقم الفاتورة',
                'العميل',
                'الفرع',
                'حالة الدفع',
                'إجمالي الفاتورة',
                'المدفوع',
                'المتبقي',
                'تاريخ الاستحقاق',
                'الملاحظة',
                'المستخدم',
            ]);

            $remainingTotal = 0.0;
            $invoiceIds = [];

            foreach ($notes as $note) {
                $invoice = $note->salesInvoice;
                $remainingAmount = (float) ($invoice?->remaining_amount ?? 0);

                if ($invoice) {
                    $invoiceIds[$invoice->id] = true;
                    $remainingTotal += $remainingAmount;
                }

                fputcsv($handle, [
                    $note->follow_up_at?->format('Y-m-d') ?: '',
                    $invoice?->invoice_number ?: '',
                    $invoice?->customer?->name ?: '',
                    $invoice?->branch?->name ?: '',
                    $invoice?->displayPaymentStatus() ?: '',
                    number_format((float) ($invoice?->grand_total ?? 0), 2, '.', ''),
                    number_format((float) ($invoice?->paid_amount ?? 0), 2, '.', ''),
                    number_format($remainingAmount, 2, '.', ''),
                    $invoice?->due_at?->format('Y-m-d') ?: '',
                    $note->note,
                    $note->user?->name ?: '',
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                'إجمالي المتابعات',
                $notes->count(),
                'عدد الفواتير',
                count($invoiceIds),
                'إجمالي المتبقي',
                number_format($remainingTotal, 2, '.', ''),
            ]);

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }


}
