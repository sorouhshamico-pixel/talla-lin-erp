<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Services\ReportFilterPreferenceService;
use App\Services\ReportSavedViewService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesInvoiceAgingReportController extends Controller
{
    private const REPORT_KEY = 'sales-invoice-aging';

    private const FILTER_KEYS = ['customer_id', 'payment_status', 'aging_bucket'];

    private const PAYMENT_STATUSES = ['unpaid', 'partial', 'paid'];

    private const PAYMENT_STATUS_LABELS = [
        'unpaid' => 'غير مدفوعة',
        'partial' => 'مدفوعة جزئيًا',
        'paid' => 'مدفوعة بالكامل',
    ];

    private const AGING_BUCKETS = [
        'not_due',
        'overdue_1_30',
        'overdue_31_60',
        'overdue_61_90',
        'overdue_more_than_90',
        'without_due_date',
    ];

    private const AGING_BUCKET_LABELS = [
        'not_due' => 'غير مستحقة بعد',
        'overdue_1_30' => 'متأخرة 1 إلى 30 يوم',
        'overdue_31_60' => 'متأخرة 31 إلى 60 يوم',
        'overdue_61_90' => 'متأخرة 61 إلى 90 يوم',
        'overdue_more_than_90' => 'أكثر من 90 يوم',
        'without_due_date' => 'بدون تاريخ استحقاق',
    ];

    public function index(Request $request, ReportFilterPreferenceService $filterPreferences): View
    {
        $request = $this->requestWithFilterPreferences($request, $filterPreferences, true);

        $today = now()->toDateString();

        $customers = Customer::query()
            ->orderBy('name')
            ->get();

        $baseQuery = $this->filteredInvoiceQuery($request, $today);

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
                'label' => self::AGING_BUCKET_LABELS['not_due'],
                'count' => (clone $notDueQuery)->count(),
                'total' => round((float) (clone $notDueQuery)->sum('remaining_amount'), 2),
            ],
            'overdue_1_30' => [
                'label' => self::AGING_BUCKET_LABELS['overdue_1_30'],
                'count' => (clone $overdue1To30Query)->count(),
                'total' => round((float) (clone $overdue1To30Query)->sum('remaining_amount'), 2),
            ],
            'overdue_31_60' => [
                'label' => self::AGING_BUCKET_LABELS['overdue_31_60'],
                'count' => (clone $overdue31To60Query)->count(),
                'total' => round((float) (clone $overdue31To60Query)->sum('remaining_amount'), 2),
            ],
            'overdue_61_90' => [
                'label' => self::AGING_BUCKET_LABELS['overdue_61_90'],
                'count' => (clone $overdue61To90Query)->count(),
                'total' => round((float) (clone $overdue61To90Query)->sum('remaining_amount'), 2),
            ],
            'overdue_more_than_90' => [
                'label' => self::AGING_BUCKET_LABELS['overdue_more_than_90'],
                'count' => (clone $overdueMoreThan90Query)->count(),
                'total' => round((float) (clone $overdueMoreThan90Query)->sum('remaining_amount'), 2),
            ],
            'without_due_date' => [
                'label' => self::AGING_BUCKET_LABELS['without_due_date'],
                'count' => (clone $withoutDueDateQuery)->count(),
                'total' => round((float) (clone $withoutDueDateQuery)->sum('remaining_amount'), 2),
            ],
        ];

        $totalOutstanding = round((float) (clone $baseQuery)->sum('remaining_amount'), 2);
        $totalCount = (clone $baseQuery)->count();

        $invoices = (clone $baseQuery)
            ->orderByRaw('CASE WHEN due_at ISNULL THEN 1 ELSE 0 END')
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

    public function export(Request $request, ReportFilterPreferenceService $filterPreferences): StreamedResponse
    {
        $request = $this->requestWithFilterPreferences($request, $filterPreferences, false);

        $today = now()->toDateString();

        $baseQuery = $this->filteredInvoiceQuery($request, $today);

        $invoices = (clone $baseQuery)
            ->orderByRaw('CASE WHEN due_at ISNULL THEN 1 ELSE 0 END')
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

        $exportFilters = [
            'customer_id' => $customerFilterLabel,
            'payment_status' => $request->filled('payment_status')
                ? (self::PAYMENT_STATUS_LABELS[$request->input('payment_status')] ?? $request->input('payment_status'))
                : 'all',
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'today' => $today,
        ];

        $bucketSummary = [
            'not_due' => ['label' => self::AGING_BUCKET_LABELS['not_due'], 'count' => 0, 'total' => 0.0],
            'overdue_1_30' => ['label' => self::AGING_BUCKET_LABELS['overdue_1_30'], 'count' => 0, 'total' => 0.0],
            'overdue_31_60' => ['label' => self::AGING_BUCKET_LABELS['overdue_31_60'], 'count' => 0, 'total' => 0.0],
            'overdue_61_90' => ['label' => self::AGING_BUCKET_LABELS['overdue_61_90'], 'count' => 0, 'total' => 0.0],
            'overdue_more_than_90' => ['label' => self::AGING_BUCKET_LABELS['overdue_more_than_90'], 'count' => 0, 'total' => 0.0],
            'without_due_date' => ['label' => self::AGING_BUCKET_LABELS['without_due_date'], 'count' => 0, 'total' => 0.0],
        ];

        foreach ($invoices as $invoice) {
            $bucketKey = $this->bucketKeyForInvoice($invoice, $today);
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
                $bucketLabel = self::AGING_BUCKET_LABELS[$this->bucketKeyForInvoice($invoice, $exportFilters['today'])];
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


    public function storeSavedView(Request $request, ReportSavedViewService $savedViews): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'customer_id' => ['nullable', 'integer'],
            'payment_status' => ['nullable', 'string', 'in:unpaid,partial,paid'],
            'aging_bucket' => ['nullable', 'string', 'in:not_due,overdue_1_30,overdue_31_60,overdue_61_90,overdue_more_than_90,without_due_date'],
            'is_default' => ['nullable'],
        ]);

        $filters = array_filter([
            'customer_id' => $validated['customer_id'] ?? null,
            'payment_status' => $validated['payment_status'] ?? null,
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
            ->route('reports.sales-invoice-aging.index', $filters)
            ->with('status', 'تم حفظ عرض التقرير بنجاح.');
    }

    private function filteredInvoiceQuery(Request $request, string $today): Builder
    {
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

        return $baseQuery;
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
            'payment_status' => $this->paymentStatusInput($request),
            'aging_bucket' => $this->agingBucketInput($request),
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function paymentStatusInput(Request $request): ?string
    {
        $status = $request->input('payment_status');

        if (! is_string($status) || $status === '') {
            return null;
        }

        return in_array($status, self::PAYMENT_STATUSES, true) ? $status : null;
    }

    private function agingBucketInput(Request $request): ?string
    {
        $bucket = $request->input('aging_bucket');

        if (! is_string($bucket) || $bucket === '') {
            return null;
        }

        return in_array($bucket, self::AGING_BUCKETS, true) ? $bucket : null;
    }

    private function bucketKeyForInvoice(SalesInvoice $invoice, string $today): string
    {
        if (! $invoice->due_at) {
            return 'without_due_date';
        }

        if ($invoice->due_at->toDateString() >= $today) {
            return 'not_due';
        }

        $daysOverdue = $invoice->due_at->diffInDays(now());

        if ($daysOverdue <= 30) {
            return 'overdue_1_30';
        }

        if ($daysOverdue <= 60) {
            return 'overdue_31_60';
        }

        if ($daysOverdue <= 90) {
            return 'overdue_61_90';
        }

        return 'overdue_more_than_90';
    }

    private function applyAgingBucketFilter(Builder $query, ?string $bucket, string $today): void
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

            'without_due_date' => $query->whereNull('due_at'),

            default => null,
        };
    }
}
