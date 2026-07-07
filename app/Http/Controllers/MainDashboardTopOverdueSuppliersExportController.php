<?php

namespace App\Http\Controllers;

use App\Services\FinancialDashboardSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MainDashboardTopOverdueSuppliersExportController extends Controller
{
    public function __invoke(Request $request, FinancialDashboardSummaryService $summaryService)
    {
        $rows = $summaryService->topOverdueSuppliers($request, 50);
        $branchLabel = $this->branchLabel($request);
        $reportDateLabel = $this->reportDateLabel($request);

        $fileName = 'main-dashboard-top-overdue-suppliers-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows, $branchLabel, $reportDateLabel) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, chr(239) . chr(187) . chr(191));

            fputcsv($handle, ['أكبر الموردين المتأخرين']);
            fputcsv($handle, ['تاريخ إنشاء التقرير', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, ['تاريخ الاحتساب', $reportDateLabel]);
            fputcsv($handle, ['فلتر الفرع', $branchLabel]);
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

    private function branchLabel(Request $request): string
    {
        $branchId = $request->integer('branch_id') ?: null;

        if (! $branchId) {
            return 'كل الفروع';
        }

        $name = DB::table('branches')->where('id', $branchId)->value('name');

        return $name ? $name . ' #' . $branchId : 'فرع غير معروف #' . $branchId;
    }

    private function reportDateLabel(Request $request): string
    {
        $asOfDate = $request->input('as_of_date');

        if ($asOfDate) {
            try {
                return Carbon::parse($asOfDate)->format('Y-m-d');
            } catch (\Throwable) {
                return now()->format('Y-m-d');
            }
        }

        return now()->format('Y-m-d');
    }
}
