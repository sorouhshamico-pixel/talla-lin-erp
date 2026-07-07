<?php

namespace App\Http\Controllers;

use App\Services\FinancialDashboardSummaryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MainDashboardTopOverduePrintController extends Controller
{
    public function __invoke(Request $request, FinancialDashboardSummaryService $summaryService): View
    {
        return view('dashboard.top-overdue-print', [
            'reportDate' => now(),
            'topOverdueCustomers' => $summaryService->topOverdueCustomers($request, 50),
            'topOverdueSuppliers' => $summaryService->topOverdueSuppliers($request, 50),
        ]);
    }
}
