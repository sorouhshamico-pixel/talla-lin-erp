<?php

namespace App\Http\Controllers;

use App\Models\SalesInvoice;
use App\Services\ReportSavedViewService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SalesInvoiceCollectionReportController extends Controller
{
    private const REPORT_KEY = 'sales-invoice-collections';

    public function index(Request $request, ReportSavedViewService $savedViews): View
    {
        [$summary, $invoices] = $this->buildReportData();

        $savedViewsForReport = Schema::hasTable('report_saved_views')
            ? $savedViews->listForReport($request->user(), self::REPORT_KEY)
            : collect();

        return view('reports.sales-invoice-collections', [
            'summary' => $summary,
            'invoices' => $invoices,
            'savedViews' => $savedViewsForReport,
        ]);
    }

    public function json(): JsonResponse
    {
        [$summary, $invoices] = $this->buildReportData();

        return response()->json([
            'summary' => $summary,
            'invoices' => $invoices->map(fn (SalesInvoice $invoice): array => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'customer' => $invoice->customer?->name,
                'branch' => $invoice->branch?->name,
                'payment_status' => $invoice->payment_status,
                'payment_status_label' => $invoice->displayPaymentStatus(),
                'grand_total' => (float) $invoice->grand_total,
                'paid_amount' => (float) $invoice->paid_amount,
                'remaining_amount' => (float) $invoice->remaining_amount,
                'due_at' => $invoice->due_at?->format('Y-m-d'),
            ])->values(),
        ]);
    }

    public function storeSavedView(Request $request, ReportSavedViewService $savedViews): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_default' => ['nullable'],
        ]);

        $savedViews->save(
            $request->user(),
            self::REPORT_KEY,
            $validated['name'],
            [],
            $request->boolean('is_default')
        );

        return redirect()
            ->route('reports.sales-invoice-collections.index')
            ->with('status', 'تم حفظ عرض تقرير تحصيل فواتير المبيعات بنجاح.');
    }

    /**
     * @return array{0: array<string, int|float>, 1: \Illuminate\Support\Collection<int, SalesInvoice>}
     */
    private function buildReportData(): array
    {
        $baseQuery = SalesInvoice::query()
            ->with(['customer', 'branch'])
            ->where('remaining_amount', '>', 0);

        $summary = [
            'outstanding_count' => (clone $baseQuery)->count(),
            'outstanding_total' => round((float) (clone $baseQuery)->sum('remaining_amount'), 2),

            'overdue_count' => (clone $baseQuery)
                ->whereDate('due_at', '<', now()->toDateString())
                ->count(),
            'overdue_total' => round((float) (clone $baseQuery)
                ->whereDate('due_at', '<', now()->toDateString())
                ->sum('remaining_amount'), 2),

            'unpaid_count' => (clone $baseQuery)
                ->where('payment_status', 'unpaid')
                ->count(),
            'unpaid_total' => round((float) (clone $baseQuery)
                ->where('payment_status', 'unpaid')
                ->sum('remaining_amount'), 2),

            'partial_count' => (clone $baseQuery)
                ->where('payment_status', 'partial')
                ->count(),
            'partial_total' => round((float) (clone $baseQuery)
                ->where('payment_status', 'partial')
                ->sum('remaining_amount'), 2),
        ];

        $invoices = $baseQuery
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->latest('id')
            ->limit(50)
            ->get();

        return [$summary, $invoices];
    }
}