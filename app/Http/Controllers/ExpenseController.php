<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(): View
    {
        $expenses = Expense::query()
            ->with(['branch', 'category', 'user'])
            ->latest('expense_date')
            ->latest('id')
            ->get();

        return view('expenses.index', [
            'expenses' => $expenses,
        ]);
    }

    public function create(): View
    {
        $branches = Branch::query()
            ->where('is_active', true)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get();

        $categories = ExpenseCategory::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        return view('expenses.create', [
            'branches' => $branches,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'in:cash,card,bank_transfer,online,other'],
            'expense_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $branch = Branch::query()->findOrFail($data['branch_id']);
        $category = ExpenseCategory::query()->findOrFail($data['expense_category_id']);

        if ($category->company_id !== $branch->company_id) {
            return back()
                ->withErrors([
                    'expense_category_id' => 'تصنيف المصروف لا يتبع نفس شركة الفرع.',
                ])
                ->withInput();
        }

        $nextNumber = Expense::query()
            ->where('company_id', $branch->company_id)
            ->count() + 1;

        Expense::query()->create([
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'user_id' => $request->user()?->id,
            'code' => 'EXP-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT),
            'description' => $data['description'],
            'amount' => (float) $data['amount'],
            'tax_amount' => (float) ($data['tax_amount'] ?? 0),
            'payment_method' => $data['payment_method'],
            'expense_date' => $data['expense_date'],
            'reference_number' => $data['reference_number'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_paid' => true,
        ]);

        return redirect()
            ->route('expenses.index')
            ->with('success', 'تم تسجيل المصروف بنجاح.');
    }
}
