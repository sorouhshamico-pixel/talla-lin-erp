<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    public function index(): View
    {
        $categories = ExpenseCategory::query()
            ->withCount('expenses')
            ->latest('id')
            ->get();

        return view('expense-categories.index', [
            'categories' => $categories,
        ]);
    }

    public function create(): View
    {
        return view('expense-categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $branch = Branch::query()->findOrFail($request->user()->current_branch_id);
        $companyId = $branch->company_id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('expense_categories', 'slug')
                    ->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        ExpenseCategory::query()->create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'is_active' => true,
        ]);

        return redirect()
            ->route('expense-categories.index')
            ->with('success', 'تم إنشاء تصنيف المصروف بنجاح.');
    }
}
