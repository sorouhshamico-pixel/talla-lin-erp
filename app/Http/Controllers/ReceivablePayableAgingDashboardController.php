<?php

namespace App\Http\Controllers;

use App\Services\CustomerSalesInvoiceAgingReportBuilder;
use App\Services\ReportFilterPreferenceService;
use App\Services\ReportSavedViewService;
use App\Services\SupplierPurchaseInvoiceAgingReportBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReceivablePayableAgingDashboardController extends Controller
{
    private const REPORT_KEY = 'receivable-payable-aging-dashboard';

    private const FILTER_KEYS = ['branch_id', 'as_of_date'];

    public function index(
        Request $request,
        CustomerSalesInvoiceAgingReportBuilder $customerAgingBuilder,
        SupplierPurchaseInvoiceAgingReportBuilder $supplierAgingBuilder,
        ReportFilterPreferenceService $filterPreferences,
        ReportSavedViewService $savedViews
    ): View {
        $request = $this->requestWithFilterPreferences($request, $filterPreferences, true);
        $request = $this->requestWithDefaultSavedView($request, $savedViews);

        $customerAging = $customerAgingBuilder->build($request);
        $supplierAging = $supplierAgingBuilder->build($request);
        $dashboardData = $this->dashboardData($customerAging, $supplierAging, $request);

        return view('reports.receivable-payable-aging-dashboard', array_merge($dashboardData, [
            'savedViews' => $savedViews->listForReport($request->user(), self::REPORT_KEY),
        ]));
    }

    public function print(
        Request $request,
        CustomerSalesInvoiceAgingReportBuilder $customerAgingBuilder,
        SupplierPurchaseInvoiceAgingReportBuilder $supplierAgingBuilder,
        ReportFilterPreferenceService $filterPreferences
    ): View {
        $request = $this->requestWithFilterPreferences($request, $filterPreferences, false);

        $customerAging = $customerAgingBuilder->build($request);
        $supplierAging = $supplierAgingBuilder->build($request);

        return view('reports.receivable-payable-aging-dashboard-print', $this->dashboardData($customerAging, $supplierAging, $request));
    }

    public function export(
        Request $request,
        CustomerSalesInvoiceAgingReportBuilder $customerAgingBuilder,
        SupplierPurchaseInvoiceAgingReportBuilder $supplierAgingBuilder,
        ReportFilterPreferenceService $filterPreferences
    ) {
        $request = $this->requestWithFilterPreferences($request, $filterPreferences, false);

        $customerAging = $customerAgingBuilder->build($request);
        $supplierAging = $supplierAgingBuilder->build($request);

        $data = $this->dashboardData($customerAging, $supplierAging, $request);

        $fileName = 'receivable-payable-aging-dashboard-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, chr(239) . chr(187) . chr(191));

            fputcsv($handle, ['لوحة أعمار الذمم']);
            fputcsv($handle, ['تاريخ إنشاء التقرير', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, ['تاريخ التقرير', $data['reportDate']->format('Y-m-d')]);
            fputcsv($handle, ['الفرع', $data['selectedBranchName'] ?? 'كل الفروع']);
            fputcsv($handle, ['تاريخ التقرير المحدد', $data['selectedAsOfDate']]);
            fputcsv($handle, []);

            fputcsv($handle, ['ملخص ذمم العملاء']);
            fputcsv($handle, ['عدد العملاء', $data['customerSummary']['customers_count']]);
            fputcsv($handle, ['فواتير العملاء المفتوحة', $data['customerSummary']['invoice_count']]);
            fputcsv($handle, ['إجمالي ذمم العملاء المفتوحة', number_format((float) $data['customerSummary']['remaining_total'], 2, '.', '')]);
            fputcsv($handle, ['إجمالي المتأخر على العملاء', number_format((float) $data['customerSummary']['overdue_total'], 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, ['ملخص ذمم الموردين']);
            fputcsv($handle, ['عدد الموردين', $data['supplierSummary']['suppliers_count']]);
            fputcsv($handle, ['فواتير الموردين المفتوحة', $data['supplierSummary']['invoice_count']]);
            fputcsv($handle, ['إجمالي ذمم الموردين المفتوحة', number_format((float) $data['supplierSummary']['remaining_total'], 2, '.', '')]);
            fputcsv($handle, ['إجمالي المتأخر للموردين', number_format((float) $data['supplierSummary']['overdue_total'], 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, ['صافي الذمم']);
            fputcsv($handle, ['صافي الذمم المفتوحة', number_format((float) $data['netSummary']['net_open_total'], 2, '.', '')]);
            fputcsv($handle, ['حالة صافي الذمم', $data['netSummary']['position_label']]);
            fputcsv($handle, ['صافي المتأخرات', number_format((float) $data['netSummary']['net_overdue_total'], 2, '.', '')]);
            fputcsv($handle, ['حالة صافي المتأخرات', $data['netSummary']['overdue_position_label']]);
            fputcsv($handle, []);

            fputcsv($handle, ['مقارنة شرائح الأعمار']);
            fputcsv($handle, ['شريحة العمر', 'ذمم العملاء', 'ذمم الموردين', 'صافي الفرق']);

            foreach ($data['bucketComparison'] as $bucket) {
                fputcsv($handle, [
                    $bucket['label'],
                    number_format((float) $bucket['customer_total'], 2, '.', ''),
                    number_format((float) $bucket['supplier_total'], 2, '.', ''),
                    number_format((float) $bucket['net_total'], 2, '.', ''),
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
            'branch_id' => ['nullable', 'integer'],
            'as_of_date' => ['nullable', 'date'],
            'is_default' => ['nullable'],
        ]);

        $filters = array_filter([
            'branch_id' => $validated['branch_id'] ?? null,
            'as_of_date' => $validated['as_of_date'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $savedViews->save(
            $request->user(),
            self::REPORT_KEY,
            $validated['name'],
            $filters,
            $request->boolean('is_default')
        );

        return redirect()
            ->route('reports.receivable-payable-aging-dashboard.index', $filters)
            ->with('status', 'تم حفظ عرض لوحة أعمار الذمم بنجاح.');
    }

    private function requestWithDefaultSavedView(Request $request, ReportSavedViewService $savedViews): Request
    {
        if ($request->query->has('reset_filters')) {
            return $request;
        }

        if ($this->hasFilterInput($request)) {
            return $request;
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

        $request->query->add($filters);

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
            'branch_id' => $request->integer('branch_id') ?: null,
            'as_of_date' => $this->dateInput($request, 'as_of_date'),
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function dashboardData(array $customerAging, array $supplierAging, Request $request): array
    {
        $selectedBranchId = $request->integer('branch_id') ?: null;
        $selectedAsOfDateParam = $this->dateInput($request, 'as_of_date');
        $selectedAsOfDate = $selectedAsOfDateParam ?: now()->format('Y-m-d');

        $branches = DB::table('branches')->orderBy('name')->get(['id', 'name']);
        $selectedBranchName = $selectedBranchId
            ? optional($branches->firstWhere('id', $selectedBranchId))->name
            : null;

        $customerRemainingTotal = round((float) $customerAging['summary']['remaining_total'], 2);
        $supplierRemainingTotal = round((float) $supplierAging['summary']['remaining_total'], 2);
        $customerOverdueTotal = round((float) $customerAging['summary']['overdue_total'], 2);
        $supplierOverdueTotal = round((float) $supplierAging['summary']['overdue_total'], 2);

        $netOpenTotal = round($customerRemainingTotal - $supplierRemainingTotal, 2);
        $netOverdueTotal = round($customerOverdueTotal - $supplierOverdueTotal, 2);

        $filterParams = array_filter([
            'branch_id' => $selectedBranchId,
            'as_of_date' => $selectedAsOfDateParam,
        ]);

        return [
            'reportDate' => $this->reportDate($request),
            'branches' => $branches,
            'selectedBranchId' => $selectedBranchId,
            'selectedBranchName' => $selectedBranchName,
            'selectedAsOfDate' => $selectedAsOfDate,
            'filterParams' => $filterParams,
            'drilldownParams' => $filterParams,
            'customerSummary' => $customerAging['summary'],
            'supplierSummary' => $supplierAging['summary'],
            'netSummary' => [
                'net_open_total' => $netOpenTotal,
                'position_label' => $netOpenTotal >= 0 ? 'صافي لصالح الشركة' : 'صافي لصالح الموردين',
                'net_overdue_total' => $netOverdueTotal,
                'overdue_position_label' => $netOverdueTotal >= 0 ? 'متأخرات لصالح الشركة' : 'متأخرات لصالح الموردين',
            ],
            'bucketComparison' => $this->bucketComparison($customerAging['rows'], $supplierAging['rows']),
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

    private function bucketComparison(Collection $customerRows, Collection $supplierRows): array
    {
        $buckets = [
            'not_due_total' => 'غير مستحقة بعد',
            'overdue_1_30_total' => 'متأخرة 1 إلى 30',
            'overdue_31_60_total' => 'متأخرة 31 إلى 60',
            'overdue_61_90_total' => 'متأخرة 61 إلى 90',
            'overdue_more_than_90_total' => 'أكثر من 90',
            'without_due_date_total' => 'بدون تاريخ استحقاق',
        ];

        return collect($buckets)
            ->map(function (string $label, string $key) use ($customerRows, $supplierRows): array {
                $customerTotal = round((float) $customerRows->sum(fn ($row) => (float) $row[$key]), 2);
                $supplierTotal = round((float) $supplierRows->sum(fn ($row) => (float) $row[$key]), 2);

                return [
                    'key' => $key,
                    'label' => $label,
                    'customer_total' => $customerTotal,
                    'supplier_total' => $supplierTotal,
                    'net_total' => round($customerTotal - $supplierTotal, 2),
                ];
            })
            ->values()
            ->all();
    }
}
