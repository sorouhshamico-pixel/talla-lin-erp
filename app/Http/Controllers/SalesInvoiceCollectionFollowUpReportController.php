<?php

namespace App\Http\Controllers;

use App\Models\SalesInvoiceCollectionNote;
use Illuminate\Contracts\View\View;

class SalesInvoiceCollectionFollowUpReportController extends Controller
{
    public function index(): View
    {
        $today = now()->toDateString();

        $baseQuery = SalesInvoiceCollectionNote::query()
            ->with([
                'salesInvoice.customer',
                'salesInvoice.branch',
                'user',
            ])
            ->whereNotNull('follow_up_at');

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
        ]);
    }
}
