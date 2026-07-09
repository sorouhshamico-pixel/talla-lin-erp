<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\ReportFilterPreferenceService;
use App\Services\SupplierPurchaseInvoiceAgingReportBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierPurchaseInvoiceAgingReportController extends Controller
{
    private const REPORT_KEY = 'supplier-purchase-invoice-aging';

    private const FILTER_KEYS = ['supplier_id', 'aging_bucket'];

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

    public function index(
        Request $request,
        SupplierPurchaseInvoiceAgingReportBuilder $builder,
        ReportFilterPreferenceService $filterPreferences
    ): View {
        $request = $this->requestWithFilterPreferences($request, $filterPreferences, true);

        $report = $builder->build($request);

        return view('reports.supplier-purchase-invoice-aging', [
            'reportDate' => $report['reportDate'],
            'rows' => $report['rows'],
            'summary' => $report['summary'],
            'supplierFilterLabel' => $report['supplierFilterLabel'],
            'agingBucketFilterLabel' => $report['agingBucketFilterLabel'],
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'agingBuckets' => self::AGING_BUCKET_LABELS,
        ]);
    }

    public function print(
        Request $request,
        SupplierPurchaseInvoiceAgingReportBuilder $builder,
        ReportFilterPreferenceService $filterPreferences
    ) {
        $request = $this->requestWithFilterPreferences($request, $filterPreferences, false);

        $report = $builder->build($request);

        return view('reports.supplier-purchase-invoice-aging-print', [
            'reportDate' => $report['reportDate'],
            'rows' => $report['rows'],
            'summary' => $report['summary'],
            'supplierFilterLabel' => $report['supplierFilterLabel'],
            'agingBucketFilterLabel' => $report['agingBucketFilterLabel'],
        ]);
    }

    public function export(
        Request $request,
        SupplierPurchaseInvoiceAgingReportBuilder $builder,
        ReportFilterPreferenceService $filterPreferences
    ) {
        $request = $this->requestWithFilterPreferences($request, $filterPreferences, false);

        $report = $builder->build($request);

        $fileName = 'supplier-purchase-invoice-aging-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, chr(239) . chr(187) . chr(191));

            fputcsv($handle, ['تقرير أعمار ذمم الموردين']);
            fputcsv($handle, ['تاريخ إنشاء التقرير', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, ['تاريخ التقرير', $report['reportDate']->format('Y-m-d')]);
            fputcsv($handle, ['فلتر المورد', $report['supplierFilterLabel']]);
            fputcsv($handle, ['فلتر شريحة العمر', $report['agingBucketFilterLabel']]);
            fputcsv($handle, []);

            fputcsv($handle, ['ملخص عام']);
            fputcsv($handle, ['عدد الموردين', $report['summary']['suppliers_count']]);
            fputcsv($handle, ['عدد الفواتير المفتوحة', $report['summary']['invoice_count']]);
            fputcsv($handle, ['إجمالي الذمم المفتوحة', number_format((float) $report['summary']['remaining_total'], 2, '.', '')]);
            fputcsv($handle, ['إجمالي المتأخر', number_format((float) $report['summary']['overdue_total'], 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'المورد',
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
                    $row['supplier'] ? $row['supplier']->name : '',
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
}
