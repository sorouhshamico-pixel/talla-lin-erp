<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Services\CustomerSalesInvoiceAgingReportBuilder;
use App\Services\ReportFilterPreferenceService;
use App\Services\ReportSavedViewService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerSalesInvoiceAgingReportController extends Controller
{
    private const REPORT_KEY = 'customer-sales-invoice-aging';

    private const FILTER_KEYS = ['customer_id', 'aging_bucket'];

    private const AGING_BUCKETS = [
        'not_due',
        'overdue_1_30',
        'overdue_31_60',
        'overdue_61_90',
        'overdue_more_than_90',
        'without_due_date',
    ];

    public function index(Request $request, ReportFilterPreferenceService $filterPreferences, ReportSavedViewService $savedViews): View
    {
        $request = $this->requestWithDefaultSavedView($request, $savedViews);
        $request = $this->requestWithFilterPreferences($request, $filterPreferences, true);

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
            ->orderByRaw('CASE WHEN due_at ISNULL THEN 1 ELSE 0 END')
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

    public function print(Request $request, ReportFilterPreferenceService $filterPreferences)
    {
        $request = $this->requestWithFilterPreferences($request, $filterPreferences, false);

        $report = app(CustomerSalesInvoiceAgingReportBuilder::class)->build($request);

        return view('reports.customer-sales-invoice-aging-print', [
            'reportDate' => $report['reportDate'],
            'rows' => $report['rows'],
            'summary' => $report['summary'],
            'customerFilterLabel' => $report['customerFilterLabel'],
            'agingBucketFilterLabel' => $report['agingBucketFilterLabel'],
        ]);
    }

    public function export(Request $request, ReportFilterPreferenceService $filterPreferences)
    {
        $request = $this->requestWithFilterPreferences($request, $filterPreferences, false);

        $report = app(CustomerSalesInvoiceAgingReportBuilder::class)->build($request);

        $fileName = 'customer-sales-invoice-aging-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, chr(239) . chr(187) . chr(191));

            fputcsv($handle, ['تقرير أعمار ذمم العملاء']);
            fputcsv($handle, ['تاريخ إنشاء التقرير', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, ['تاريخ التقرير', $report['reportDate']->format('Y-m-d')]);
            fputcsv($handle, ['فلتر العميل', $report['customerFilterLabel']]);
            fputcsv($handle, ['فلتر شريحة العمر', $report['agingBucketFilterLabel']]);
            fputcsv($handle, []);

            fputcsv($handle, ['ملخص عام']);
            fputcsv($handle, ['عدد العملاء', $report['summary']['customers_count']]);
            fputcsv($handle, ['عدد الفواتير المفتوحة', $report['summary']['invoice_count']]);
            fputcsv($handle, ['إجمالي الذمم المفتوحة', number_format((float) $report['summary']['remaining_total'], 2, '.', '')]);
            fputcsv($handle, ['إجمالي المتأخر', number_format((float) $report['summary']['overdue_total'], 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'العميل',
                'عدد الفواتير',
                'إجمالي المتبقي',
                'غير مستحقة بعد',
                'متأخرة 1 إلى 30',
                'متأخرة 31 إلى 60',
                'متأخرة 61 إلى 90',
                'أكثر من 90',
                'بدون تاريخ استحقاق',
                'أقدم استحقاق',
            ]);

            foreach ($report['rows'] as $row) {
                fputcsv($handle, [
                    $row['customer'] ? $row['customer']->name : '',
                    $row['invoice_count'],
                    number_format((float) $row['remaining_total'], 2, '.', ''),
                    number_format((float) $row['not_due_total'], 2, '.', ''),
                    number_format((float) $row['overdue_1_30_total'], 2, '.', ''),
                    number_format((float) $row['overdue_31_60_total'], 2, '.', ''),
                    number_format((float) $row['overdue_61_90_total'], 2, '.', ''),
                    number_format((float) $row['overdue_more_than_90_total'], 2, '.', ''),
                    number_format((float) $row['without_due_date_total'], 2, '.', ''),
                    $row['oldest_due_at'] ? $row['oldest_due_at']->format('Y-m-d') : '',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }


    public function storeSavedView(Request $request, ReportSavedViewService $savedViews): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'customer_id' => ['nullable', 'integer'],
            'aging_bucket' => ['nullable', 'string', 'in:not_due,overdue_1_30,overdue_31_60,overdue_61_90,overdue_more_than_90,without_due_date'],
            'is_default' => ['nullable'],
        ]);

        $filters = array_filter([
            'customer_id' => $validated['customer_id'] ?? null,
            'aging_bucket' => $validated['aging_bucket'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $savedViews->save(
            $request->user(),
            self::REPORT_KEY,
            $validated['name'],
            $filters,
            $request->boolean('is_default')
        );

        return redirect()
            ->route('reports.customer-sales-invoice-aging.index', $filters)
            ->with('status', 'تم حفظ عرض التقرير بنجاح.');
    }


    private function requestWithDefaultSavedView(Request $request, ReportSavedViewService $savedViews): Request
    {
        if ($request->boolean('reset_filters')) {
            return $request;
        }

        foreach (self::FILTER_KEYS as $key) {
            if ($request->filled($key)) {
                return $request;
            }
        }

        $user = $request->user();

        if (! $user) {
            return $request;
        }

        $defaultSavedView = $savedViews->getDefault($user, self::REPORT_KEY);

        if (! $defaultSavedView) {
            return $request;
        }

        $filters = array_filter(
            $defaultSavedView->filters ?? [],
            fn ($value) => $value !== null && $value !== ''
        );

        if ($filters === []) {
            return $request;
        }

        return $request->merge($filters);
    }

    private function requestWithFilterPreferences(Request $request, ReportFilterPreferenceService $filterPreferences, bool $persist): Request
    {
        $user = $request->user();

        if (! $user) {
            return $request;
        }

        if ($request->query->has('reset_filters')) {
            if ($persist) {
                $filterPreferences->clear($user, self::REPORT_KEY);
            }

            foreach (self::FILTER_KEYS as $key) {
                $request->query->remove($key);
                $request->request->remove($key);
            }

            return $request;
        }

        if ($this->hasFilterInput($request)) {
            if ($persist) {
                $filterPreferences->save($user, self::REPORT_KEY, $this->filterInputs($request));
            }

            return $request;
        }

        $savedFilters = $filterPreferences->get($user, self::REPORT_KEY);

        if ($savedFilters !== []) {
            $request->query->add($savedFilters);
            $request->merge($savedFilters);
        }

        return $request;
    }

    private function hasFilterInput(Request $request): bool
    {
        foreach (self::FILTER_KEYS as $key) {
            if ($request->query->has($key) || $request->request->has($key)) {
                return true;
            }
        }

        return false;
    }

    private function filterInputs(Request $request): array
    {
        return array_filter([
            'customer_id' => $request->integer('customer_id') ?: null,
            'aging_bucket' => $this->agingBucketInput($request),
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function agingBucketInput(Request $request): ?string
    {
        $bucket = $request->input('aging_bucket');

        if (! is_string($bucket) || $bucket === '') {
            return null;
        }

        return in_array($bucket, self::AGING_BUCKETS, true) ? $bucket : null;
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
