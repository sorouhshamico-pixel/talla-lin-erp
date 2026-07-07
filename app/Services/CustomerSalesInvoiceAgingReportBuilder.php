<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CustomerSalesInvoiceAgingReportBuilder
{
    public function build(Request $request): array
    {
        $reportDate = now()->startOfDay();

        $invoicesQuery = SalesInvoice::query()
            ->with(['customer'])
            ->where('remaining_amount', '>', 0);

        if ($request->filled('customer_id')) {
            $invoicesQuery->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('aging_bucket')) {
            $this->applyAgingBucketFilter($invoicesQuery, (string) $request->input('aging_bucket'), $reportDate);
        }

        $invoices = $invoicesQuery
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->latest('id')
            ->get();

        $rows = $invoices
            ->groupBy('customer_id')
            ->map(function ($customerInvoices) use ($reportDate) {
                $firstInvoice = $customerInvoices->first();

                $row = [
                    'customer' => $firstInvoice ? $firstInvoice->customer : null,
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
                        $row['without_due_date_total'] += $remainingAmount;
                        continue;
                    }

                    $dueAt = Carbon::parse($invoice->due_at)->startOfDay();

                    if ($row['oldest_due_at'] === null || $dueAt->lt($row['oldest_due_at'])) {
                        $row['oldest_due_at'] = $dueAt->copy();
                    }

                    if ($dueAt->greaterThanOrEqualTo($reportDate)) {
                        $row['not_due_total'] += $remainingAmount;
                        continue;
                    }

                    $daysOverdue = $dueAt->diffInDays($reportDate);

                    if ($daysOverdue <= 30) {
                        $row['overdue_1_30_total'] += $remainingAmount;
                    } elseif ($daysOverdue <= 60) {
                        $row['overdue_31_60_total'] += $remainingAmount;
                    } elseif ($daysOverdue <= 90) {
                        $row['overdue_61_90_total'] += $remainingAmount;
                    } else {
                        $row['overdue_more_than_90_total'] += $remainingAmount;
                    }
                }

                return $row;
            })
            ->sortByDesc('remaining_total')
            ->values();

        $summary = [
            'customers_count' => $rows->count(),
            'invoice_count' => $invoices->count(),
            'remaining_total' => round((float) $invoices->sum('remaining_amount'), 2),
            'overdue_total' => round((float) $rows->sum(function ($row) {
                return (float) $row['overdue_1_30_total']
                    + (float) $row['overdue_31_60_total']
                    + (float) $row['overdue_61_90_total']
                    + (float) $row['overdue_more_than_90_total'];
            }), 2),
        ];

        return [
            'reportDate' => $reportDate,
            'invoices' => $invoices,
            'rows' => $rows,
            'summary' => $summary,
            'customerFilterLabel' => $this->customerFilterLabel($request),
            'agingBucketFilterLabel' => $this->agingBucketFilterLabel($request),
        ];
    }

    private function customerFilterLabel(Request $request): string
    {
        if (! $request->filled('customer_id')) {
            return 'all';
        }

        $customer = Customer::query()->find($request->input('customer_id'));

        return $customer
            ? $customer->name . ' #' . $customer->id
            : (string) $request->input('customer_id');
    }

    private function agingBucketFilterLabel(Request $request): string
    {
        if (! $request->filled('aging_bucket')) {
            return 'all';
        }

        return $this->bucketLabels()[$request->input('aging_bucket')]
            ?? (string) $request->input('aging_bucket');
    }

    private function bucketLabels(): array
    {
        return [
            'not_due' => 'غير مستحقة بعد',
            'overdue_1_30' => 'متأخرة 1 إلى 30 يوم',
            'overdue_31_60' => 'متأخرة 31 إلى 60 يوم',
            'overdue_61_90' => 'متأخرة 61 إلى 90 يوم',
            'overdue_more_than_90' => 'أكثر من 90 يوم',
            'without_due_date' => 'بدون تاريخ استحقاق',
        ];
    }

    private function applyAgingBucketFilter($query, string $bucket, Carbon $reportDate): void
    {
        match ($bucket) {
            'not_due' => $query->whereNotNull('due_at')->whereDate('due_at', '>=', $reportDate->toDateString()),
            'overdue_1_30' => $query->whereNotNull('due_at')->whereDate('due_at', '<', $reportDate->toDateString())->whereDate('due_at', '>=', $reportDate->copy()->subDays(30)->toDateString()),
            'overdue_31_60' => $query->whereNotNull('due_at')->whereDate('due_at', '<', $reportDate->copy()->subDays(30)->toDateString())->whereDate('due_at', '>=', $reportDate->copy()->subDays(60)->toDateString()),
            'overdue_61_90' => $query->whereNotNull('due_at')->whereDate('due_at', '<', $reportDate->copy()->subDays(60)->toDateString())->whereDate('due_at', '>=', $reportDate->copy()->subDays(90)->toDateString()),
            'overdue_more_than_90' => $query->whereNotNull('due_at')->whereDate('due_at', '<', $reportDate->copy()->subDays(90)->toDateString()),
            'without_due_date' => $query->whereNull('due_at'),
            default => null,
        };
    }
}
