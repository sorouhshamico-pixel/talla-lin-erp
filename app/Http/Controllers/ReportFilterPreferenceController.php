<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportFilterPreferenceController extends Controller
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

    public function index(Request $request): View
    {
        $preferences = DB::table('user_report_filter_preferences')
            ->where('user_id', $request->user()->id)
            ->orderBy('report_key')
            ->get()
            ->map(fn ($preference) => $this->formatPreference($preference));

        return view('reports.filter-preferences.index', [
            'preferences' => $preferences,
            'totalPreferences' => $preferences->count(),
        ]);
    }

    public function destroy(Request $request, string $reportKey): RedirectResponse
    {
        DB::table('user_report_filter_preferences')
            ->where('user_id', $request->user()->id)
            ->where('report_key', $reportKey)
            ->delete();

        return redirect()
            ->route('reports.filter-preferences.index')
            ->with('status', 'تم حذف تفضيلات الفلتر للتقرير المحدد.');
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        DB::table('user_report_filter_preferences')
            ->where('user_id', $request->user()->id)
            ->delete();

        return redirect()
            ->route('reports.filter-preferences.index')
            ->with('status', 'تم حذف جميع تفضيلات فلاتر التقارير.');
    }

    private function formatPreference(object $preference): object
    {
        $filters = $this->decodeFilters($preference->filters ?? null);

        return (object) [
            'report_key' => $preference->report_key,
            'report_label' => self::REPORT_LABELS[$preference->report_key] ?? $preference->report_key,
            'filters' => collect($filters)
                ->map(fn ($value, $key) => [
                    'key' => $key,
                    'label' => self::FILTER_LABELS[$key] ?? $key,
                    'value' => $value,
                ])
                ->values(),
            'updated_at' => $preference->updated_at ?? null,
        ];
    }

    private function decodeFilters(?string $filters): array
    {
        if (! $filters) {
            return [];
        }

        $decoded = json_decode($filters, true);

        return is_array($decoded) ? $decoded : [];
    }
}
