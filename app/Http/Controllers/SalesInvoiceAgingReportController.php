<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesInvoice;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SalesInvoiceAgingReportController extends Controller
{
    public function index(Request $request): View
    {
        $today = now()->toDateString();

        $customers = Customer::query()
            ->orderBy('name')
            ->get();

        $baseQuery = SalesInvoice::query()
            ->with(['customer', 'branch'])
            ->where('remaining_amount', '>', 0);

        if ($request->filled('customer_id')) {
            $baseQuery->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('payment_status')) {
            $baseQuery->where('payment_status', $request->input('payment_status'));
        }

        $notDueQuery = (clone $baseQuery)
            ->whereNotNull('due_at')
            ->whereDate('due_at', '>=', $today);

        $overdue1To30Query = (clone $baseQuery)
            ->whereNotNull('due_at')
            ->whereDate('due_at', '>=', now()->subDays(30)->toDateString())
            ->whereDate('due_at', '<', $today);

        $overdue31To60Query = (clone $baseQuery)
            ->whereNotNull('due_at')
            ->whereDate('due_at', '>=', now()->subDays(60)->toDateString())
            ->whereDate('due_at', '<', now()->subDays(30)->toDateString());

        $overdue61To90Query = (clone $baseQuery)
            ->whereNotNull('due_at')
            ->whereDate('due_at', '>=', now()->subDays(90)->toDateString())
            ->whereDate('due_at', '<', now()->subDays(60)->toDateString());

        $overdueMoreThan90Query = (clone $baseQuery)
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<', now()->subDays(90)->toDateString());

        $withoutDueDateQuery = (clone $baseQuery)
            ->whereNull('due_at');

        $summary = [
            'not_due' => [
                'label' => 'غير مستحقة بعد',
                'count' => (clone $notDueQuery)->count(),
                'total' => round((float) (clone $notDueQuery)->sum('remaining_amount'), 2),
            ],
            'overdue_1_30' => [
                'label' => 'متأخرة 1 إلى 30 يوم',
                'count' => (clone $overdue1To30Query)->count(),
                'total' => round((float) (clone $overdue1To30Query)->sum('remaining_amount'), 2),
            ],
            'overdue_31_60' => [
                'label' => 'متأخرة 31 إلى 60 يوم',
                'count' => (clone $overdue31To60Query)->count(),
                'total' => round((float) (clone $overdue31To60Query)->sum('remaining_amount'), 2),
            ],
            'overdue_61_90' => [
                'label' => 'متأخرة 61 إلى 90 يوم',
                'count' => (clone $overdue61To90Query)->count(),
                'total' => round((float) (clone $overdue61To90Query)->sum('remaining_amount'), 2),
            ],
            'overdue_more_than_90' => [
                'label' => 'أكثر من 90 يوم',
                'count' => (clone $overdueMoreThan90Query)->count(),
                'total' => round((float) (clone $overdueMoreThan90Query)->sum('remaining_amount'), 2),
            ],
            'without_due_date' => [
                'label' => 'بدون تاريخ استحقاق',
                'count' => (clone $withoutDueDateQuery)->count(),
                'total' => round((float) (clone $withoutDueDateQuery)->sum('remaining_amount'), 2),
            ],
        ];

        $totalOutstanding = round((float) (clone $baseQuery)->sum('remaining_amount'), 2);
        $totalCount = (clone $baseQuery)->count();

        $invoices = (clone $baseQuery)
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->latest('id')
            ->limit(100)
            ->get();

        return view('reports.sales-invoice-aging', [
            'summary' => $summary,
            'invoices' => $invoices,
            'totalOutstanding' => $totalOutstanding,
            'totalCount' => $totalCount,
            'today' => $today,
            'customers' => $customers,
            'customerFilter' => $request->input('customer_id'),
            'paymentStatusFilter' => $request->input('payment_status'),
        ]);
    }
}
