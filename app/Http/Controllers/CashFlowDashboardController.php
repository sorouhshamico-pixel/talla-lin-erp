<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Services\ReportFilterPreferenceService;
use App\Services\ReportSavedViewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CashFlowDashboardController extends Controller
{
    private const REPORT_KEY = 'cash-flow-dashboard';

    private const FILTER_KEYS = ['branch_id', 'date_from', 'date_to'];

    public function index(
        Request $request,
        ReportFilterPreferenceService $filterPreferences,
        ReportSavedViewService $savedViews
    ): View {
        $request = $this->requestWithDefaultSavedView($request, $savedViews);
        $request = $this->requestWithFilterPreferences($request, $filterPreferences, true);

        $viewData = $this->dashboardData($request);
        $viewData['savedViews'] = $savedViews->listForReport($request->user(), self::REPORT_KEY);

        return view('reports.cash-flow-dashboard', $viewData);
    }

    public function print(Request $request, ReportFilterPreferenceService $filterPreferences): View
    {
        $request = $this->requestWithFilterPreferences($request, $filterPreferences, false);

        return view('reports.cash-flow-dashboard-print', $this->dashboardData($request));
    }

    public function export(Request $request, ReportFilterPreferenceService $filterPreferences)
    {
        $request = $this->requestWithFilterPreferences($request, $filterPreferences, false);

        $data = $this->dashboardData($request);

        $fileName = 'cash-flow-dashboard-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, chr(239) . chr(187) . chr(191));

            fputcsv($handle, ['لوحة التدفق النقدي المتوقع']);
            fputcsv($handle, ['تاريخ إنشاء التقرير', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, ['تاريخ التقرير', $data['reportDate']->format('Y-m-d')]);
            fputcsv($handle, ['الفرع', $data['selectedBranchName'] ?? 'كل الفروع']);
            fputcsv($handle, ['من تاريخ الاستحقاق', $data['selectedDateFrom'] ?? 'غير محدد']);
            fputcsv($handle, ['إلى تاريخ الاستحقاق', $data['selectedDateTo'] ?? 'غير محدد']);
            fputcsv($handle, []);

            fputcsv($handle, ['ملخص التدفقات الداخلة']);
            fputcsv($handle, ['عدد العملاء أصحاب الذمم', $data['inflowSummary']['customers_count']]);
            fputcsv($handle, ['فواتير العملاء المفتوحة', $data['inflowSummary']['open_invoice_count']]);
            fputcsv($handle, ['التدفقات الداخلة المتوقعة', number_format((float) $data['inflowSummary']['expected_inflows'], 2, '.', '')]);
            fputcsv($handle, ['تدفقات داخلة متأخرة', number_format((float) $data['inflowSummary']['overdue_inflows'], 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, ['ملخص التدفقات الخارجة']);
            fputcsv($handle, ['عدد الموردين أصحاب الذمم', $data['outflowSummary']['suppliers_count']]);
            fputcsv($handle, ['فواتير الموردين المفتوحة', $data['outflowSummary']['open_invoice_count']]);
            fputcsv($handle, ['التدفقات الخارجة المتوقعة', number_format((float) $data['outflowSummary']['expected_outflows'], 2, '.', '')]);
            fputcsv($handle, ['تدفقات خارجة متأخرة', number_format((float) $data['outflowSummary']['overdue_outflows'], 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, ['صافي التدفق النقدي']);
            fputcsv($handle, ['صافي التدفق النقدي المتوقع', number_format((float) $data['netCashSummary']['net_expected_cash'], 2, '.', '')]);
            fputcsv($handle, ['حالة التدفق النقدي المتوقع', $data['netCashSummary']['position_label']]);
            fputcsv($handle, []);

            fputcsv($handle, ['مخاطر التدفق النقدي']);
            fputcsv($handle, ['إجمالي التدفقات الداخلة المتأخرة', number_format((float) $data['riskSummary']['overdue_inflows'], 2, '.', '')]);
            fputcsv($handle, ['إجمالي التدفقات الخارجة المتأخرة', number_format((float) $data['riskSummary']['overdue_outflows'], 2, '.', '')]);
            fputcsv($handle, ['صافي الضغط النقدي المتأخر', number_format((float) $data['riskSummary']['net_overdue_pressure'], 2, '.', '')]);
            fputcsv($handle, ['حالة الضغط النقدي', $data['riskSummary']['pressure_label']]);
            fputcsv($handle, ['نسبة تغطية الالتزامات المتوقعة', $data['riskSummary']['cash_coverage_ratio'] === null ? 'غير مطبق' : number_format((float) $data['riskSummary']['cash_coverage_ratio'], 2, '.', '') . '%']);
            fputcsv($handle, ['حالة التغطية النقدية', $data['riskSummary']['coverage_label']]);
            fputcsv($handle, []);

            fputcsv($handle, ['التدفق النقدي حسب شرائح الأعمار']);
            fputcsv($handle, ['شريحة العمر', 'تدفقات داخلة متوقعة', 'تدفقات خارجة متوقعة', 'صافي التدفق النقدي']);

            foreach ($data['bucketCashFlow'] as $bucket) {
                fputcsv($handle, [
                    $bucket['label'],
                    number_format((float) $bucket['expected_inflows'], 2, '.', ''),
                    number_format((float) $bucket['expected_outflows'], 2, '.', ''),
                    number_format((float) $bucket['net_cash_flow'], 2, '.', ''),
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
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'is_default' => ['nullable'],
        ]);

        $filters = array_filter([
            'branch_id' => $validated['branch_id'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $savedViews->save(
            $request->user(),
            self::REPORT_KEY,
            $validated['name'],
            $filters,
            $request->boolean('is_default')
        );

        return redirect()
            ->route('reports.cash-flow-dashboard.index', $filters)
            ->with('status', 'تم حفظ عرض اللوحة بنجاح.');
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
            'branch_id' => $request->integer('branch_id') ?: null,
            'date_from' => $this->dateInput($request, 'date_from'),
            'date_to' => $this->dateInput($request, 'date_to'),
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function dashboardData(Request $request): array
    {
        $reportDate = $this->reportDate($request);

        $customerInvoices = $this->filteredSalesInvoices($request);
        $supplierInvoices = $this->filteredPurchaseInvoices($request);

        $customerRows = $this->groupSalesInvoicesByCustomer($customerInvoices, $reportDate);
        $supplierRows = $this->groupPurchaseInvoicesBySupplier($supplierInvoices, $reportDate);

        $expectedInflows = round((float) $customerInvoices->sum('remaining_amount'), 2);
        $expectedOutflows = round((float) $supplierInvoices->sum('remaining_amount'), 2);

        $overdueInflows = round((float) $customerInvoices
            ->filter(fn ($invoice) => $invoice->due_at && Carbon::parse($invoice->due_at)->startOfDay()->lt($reportDate))
            ->sum('remaining_amount'), 2);

        $overdueOutflows = round((float) $supplierInvoices
            ->filter(fn ($invoice) => $invoice->due_at && Carbon::parse($invoice->due_at)->startOfDay()->lt($reportDate))
            ->sum('remaining_amount'), 2);

        $netExpectedCash = round($expectedInflows - $expectedOutflows, 2);

        $selectedBranchId = $request->integer('branch_id') ?: null;
        $selectedDateFrom = $this->dateInput($request, 'date_from');
        $selectedDateTo = $this->dateInput($request, 'date_to');

        $branches = DB::table('branches')->orderBy('name')->get(['id', 'name']);
        $selectedBranchName = $selectedBranchId
            ? optional($branches->firstWhere('id', $selectedBranchId))->name
            : null;

        return [
            'reportDate' => $reportDate,
            'branches' => $branches,
            'selectedBranchId' => $selectedBranchId,
            'selectedBranchName' => $selectedBranchName,
            'selectedDateFrom' => $selectedDateFrom,
            'selectedDateTo' => $selectedDateTo,
            'filterParams' => array_filter([
                'branch_id' => $selectedBranchId,
                'date_from' => $selectedDateFrom,
                'date_to' => $selectedDateTo,
            ]),
            'drilldownParams' => array_filter([
                'branch_id' => $selectedBranchId,
                'as_of_date' => $selectedDateTo,
            ]),
            'inflowSummary' => [
                'customers_count' => $customerInvoices->pluck('customer_id')->filter()->unique()->count(),
                'open_invoice_count' => $customerInvoices->count(),
                'expected_inflows' => $expectedInflows,
                'overdue_inflows' => $overdueInflows,
            ],
            'outflowSummary' => [
                'suppliers_count' => $supplierInvoices->pluck('supplier_id')->filter()->unique()->count(),
                'open_invoice_count' => $supplierInvoices->count(),
                'expected_outflows' => $expectedOutflows,
                'overdue_outflows' => $overdueOutflows,
            ],
            'netCashSummary' => [
                'net_expected_cash' => $netExpectedCash,
                'position_label' => $netExpectedCash >= 0
                    ? 'صافي تدفق نقدي متوقع لصالح الشركة'
                    : 'صافي التزامات نقدية متوقعة على الشركة',
            ],
            'riskSummary' => $this->riskSummary($overdueInflows, $overdueOutflows, $expectedInflows, $expectedOutflows),
            'bucketCashFlow' => $this->bucketCashFlow($customerRows, $supplierRows),
        ];
    }

    private function filteredSalesInvoices(Request $request): Collection
    {
        $query = SalesInvoice::query()
            ->where('remaining_amount', '>', 0);

        $this->applyCommonFilters($query, $request);

        return $query->get([
            'id',
            'customer_id',
            'branch_id',
            'invoice_number',
            'remaining_amount',
            'due_at',
        ]);
    }

    private function filteredPurchaseInvoices(Request $request): Collection
    {
        $query = PurchaseInvoice::query()
            ->where('remaining_amount', '>', 0);

        $this->applyCommonFilters($query, $request);

        return $query->get([
            'id',
            'supplier_id',
            'branch_id',
            'invoice_number',
            'remaining_amount',
            'due_at',
        ]);
    }

    private function applyCommonFilters($query, Request $request): void
    {
        $branchId = $request->integer('branch_id') ?: null;

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $dateFrom = $this->dateInput($request, 'date_from');
        $dateTo = $this->dateInput($request, 'date_to');

        if ($dateFrom) {
            $query->whereNotNull('due_at')
                ->whereDate('due_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereNotNull('due_at')
                ->whereDate('due_at', '<=', $dateTo);
        }
    }

    private function groupSalesInvoicesByCustomer(Collection $invoices, Carbon $reportDate): Collection
    {
        return $invoices
            ->groupBy(fn ($invoice) => $invoice->customer_id ?: 'without_customer')
            ->map(fn (Collection $group) => $this->bucketTotalsForInvoices($group, $reportDate))
            ->values();
    }

    private function groupPurchaseInvoicesBySupplier(Collection $invoices, Carbon $reportDate): Collection
    {
        return $invoices
            ->groupBy(fn ($invoice) => $invoice->supplier_id ?: 'without_supplier')
            ->map(fn (Collection $group) => $this->bucketTotalsForInvoices($group, $reportDate))
            ->values();
    }

    private function bucketTotalsForInvoices(Collection $invoices, Carbon $reportDate): array
    {
        $totals = [
            'not_due_total' => 0.0,
            'overdue_1_30_total' => 0.0,
            'overdue_31_60_total' => 0.0,
            'overdue_61_90_total' => 0.0,
            'overdue_more_than_90_total' => 0.0,
            'without_due_date_total' => 0.0,
        ];

        foreach ($invoices as $invoice) {
            $key = $this->bucketKey($invoice->due_at, $reportDate);
            $totals[$key] += (float) $invoice->remaining_amount;
        }

        return collect($totals)
            ->map(fn ($value) => round((float) $value, 2))
            ->all();
    }

    private function bucketKey($dueAt, Carbon $reportDate): string
    {
        if (! $dueAt) {
            return 'without_due_date_total';
        }

        $dueDate = Carbon::parse($dueAt)->startOfDay();

        if ($dueDate->gte($reportDate)) {
            return 'not_due_total';
        }

        $daysOverdue = (int) $dueDate->diffInDays($reportDate);

        if ($daysOverdue <= 30) {
            return 'overdue_1_30_total';
        }

        if ($daysOverdue <= 60) {
            return 'overdue_31_60_total';
        }

        if ($daysOverdue <= 90) {
            return 'overdue_61_90_total';
        }

        return 'overdue_more_than_90_total';
    }

    private function reportDate(Request $request): Carbon
    {
        $dateTo = $this->dateInput($request, 'date_to');

        if ($dateTo) {
            return Carbon::parse($dateTo)->startOfDay();
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

    private function riskSummary(float $overdueInflows, float $overdueOutflows, float $expectedInflows, float $expectedOutflows): array
    {
        $netOverduePressure = round($overdueOutflows - $overdueInflows, 2);
        $cashCoverageRatio = $expectedOutflows > 0
            ? round(($expectedInflows / $expectedOutflows) * 100, 2)
            : null;

        return [
            'overdue_inflows' => $overdueInflows,
            'overdue_outflows' => $overdueOutflows,
            'net_overdue_pressure' => $netOverduePressure,
            'cash_coverage_ratio' => $cashCoverageRatio,
            'pressure_label' => $netOverduePressure > 0
                ? 'ضغط نقدي متأخر على الشركة'
                : 'المتأخرات الداخلة تغطي الالتزامات المتأخرة',
            'coverage_label' => $cashCoverageRatio === null
                ? 'لا توجد التزامات خارجة مفتوحة'
                : ($cashCoverageRatio >= 100 ? 'تغطية نقدية متوقعة كافية' : 'تغطية نقدية متوقعة غير كافية'),
        ];
    }

    private function bucketCashFlow(Collection $customerRows, Collection $supplierRows): array
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
                $expectedInflows = round((float) $customerRows->sum(fn ($row) => (float) $row[$key]), 2);
                $expectedOutflows = round((float) $supplierRows->sum(fn ($row) => (float) $row[$key]), 2);

                return [
                    'key' => $key,
                    'label' => $label,
                    'expected_inflows' => $expectedInflows,
                    'expected_outflows' => $expectedOutflows,
                    'net_cash_flow' => round($expectedInflows - $expectedOutflows, 2),
                ];
            })
            ->values()
            ->all();
    }
}
