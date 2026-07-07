<?php

namespace App\Http\Controllers;

use App\Services\FinancialDashboardSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MainDashboardTopOverduePrintController extends Controller
{
    public function __invoke(Request $request, FinancialDashboardSummaryService $summaryService): View
    {
        return view('dashboard.top-overdue-print', [
            'reportDate' => now(),
            'branchLabel' => $this->branchLabel($request),
            'topOverdueCustomers' => $summaryService->topOverdueCustomers($request, 50),
            'topOverdueSuppliers' => $summaryService->topOverdueSuppliers($request, 50),
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
}
