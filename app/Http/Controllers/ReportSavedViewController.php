<?php

namespace App\Http\Controllers;

use App\Models\ReportSavedView;
use App\Services\ReportSavedViewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class ReportSavedViewController extends Controller
{
    private const REPORT_LABELS = [
        'cash-flow-dashboard' => 'لوحة التدفق النقدي',
        'receivable-payable-aging-dashboard' => 'لوحة أعمار الذمم المدينة والدائنة',
        'customer-sales-invoice-aging' => 'تقرير أعمار ذمم العملاء',
        'supplier-purchase-invoice-aging' => 'تقرير أعمار ذمم الموردين',
        'sales-invoice-aging' => 'تقرير أعمار فواتير المبيعات',
        'customer-sales-invoice-aging-drilldown' => 'تفاصيل أعمار فواتير العملاء',
        'supplier-purchase-invoice-aging-drilldown' => 'تفاصيل أعمار فواتير الموردين',
    ];

    private const REPORT_ROUTES = [
        'cash-flow-dashboard' => 'reports.cash-flow-dashboard.index',
        'receivable-payable-aging-dashboard' => 'reports.receivable-payable-aging-dashboard.index',
        'customer-sales-invoice-aging' => 'reports.customer-sales-invoice-aging.index',
        'supplier-purchase-invoice-aging' => 'reports.supplier-purchase-invoice-aging.index',
        'sales-invoice-aging' => 'reports.sales-invoice-aging.index',
        'customer-sales-invoice-aging-drilldown' => 'reports.customer-sales-invoice-aging.drilldown',
        'supplier-purchase-invoice-aging-drilldown' => 'reports.supplier-purchase-invoice-aging.drilldown',
    ];

    private const FILTER_LABELS = [
        'branch_id' => 'الفرع',
        'customer_id' => 'العميل',
        'supplier_id' => 'المورد',
        'date_from' => 'من تاريخ',
        'date_to' => 'إلى تاريخ',
        'as_of_date' => 'تاريخ التقرير',
        'payment_status' => 'حالة الدفع',
        'aging_bucket' => 'شريحة العمر',
    ];

    private const PAYMENT_STATUS_LABELS = [
        'unpaid' => 'غير مدفوعة',
        'partial' => 'مدفوعة جزئيًا',
        'paid' => 'مدفوعة بالكامل',
    ];

    private const AGING_BUCKET_LABELS = [
        'not_due' => 'غير مستحقة بعد',
        'overdue_1_30' => 'متأخرة 1 إلى 30 يوم',
        'overdue_31_60' => 'متأخرة 31 إلى 60 يوم',
        'overdue_61_90' => 'متأخرة 61 إلى 90 يوم',
        'overdue_more_than_90' => 'أكثر من 90 يوم',
        'without_due_date' => 'بدون تاريخ استحقاق',
    ];

    public function index(Request $request, ReportSavedViewService $savedViewService): View
    {
        $savedViews = $savedViewService
            ->list($request->user())
            ->map(fn (ReportSavedView $savedView) => $this->formatSavedView($savedView));

        return view('reports.saved-views.index', [
            'savedViews' => $savedViews,
            'totalSavedViews' => $savedViews->count(),
        ]);
    }

    public function makeDefault(Request $request, ReportSavedView $savedView): RedirectResponse
    {
        $this->authorizeSavedView($request, $savedView);

        ReportSavedView::query()
            ->where('user_id', $request->user()->id)
            ->where('report_key', $savedView->report_key)
            ->update(['is_default' => false]);

        $savedView->forceFill(['is_default' => true])->save();

        return redirect()
            ->route('reports.saved-views.index')
            ->with('status', 'تم تعيين العرض الافتراضي للتقرير.');
    }


    public function edit(Request $request, ReportSavedView $savedView): View
    {
        $this->authorizeSavedView($request, $savedView);

        $filters = $savedView->filters ?? [];

        return view('reports.saved-views.edit', [
            'savedView' => $savedView,
            'reportName' => $this->formatReportName($savedView->report_key),
            'filters' => collect($filters)
                ->map(fn ($value, $key) => [
                    'key' => $key,
                    'label' => $this->formatFilterKey((string) $key),
                    'value' => $this->formatFilterDisplayValue((string) $key, $value),
                ])
                ->values(),
        ]);
    }

    public function update(Request $request, ReportSavedView $savedView): RedirectResponse
    {
        $this->authorizeSavedView($request, $savedView);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_default' => ['nullable'],
        ]);

        DB::transaction(function () use ($request, $savedView, $validated): void {
            $isDefault = $request->boolean('is_default');

            if ($isDefault) {
                ReportSavedView::query()
                    ->where('user_id', $request->user()->id)
                    ->where('report_key', $savedView->report_key)
                    ->where('id', '!=', $savedView->id)
                    ->update(['is_default' => false]);
            }

            $savedView->forceFill([
                'name' => $validated['name'],
                'is_default' => $isDefault,
            ])->save();
        });

        return redirect()
            ->route('reports.saved-views.index')
            ->with('status', 'تم تحديث العرض المحفوظ بنجاح.');
    }

    public function destroy(Request $request, ReportSavedView $savedView, ReportSavedViewService $savedViewService): RedirectResponse
    {
        $this->authorizeSavedView($request, $savedView);

        $savedViewService->delete($request->user(), $savedView->id);

        return redirect()
            ->route('reports.saved-views.index')
            ->with('status', 'تم حذف العرض المحفوظ.');
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        ReportSavedView::query()
            ->where('user_id', $request->user()->id)
            ->delete();

        return redirect()
            ->route('reports.saved-views.index')
            ->with('status', 'تم حذف جميع العروض المحفوظة.');
    }

    private function formatSavedView(ReportSavedView $savedView): object
    {
        $filters = $savedView->filters ?? [];

        return (object) [
            'id' => $savedView->id,
            'name' => $savedView->name,
            'report_key' => $savedView->report_key,
            'report_label' => self::REPORT_LABELS[$savedView->report_key] ?? $savedView->report_key,
            'is_default' => $savedView->is_default,
            'report_url' => $this->reportUrl($savedView->report_key, $filters),
            'filters' => collect($filters)
                ->map(fn ($value, $key) => [
                    'key' => $key,
                    'label' => self::FILTER_LABELS[$key] ?? $key,
                    'value' => $value,
                    'display_value' => $this->displayFilterValue((string) $key, $value),
                ])
                ->values(),
            'updated_at' => $savedView->updated_at,
        ];
    }


    private function formatReportName(string $reportKey): string
    {
        return [
            'cash-flow-dashboard' => 'لوحة التدفق النقدي',
            'receivable-payable-aging-dashboard' => 'لوحة أعمار الذمم',
            'customer-sales-invoice-aging' => 'تقرير أعمار ذمم العملاء',
            'supplier-purchase-invoice-aging' => 'تقرير أعمار ذمم الموردين',
            'sales-invoice-aging' => 'تقرير أعمار فواتير المبيعات',
            'customer-sales-invoice-aging-drilldown' => 'تفاصيل أعمار فواتير العملاء',
            'supplier-purchase-invoice-aging-drilldown' => 'تفاصيل أعمار فواتير الموردين',
        ][$reportKey] ?? $reportKey;
    }



    private function formatFilterKey(string $key): string
    {
        return [
            'customer_id' => 'العميل',
            'supplier_id' => 'المورد',
            'branch_id' => 'الفرع',
            'as_of_date' => 'حتى تاريخ',
            'aging_bucket' => 'شريحة العمر',
            'payment_status' => 'حالة الدفع',
        ][$key] ?? $key;
    }

    private function formatFilterDisplayValue(string $key, mixed $value): string
    {
        return match ($key) {
            'customer_id' => $this->lookupTableDisplayValue('customers', $value, ['name', 'customer_name', 'company_name'])
                ?? $this->formatFilterValue($value),
            'supplier_id' => $this->lookupTableDisplayValue('suppliers', $value, ['name', 'supplier_name', 'company_name'])
                ?? $this->formatFilterValue($value),
            'branch_id' => $this->lookupTableDisplayValue('branches', $value, ['name', 'branch_name'])
                ?? $this->formatFilterValue($value),
            'aging_bucket' => $this->formatAgingBucket((string) $value),
            'payment_status' => $this->formatPaymentStatus((string) $value),
            default => $this->formatFilterValue($value),
        };
    }

    private function formatAgingBucket(string $bucket): string
    {
        return [
            'not_due' => 'غير مستحق',
            'overdue_1_30' => 'متأخر من 1 إلى 30 يوم',
            'overdue_31_60' => 'متأخر من 31 إلى 60 يوم',
            'overdue_61_90' => 'متأخر من 61 إلى 90 يوم',
            'overdue_more_than_90' => 'متأخر أكثر من 90 يوم',
            'without_due_date' => 'بدون تاريخ استحقاق',
        ][$bucket] ?? $bucket;
    }

    private function formatPaymentStatus(string $status): string
    {
        return [
            'all' => 'كل الحالات',
            'paid' => 'مدفوعة',
            'unpaid' => 'غير مدفوعة',
            'partial' => 'مدفوعة جزئيًا',
            'partially_paid' => 'مدفوعة جزئيًا',
            'pending' => 'قيد المتابعة',
            'overdue' => 'متأخرة',
        ][$status] ?? $status;
    }

    private function lookupTableDisplayValue(string $table, mixed $id, array $columns): ?string
    {
        if (! is_numeric($id)) {
            return null;
        }

        try {
            foreach ($columns as $column) {
                if (! DB::getSchemaBuilder()->hasColumn($table, $column)) {
                    continue;
                }

                $value = DB::table($table)
                    ->where('id', $id)
                    ->value($column);

                if ($value !== null && $value !== '') {
                    return (string) $value;
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function formatFilterValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'نعم' : 'لا';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($value === null) {
            return '';
        }

        return (string) $value;
    }

    private function reportUrl(string $reportKey, array $filters): ?string
    {
        $routeName = self::REPORT_ROUTES[$reportKey] ?? null;

        if (! $routeName || ! Route::has($routeName)) {
            return null;
        }

        $query = array_filter($filters, fn ($value) => $value !== null && $value !== '');

        return route($routeName, $query);
    }

    private function displayFilterValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return match ($key) {
            'customer_id' => $this->entityLabel('customers', (int) $value, 'عميل'),
            'supplier_id' => $this->entityLabel('suppliers', (int) $value, 'مورد'),
            'branch_id' => $this->entityLabel('branches', (int) $value, 'فرع'),
            'payment_status' => self::PAYMENT_STATUS_LABELS[(string) $value] ?? (string) $value,
            'aging_bucket' => self::AGING_BUCKET_LABELS[(string) $value] ?? (string) $value,
            default => (string) $value,
        };
    }

    private function entityLabel(string $table, int $id, string $fallbackLabel): string
    {
        if ($id <= 0) {
            return '-';
        }

        $name = DB::table($table)->where('id', $id)->value('name');

        return $name ? $name . ' #' . $id : $fallbackLabel . ' غير معروف #' . $id;
    }

    private function authorizeSavedView(Request $request, ReportSavedView $savedView): void
    {
        abort_unless((int) $savedView->user_id === (int) $request->user()->id, 404);
    }
}
