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

        if ($request->filled('aging_bucket')) {
            $this->applyAgingBucketFilter($baseQuery, $request->input('aging_bucket'), $today);
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
            'agingBucketFilter' => $request->input('aging_bucket'),
        ]);
    }
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $today = now()->toDateString();

        $baseQuery = SalesInvoice::query()
            ->with(['customer', 'branch'])
            ->where('remaining_amount', '>', 0);

        if ($request->filled('customer_id')) {
            $baseQuery->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('payment_status')) {
            $baseQuery->where('payment_status', $request->input('payment_status'));
        }

        if ($request->filled('aging_bucket')) {
            $this->applyAgingBucketFilter($baseQuery, $request->input('aging_bucket'), $today);
        }

        $invoices = (clone $baseQuery)
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->latest('id')
            ->get();

        $customerFilterLabel = 'all';

        if ($request->filled('customer_id')) {
            $customer = Customer::query()->find($request->input('customer_id'));
            $customerFilterLabel = $customer
                ? $customer->name . ' #' . $customer->id
                : (string) $request->input('customer_id');
        }

        $paymentStatusLabels = [
            'unpaid' => 'غير مدفوعة',
            'partial' => 'مدفوعة جزئيًا',
            'paid' => 'مدفوعة بالكامل',
        ];

        $exportFilters = [
            'customer_id' => $customerFilterLabel,
            'payment_status' => $request->filled('payment_status')
                ? ($paymentStatusLabels[$request->input('payment_status')] ?? $request->input('payment_status'))
                : 'all',
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'today' => $today,
        ];

        $bucketSummary = [
            'not_due' => ['label' => 'غير مستحقة بعد', 'count' => 0, 'total' => 0.0],
            'overdue_1_30' => ['label' => 'متأخرة 1 إلى 30 يوم', 'count' => 0, 'total' => 0.0],
            'overdue_31_60' => ['label' => 'متأخرة 31 إلى 60 يوم', 'count' => 0, 'total' => 0.0],
            'overdue_61_90' => ['label' => 'متأخرة 61 إلى 90 يوم', 'count' => 0, 'total' => 0.0],
            'overdue_more_than_90' => ['label' => 'أكثر من 90 يوم', 'count' => 0, 'total' => 0.0],
            'without_due_date' => ['label' => 'بدون تاريخ استحقاق', 'count' => 0, 'total' => 0.0],
        ];

        foreach ($invoices as $invoice) {
            $bucketKey = 'without_due_date';

            if ($invoice->due_at) {
                if ($invoice->due_at->toDateString() >= $today) {
                    $bucketKey = 'not_due';
                } else {
                    $daysOverdue = $invoice->due_at->diffInDays(now());

                    if ($daysOverdue <= 30) {
                        $bucketKey = 'overdue_1_30';
                    } elseif ($daysOverdue <= 60) {
                        $bucketKey = 'overdue_31_60';
                    } elseif ($daysOverdue <= 90) {
                        $bucketKey = 'overdue_61_90';
                    } else {
                        $bucketKey = 'overdue_more_than_90';
                    }
                }
            }

            $bucketSummary[$bucketKey]['count']++;
            $bucketSummary[$bucketKey]['total'] += (float) $invoice->remaining_amount;
        }

        $fileName = 'sales-invoice-aging-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($invoices, $bucketSummary, $exportFilters): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['تقرير أعمار ذمم فواتير المبيعات']);
            fputcsv($handle, ['تاريخ إنشاء التقرير', $exportFilters['generated_at']]);
            fputcsv($handle, ['تاريخ التقرير', $exportFilters['today']]);
            fputcsv($handle, ['فلتر العميل', $exportFilters['customer_id']]);
            fputcsv($handle, ['فلتر حالة الدفع', $exportFilters['payment_status']]);
            fputcsv($handle, []);

            fputcsv($handle, ['ملخص شرائح الأعمار']);
            fputcsv($handle, ['الشريحة', 'عدد الفواتير', 'إجمالي المتبقي']);

            foreach ($bucketSummary as $bucket) {
                fputcsv($handle, [
                    $bucket['label'],
                    $bucket['count'],
                    number_format((float) $bucket['total'], 2, '.', ''),
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                'رقم الفاتورة',
                'العميل',
                'الفرع',
                'حالة الدفع',
                'إجمالي الفاتورة',
                'المدفوع',
                'المتبقي',
                'تاريخ الاستحقاق',
                'الشريحة',
            ]);

            $totalRemaining = 0.0;

            foreach ($invoices as $invoice) {
                $bucketLabel = 'بدون تاريخ استحقاق';

                if ($invoice->due_at) {
                    if ($invoice->due_at->toDateString() >= $exportFilters['today']) {
                        $bucketLabel = 'غير مستحقة بعد';
                    } else {
                        $daysOverdue = $invoice->due_at->diffInDays(now());

                        if ($daysOverdue <= 30) {
                            $bucketLabel = 'متأخرة 1 إلى 30 يوم';
                        } elseif ($daysOverdue <= 60) {
                            $bucketLabel = 'متأخرة 31 إلى 60 يوم';
                        } elseif ($daysOverdue <= 90) {
                            $bucketLabel = 'متأخرة 61 إلى 90 يوم';
                        } else {
                            $bucketLabel = 'أكثر من 90 يوم';
                        }
                    }
                }

                $remainingAmount = (float) $invoice->remaining_amount;
                $totalRemaining += $remainingAmount;

                fputcsv($handle, [
                    $invoice->invoice_number,
                    $invoice->customer?->name ?: '',
                    $invoice->branch?->name ?: '',
                    $invoice->displayPaymentStatus(),
                    number_format((float) $invoice->grand_total, 2, '.', ''),
                    number_format((float) $invoice->paid_amount, 2, '.', ''),
                    number_format($remainingAmount, 2, '.', ''),
                    $invoice->due_at?->format('Y-m-d') ?: '',
                    $bucketLabel,
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                'إجمالي الفواتير المفتوحة',
                $invoices->count(),
                'إجمالي المتبقي',
                number_format($totalRemaining, 2, '.', ''),
            ]);

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }


    private function applyAgingBucketFilter(\Illuminate\Database\Eloquent\Builder $query, ?string $bucket, string $today): void
    {
        match ($bucket) {
            'not_due' => $query
                ->whereNotNull('due_at')
                ->whereDate('due_at', '>=', $today),

            'overdue_1_30' => $query
                ->whereNotNull('due_at')
                ->whereDate('due_at', '>=', now()->subDays(30)->toDateString())
                ->whereDate('due_at', '<', $today),

            'overdue_31_60' => $query
                ->whereNotNull('due_at')
                ->whereDate('due_at', '>=', now()->subDays(60)->toDateString())
                ->whereDate('due_at', '<', now()->subDays(30)->toDateString()),

            'overdue_61_90' => $query
                ->whereNotNull('due_at')
                ->whereDate('due_at', '>=', now()->subDays(90)->toDateString())
                ->whereDate('due_at', '<', now()->subDays(60)->toDateString()),

            'overdue_more_than_90' => $query
                ->whereNotNull('due_at')
                ->whereDate('due_at', '<', now()->subDays(90)->toDateString()),

            'without_due_date' => $query
                ->whereNull('due_at'),

            default => null,
        };
    }


}
