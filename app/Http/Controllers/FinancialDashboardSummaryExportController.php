<?php

namespace App\Http\Controllers;

use App\Services\FinancialDashboardSummaryService;
use Illuminate\Http\Request;

class FinancialDashboardSummaryExportController extends Controller
{
    public function __invoke(Request $request, FinancialDashboardSummaryService $summaryService)
    {
        $summary = $summaryService->summary($request);

        $fileName = 'main-dashboard-financial-summary-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($summary) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, chr(239) . chr(187) . chr(191));

            fputcsv($handle, ['الملخص المالي السريع للوحة التحكم']);
            fputcsv($handle, ['تاريخ إنشاء التقرير', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, []);

            fputcsv($handle, ['ذمم العملاء']);
            fputcsv($handle, ['عدد العملاء أصحاب الذمم', $summary['customers_count']]);
            fputcsv($handle, ['فواتير العملاء المفتوحة', $summary['customer_open_invoice_count']]);
            fputcsv($handle, ['ذمم العملاء المفتوحة', number_format((float) $summary['expected_inflows'], 2, '.', '')]);
            fputcsv($handle, ['متأخرات العملاء', number_format((float) $summary['overdue_inflows'], 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, ['التزامات الموردين']);
            fputcsv($handle, ['عدد الموردين أصحاب الذمم', $summary['suppliers_count']]);
            fputcsv($handle, ['فواتير الموردين المفتوحة', $summary['supplier_open_invoice_count']]);
            fputcsv($handle, ['التزامات الموردين المفتوحة', number_format((float) $summary['expected_outflows'], 2, '.', '')]);
            fputcsv($handle, ['متأخرات الموردين', number_format((float) $summary['overdue_outflows'], 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, ['التدفق النقدي المتوقع']);
            fputcsv($handle, ['صافي التدفق النقدي المتوقع', number_format((float) $summary['net_expected_cash'], 2, '.', '')]);
            fputcsv($handle, ['حالة التدفق النقدي', $summary['position_label']]);
            fputcsv($handle, []);

            fputcsv($handle, ['مؤشرات المخاطر المالية']);
            fputcsv($handle, ['صافي الضغط النقدي المتأخر', number_format((float) $summary['net_overdue_pressure'], 2, '.', '')]);
            fputcsv($handle, ['نسبة تغطية الالتزامات', $summary['cash_coverage_ratio'] === null ? 'غير مطبق' : number_format((float) $summary['cash_coverage_ratio'], 2, '.', '') . '%']);
            fputcsv($handle, ['حالة التغطية النقدية', $summary['cash_coverage_label']]);
            fputcsv($handle, ['مؤشر المتابعة المالية', $summary['risk_label']]);

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
