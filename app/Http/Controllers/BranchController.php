<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(): View
    {
        $branches = Branch::query()
            ->with('company')
            ->withCount('warehouses')
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get();

        return view('branches.index', [
            'branches' => $branches,
        ]);
    }
}
