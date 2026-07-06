<?php

namespace App\Http\Controllers;

use App\Models\SalesInvoice;
use Illuminate\Contracts\View\View;

class SalesInvoiceCollectionReportController extends Controller
{
    public function index(): View
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

        return view('reports.sales-invoice-collections', [
            'summary' => $summary,
            'invoices' => $invoices,
        ]);
    }
}
