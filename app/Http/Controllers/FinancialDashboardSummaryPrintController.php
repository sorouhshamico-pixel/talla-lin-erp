<?php

namespace App\Http\Controllers;

use App\Services\FinancialDashboardSummaryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialDashboardSummaryPrintController extends Controller
{
    public function __invoke(Request $request, FinancialDashboardSummaryService $summaryService): View
    {
        return view('dashboard.financial-summary-print', [
            'summary' => $summaryService->summary($request),
            'reportDate' => now(),
        ]);
    }
}
