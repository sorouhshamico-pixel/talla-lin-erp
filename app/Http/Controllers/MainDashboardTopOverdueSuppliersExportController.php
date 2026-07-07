<?php

namespace App\Http\Controllers;

use App\Services\FinancialDashboardSummaryService;
use Illuminate\Http\Request;

class MainDashboardTopOverdueSuppliersExportController extends Controller
{
    public function __invoke(Request $request, FinancialDashboardSummaryService $summaryService)
    {
        $rows = $summaryService->topOverdueSuppliers($request, 50);

        $fileName = 'main-dashboard-top-overdue-suppliers-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, chr(239) . chr(187) . chr(191));

            fputcsv($handle, ['أكبر الموردين المتأخرين']);
            fputcsv($handle, ['تاريخ إنشاء التقرير', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'المورد',
                'عدد الفواتير',
                'إجمالي المتأخر',
                'أقدم استحقاق',
                'أقصى تأخير بالأيام',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['supplier_name'],
                    $row['invoice_count'],
                    number_format((float) $row['overdue_total'], 2, '.', ''),
                    $row['oldest_due_at'] ?? '',
                    $row['max_days_overdue'] ?? '',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
