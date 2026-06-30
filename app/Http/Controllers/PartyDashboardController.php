<?php

namespace App\Http\Controllers;

use App\Services\PartyDashboardSummaryService;

class PartyDashboardController extends Controller
{
    public function index(PartyDashboardSummaryService $service)
    {
        return view('party-dashboard.index', [
            'summary' => $service->summary(),
        ]);
    }
}
