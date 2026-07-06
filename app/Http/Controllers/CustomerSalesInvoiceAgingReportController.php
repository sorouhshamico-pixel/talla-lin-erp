<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesInvoice;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CustomerSalesInvoiceAgingReportController extends Controller
{
    public function index(Request $request): View
    {
        $today = now()->toDateString();

        $customers = Customer::query()
            ->orderBy('name')
            ->get();

        $invoicesQuery = SalesInvoice::query()
            ->with(['customer'])
            ->where('remaining_amount', '>', 0);

        if ($request->filled('customer_id')) {
            $invoicesQuery->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('aging_bucket')) {
            $this->applyAgingBucketFilter($invoicesQuery, $request->input('aging_bucket'), $today);
        }

        $invoices = $invoicesQuery
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->latest('id')
            ->get();

        $rows = $invoices
            ->groupBy('customer_id')
            ->map(function ($customerInvoices) use ($today): array {
                $firstInvoice = $customerInvoices->first();

                $summary = [
                    'customer' => $firstInvoice?->customer,
                    'invoice_count' => $customerInvoices->count(),
                    'remaining_total' => round((float) $customerInvoices->sum('remaining_amount'), 2),
                    'not_due_total' => 0.0,
                    'overdue_1_30_total' => 0.0,
                    'overdue_31_60_total' => 0.0,
                    'overdue_61_90_total' => 0.0,
                    'overdue_more_than_90_total' => 0.0,
                    'without_due_date_total' => 0.0,
                    'oldest_due_at' => null,
                ];

                foreach ($customerInvoices as $invoice) {
                    $remainingAmount = (float) $invoice->remaining_amount;

                    if (! $invoice->due_at) {
                        $summary['without_due_date_total'] += $remainingAmount;
                        continue;
                    }

                    if ($summary['oldest_due_at'] === null || $invoice->due_at->lt($summary['oldest_due_at'])) {
                        $summary['oldest_due_at'] = $invoice->due_at;
                    }

                    if ($invoice->due_at->toDateString() >= $today) {
                        $summary['not_due_total'] += $remainingAmount;
                        continue;
                    }

                    $daysOverdue = $invoice->due_at->diffInDays(now());

                    if ($daysOverdue <= 30) {
                        $summary['overdue_1_30_total'] += $remainingAmount;
                    } elseif ($daysOverdue <= 60) {
                        $summary['overdue_31_60_total'] += $remainingAmount;
                    } elseif ($daysOverdue <= 90) {
                        $summary['overdue_61_90_total'] += $remainingAmount;
                    } else {
                        $summary['overdue_more_than_90_total'] += $remainingAmount;
                    }
                }

                return $summary;
            })
            ->sortByDesc('remaining_total')
            ->values();

        $summary = [
            'customers_count' => $rows->count(),
            'invoice_count' => $invoices->count(),
            'remaining_total' => round((float) $invoices->sum('remaining_amount'), 2),
            'overdue_total' => round((float) $rows->sum(function (array $row): float {
                return (float) $row['overdue_1_30_total']
                    + (float) $row['overdue_31_60_total']
                    + (float) $row['overdue_61_90_total']
                    + (float) $row['overdue_more_than_90_total'];
            }), 2),
        ];

        return view('reports.customer-sales-invoice-aging', [
            'rows' => $rows,
            'summary' => $summary,
            'today' => $today,
            'customers' => $customers,
            'customerFilter' => $request->input('customer_id'),
            'agingBucketFilter' => $request->input('aging_bucket'),
        ]);
    }


    public function export(Request $request)
    {
        $fileName = 'customer-sales-invoice-aging-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fwrite($handle, chr(239) . chr(187) . chr(191));

            fputcsv($handle, ['تقرير أعمار ذمم العملاء']);
            fputcsv($handle, ['تاريخ إنشاء التقرير', now()->format('Y-m-d H:i:s')]);

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
    private function applyAgingBucketFilter(Builder $query, ?string $bucket, string $today): void
    {
        match ($bucket) {
            'not_due' => $query->whereNotNull('due_at')->whereDate('due_at', '>=', $today),
            'overdue_1_30' => $query->whereNotNull('due_at')->whereDate('due_at', '>=', now()->subDays(30)->toDateString())->whereDate('due_at', '<', $today),
            'overdue_31_60' => $query->whereNotNull('due_at')->whereDate('due_at', '>=', now()->subDays(60)->toDateString())->whereDate('due_at', '<', now()->subDays(30)->toDateString()),
            'overdue_61_90' => $query->whereNotNull('due_at')->whereDate('due_at', '>=', now()->subDays(90)->toDateString())->whereDate('due_at', '<', now()->subDays(60)->toDateString()),
            'overdue_more_than_90' => $query->whereNotNull('due_at')->whereDate('due_at', '<', now()->subDays(90)->toDateString()),
            'without_due_date' => $query->whereNull('due_at'),
            default => null,
        };
    }

}
