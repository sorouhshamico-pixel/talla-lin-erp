<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Services\ReportFilterPreferenceService;
use App\Services\ReportSavedViewService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupplierPurchaseInvoiceAgingDrilldownController extends Controller
{
    private const REPORT_KEY = 'supplier-purchase-invoice-aging-drilldown';

    private const FILTER_KEYS = ['supplier_id', 'branch_id', 'as_of_date', 'aging_bucket'];

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

        return view('reports.supplier-purchase-invoice-aging-drilldown', $this->drilldownData($request));
    }

    public function export(Request $request, ReportFilterPreferenceService $filterPreferences)
    {
        $request = $this->requestWithFilterPreferences($request, $filterPreferences, false);

        $data = $this->drilldownData($request);

        $fileName = 'supplier-purchase-invoice-aging-drilldown-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, chr(239) . chr(187) . chr(191));

            fputcsv($handle, ['تفاصيل فواتير الموردين المفتوحة']);
            fputcsv($handle, ['تاريخ إنشاء التقرير', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, ['تاريخ التقرير', $data['reportDate']->format('Y-m-d')]);
            fputcsv($handle, ['فلتر المورد', $data['selectedSupplierLabel']]);
            fputcsv($handle, ['فلتر الفرع', $data['selectedBranchLabel']]);
            fputcsv($handle, ['فلتر شريحة العمر', $data['selectedAgingBucketLabel']]);
            fputcsv($handle, []);

            fputcsv($handle, ['ملخص']);
            fputcsv($handle, ['عدد الفواتير المفتوحة', $data['summary']['invoice_count']]);
            fputcsv($handle, ['إجمالي الفواتير', number_format((float) $data['summary']['grand_total'], 2, '.', '')]);
            fputcsv($handle, ['إجمالي المدفوع', number_format((float) $data['summary']['paid_total'], 2, '.', '')]);
            fputcsv($handle, ['إجمالي المتبقي', number_format((float) $data['summary']['remaining_total'], 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'رقم الفاتورة',
                'المورد',
                'تاريخ الإصدار',
                'تاريخ الاستحقاق',
                'الإجمالي',
                'المدفوع',
                'المتبقي',
                'حالة الدفع',
            ]);

            foreach ($data['invoices'] as $invoice) {
                fputcsv($handle, [
                    $invoice->invoice_number,
                    $data['supplierNames'][$invoice->supplier_id] ?? '',
                    $invoice->issued_at ? Carbon::parse($invoice->issued_at)->format('Y-m-d') : '',
                    $invoice->due_at ? Carbon::parse($invoice->due_at)->format('Y-m-d') : '',
                    number_format((float) $invoice->grand_total, 2, '.', ''),
                    number_format((float) $invoice->paid_amount, 2, '.', ''),
                    number_format((float) $invoice->remaining_amount, 2, '.', ''),
                    $invoice->payment_status,
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
            'supplier_id' => ['nullable', 'integer'],
            'branch_id' => ['nullable', 'integer'],
            'as_of_date' => ['nullable', 'date'],
            'aging_bucket' => ['nullable', 'string', 'in:not_due,overdue_1_30,overdue_31_60,overdue_61_90,overdue_more_than_90,without_due_date'],
            'is_default' => ['nullable'],
        ]);

        $filters = array_filter([
            'supplier_id' => $validated['supplier_id'] ?? null,
            'branch_id' => $validated['branch_id'] ?? null,
            'as_of_date' => $validated['as_of_date'] ?? null,
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
            ->route('reports.supplier-purchase-invoice-aging.drilldown', $filters)
            ->with('status', 'تم حفظ عرض التقرير بنجاح.');
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
            'supplier_id' => $request->integer('supplier_id') ?: null,
            'branch_id' => $request->integer('branch_id') ?: null,
            'as_of_date' => $this->dateInput($request, 'as_of_date'),
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

    private function drilldownData(Request $request): array
    {
        $reportDate = $this->reportDate($request);

        $supplierId = $request->integer('supplier_id') ?: null;
        $branchId = $request->integer('branch_id') ?: null;
        $agingBucket = $this->agingBucketInput($request);

        $query = PurchaseInvoice::query()
            ->where('remaining_amount', '>', 0);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $this->applyAgingBucket($query, $agingBucket, $reportDate);

        $invoices = $query
            ->orderByRaw('due_at IS NULL')
            ->orderBy('due_at')
            ->orderBy('invoice_number')
            ->get();

        $supplierNames = Supplier::query()
            ->whereIn('id', $invoices->pluck('supplier_id')->filter()->unique())
            ->pluck('name', 'id');

        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $branches = DB::table('branches')
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedSupplierName = $supplierId
            ? Supplier::query()->whereKey($supplierId)->value('name')
            : null;

        return [
            'reportDate' => $reportDate,
            'suppliers' => $suppliers,
            'branches' => $branches,
            'agingBuckets' => self::AGING_BUCKET_LABELS,
            'selectedSupplierId' => $supplierId,
            'selectedBranchId' => $branchId,
            'selectedAsOfDate' => $reportDate->format('Y-m-d'),
            'selectedAgingBucket' => $agingBucket,
            'selectedSupplierLabel' => $supplierId ? $selectedSupplierName . ' #' . $supplierId : 'كل الموردين',
            'selectedBranchLabel' => $this->branchLabel($request),
            'selectedAgingBucketLabel' => self::AGING_BUCKET_LABELS[$agingBucket] ?? 'كل الشرائح',
            'invoices' => $invoices,
            'supplierNames' => $supplierNames,
            'summary' => [
                'invoice_count' => $invoices->count(),
                'remaining_total' => round((float) $invoices->sum('remaining_amount'), 2),
                'grand_total' => round((float) $invoices->sum('grand_total'), 2),
                'paid_total' => round((float) $invoices->sum('paid_amount'), 2),
            ],
        ];
    }

    private function reportDate(Request $request): Carbon
    {
        $asOfDate = $this->dateInput($request, 'as_of_date');

        if ($asOfDate) {
            return Carbon::parse($asOfDate)->startOfDay();
        }

        return now()->startOfDay();
    }

    private function dateInput(Request $request, string $key): ?string
    {
        $value = $request->input($key);

        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function branchLabel(Request $request): string
    {
        $branchId = $request->integer('branch_id') ?: null;

        if (! $branchId) {
            return 'كل الفروع';
        }

        $name = DB::table('branches')->where('id', $branchId)->value('name');

        return $name ? $name . ' #' . $branchId : 'فرع غير معروف #' . $branchId;
    }

    private function applyAgingBucket(Builder $query, ?string $agingBucket, Carbon $reportDate): void
    {
        if (! $agingBucket) {
            return;
        }

        match ($agingBucket) {
            'not_due' => $query
                ->whereNotNull('due_at')
                ->whereDate('due_at', '>=', $reportDate->toDateString()),

            'overdue_1_30' => $query
                ->whereNotNull('due_at')
                ->whereDate('due_at', '<', $reportDate->toDateString())
                ->whereDate('due_at', '>=', $reportDate->copy()->subDays(30)->toDateString()),

            'overdue_31_60' => $query
                ->whereNotNull('due_at')
                ->whereDate('due_at', '<', $reportDate->copy()->subDays(30)->toDateString())
                ->whereDate('due_at', '>=', $reportDate->copy()->subDays(60)->toDateString()),

            'overdue_61_90' => $query
                ->whereNotNull('due_at')
                ->whereDate('due_at', '<', $reportDate->copy()->subDays(60)->toDateString())
                ->whereDate('due_at', '>=', $reportDate->copy()->subDays(90)->toDateString()),

            'overdue_more_than_90' => $query
                ->whereNotNull('due_at')
                ->whereDate('due_at', '<', $reportDate->copy()->subDays(90)->toDateString()),

            'without_due_date' => $query->whereNull('due_at'),

            default => null,
        };
    }
}
