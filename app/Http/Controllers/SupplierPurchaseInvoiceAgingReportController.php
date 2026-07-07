<?php

namespace App\Http\Controllers;

use App\Services\SupplierPurchaseInvoiceAgingReportBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierPurchaseInvoiceAgingReportController extends Controller
{
    public function export(Request $request, \App\Services\SupplierPurchaseInvoiceAgingReportBuilder $builder)
    {
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

    public function index(Request $request, SupplierPurchaseInvoiceAgingReportBuilder $builder): View
    {
        $report = $builder->build($request);

        return view('reports.supplier-purchase-invoice-aging', [
            'reportDate' => $report['reportDate'],
            'rows' => $report['rows'],
            'summary' => $report['summary'],
            'supplierFilterLabel' => $report['supplierFilterLabel'],
            'agingBucketFilterLabel' => $report['agingBucketFilterLabel'],
            'suppliers' => \App\Models\Supplier::query()->orderBy('name')->get(['id', 'name']),
            'agingBuckets' => [
                'not_due' => 'غير مستحقة بعد',
                'overdue_1_30' => 'متأخرة 1 إلى 30 يوم',
                'overdue_31_60' => 'متأخرة 31 إلى 60 يوم',
                'overdue_61_90' => 'متأخرة 61 إلى 90 يوم',
                'overdue_more_than_90' => 'أكثر من 90 يوم',
                'without_due_date' => 'بدون تاريخ استحقاق',
            ],
        ]);
    }
}
