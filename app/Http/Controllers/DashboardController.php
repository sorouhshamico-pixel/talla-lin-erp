<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user()->load(['branches', 'currentBranch']);

        $company = Company::query()
            ->withCount(['branches', 'warehouses'])
            ->first();

        return view('dashboard.index', [
            'user' => $user,
            'company' => $company,
        ]);
    }
}
