<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'branch_id' => $request->input('branch_id'),
            'expense_category_id' => $request->input('expense_category_id'),
            'payment_method' => $request->input('payment_method'),
            'payment_status' => $request->input('payment_status'),
        ];

        $branches = Branch::query()
            ->where('is_active', true)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get();

        $categories = ExpenseCategory::query()
            ->orderBy('name')
            ->get();

        $paymentMethods = $this->paymentMethods();
        $paymentStatuses = $this->paymentStatuses();

        $expensesQuery = Expense::query()
            ->with(['branch', 'category', 'user']);

        if (! empty($filters['from_date'])) {
            $expensesQuery->whereDate('expense_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $expensesQuery->whereDate('expense_date', '<=', $filters['to_date']);
        }

        if (! empty($filters['branch_id'])) {
            $expensesQuery->where('branch_id', $filters['branch_id']);
        }

        if (! empty($filters['expense_category_id'])) {
            $expensesQuery->where('expense_category_id', $filters['expense_category_id']);
        }

        if (! empty($filters['payment_method'])) {
            $expensesQuery->where('payment_method', $filters['payment_method']);
        }

        if ($filters['payment_status'] === 'paid') {
            $expensesQuery->where('is_paid', true);
        }

        if ($filters['payment_status'] === 'unpaid') {
            $expensesQuery->where('is_paid', false);
        }

        $expenseTotals = [
            'count' => (clone $expensesQuery)->count(),
            'amount' => round((float) (clone $expensesQuery)->sum('amount'), 2),
            'tax_amount' => round((float) (clone $expensesQuery)->sum('tax_amount'), 2),
            'paid_amount' => round((float) (clone $expensesQuery)->where('is_paid', true)->sum('amount'), 2),
            'unpaid_amount' => round((float) (clone $expensesQuery)->where('is_paid', false)->sum('amount'), 2),
        ];

        $expenses = $expensesQuery
            ->latest('expense_date')
            ->latest('id')
            ->get();

        return view('expenses.index', [
            'expenses' => $expenses,
            'branches' => $branches,
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
            'paymentStatuses' => $paymentStatuses,
            'filters' => $filters,
            'expenseTotals' => $expenseTotals,
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
            'paymentMethods' => $this->paymentMethods(),
            'paymentStatuses' => $this->paymentStatuses(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedExpenseData($request);

        $branch = Branch::query()->findOrFail($data['branch_id']);
        $category = ExpenseCategory::query()->findOrFail($data['expense_category_id']);

        $companyValidationResponse = $this->validateCategoryAndBranchCompany($category, $branch);

        if ($companyValidationResponse) {
            return $companyValidationResponse;
        }

        if (! $category->is_active) {
            return back()
                ->withErrors([
                    'expense_category_id' => 'لا يمكن تسجيل مصروف على تصنيف غير نشط.',
                ])
                ->withInput();
        }

        $nextNumber = Expense::query()
            ->where('company_id', $branch->company_id)
            ->count() + 1;

        $attachmentData = $this->storeAttachment($request);

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
            'attachment_path' => $attachmentData['attachment_path'],
            'attachment_original_name' => $attachmentData['attachment_original_name'],
            'is_paid' => $this->paymentStatusValue($request),
        ]);

        return redirect()
            ->route('expenses.index')
            ->with('success', 'تم تسجيل المصروف بنجاح.');
    }

    public function edit(Expense $expense): View
    {
        $branches = Branch::query()
            ->where('is_active', true)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get();

        $categories = ExpenseCategory::query()
            ->where(function ($query) use ($expense): void {
                $query->where('is_active', true)
                    ->orWhere('id', $expense->expense_category_id);
            })
            ->orderBy('name')
            ->get();

        return view('expenses.edit', [
            'expense' => $expense->load(['branch', 'category']),
            'branches' => $branches,
            'categories' => $categories,
            'paymentMethods' => $this->paymentMethods(),
            'paymentStatuses' => $this->paymentStatuses(),
        ]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $data = $this->validatedExpenseData($request);

        $branch = Branch::query()->findOrFail($data['branch_id']);
        $category = ExpenseCategory::query()->findOrFail($data['expense_category_id']);

        $companyValidationResponse = $this->validateCategoryAndBranchCompany($category, $branch);

        if ($companyValidationResponse) {
            return $companyValidationResponse;
        }

        if (! $category->is_active) {
            return back()
                ->withErrors([
                    'expense_category_id' => 'لا يمكن تحديث المصروف باستخدام تصنيف غير نشط.',
                ])
                ->withInput();
        }

        $attachmentData = [
            'attachment_path' => $expense->attachment_path,
            'attachment_original_name' => $expense->attachment_original_name,
        ];

        if ($request->hasFile('attachment')) {
            $this->deleteAttachmentFile($expense);
            $attachmentData = $this->storeAttachment($request);
        }

        $expense->update([
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'description' => $data['description'],
            'amount' => (float) $data['amount'],
            'tax_amount' => (float) ($data['tax_amount'] ?? 0),
            'payment_method' => $data['payment_method'],
            'expense_date' => $data['expense_date'],
            'reference_number' => $data['reference_number'] ?? null,
            'notes' => $data['notes'] ?? null,
            'attachment_path' => $attachmentData['attachment_path'],
            'attachment_original_name' => $attachmentData['attachment_original_name'],
            'is_paid' => $this->paymentStatusValue($request),
        ]);

        return redirect()
            ->route('expenses.index')
            ->with('success', 'تم تحديث المصروف بنجاح.');
    }

    public function destroyAttachment(Expense $expense): RedirectResponse
    {
        $this->deleteAttachmentFile($expense);

        $expense->update([
            'attachment_path' => null,
            'attachment_original_name' => null,
        ]);

        return redirect()
            ->route('expenses.edit', $expense)
            ->with('success', 'تم حذف مرفق المصروف بنجاح.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->deleteAttachmentFile($expense);

        $expense->delete();

        return redirect()
            ->route('expenses.index')
            ->with('success', 'تم حذف المصروف بنجاح.');
    }

    private function validatedExpenseData(Request $request): array
    {
        return $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'expense_category_id' => ['required', 'integer', Rule::exists('expense_categories', 'id')->where('is_active', true)],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'in:cash,card,bank_transfer,online,other'],
            'is_paid' => ['nullable', 'boolean'],
            'expense_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:4096'],
        ]);
    }

    private function storeAttachment(Request $request): array
    {
        if (! $request->hasFile('attachment')) {
            return [
                'attachment_path' => null,
                'attachment_original_name' => null,
            ];
        }

        $file = $request->file('attachment');

        return [
            'attachment_path' => $file->store('expense-attachments', 'public'),
            'attachment_original_name' => $file->getClientOriginalName(),
        ];
    }

    private function deleteAttachmentFile(Expense $expense): void
    {
        if (! $expense->attachment_path) {
            return;
        }

        Storage::disk('public')->delete($expense->attachment_path);
    }

    private function paymentStatusValue(Request $request): bool
    {
        if (! $request->has('is_paid')) {
            return true;
        }

        return $request->boolean('is_paid');
    }

    private function validateCategoryAndBranchCompany(ExpenseCategory $category, Branch $branch): ?RedirectResponse
    {
        if ($category->company_id !== $branch->company_id) {
            return back()
                ->withErrors([
                    'expense_category_id' => 'تصنيف المصروف لا يتبع نفس شركة الفرع.',
                ])
                ->withInput();
        }

        return null;
    }

    private function paymentMethods(): array
    {
        return [
            'cash' => 'نقدًا',
            'card' => 'بطاقة',
            'bank_transfer' => 'تحويل بنكي',
            'online' => 'دفع إلكتروني',
            'other' => 'أخرى',
        ];
    }

    private function paymentStatuses(): array
    {
        return [
            'paid' => 'مدفوعة',
            'unpaid' => 'غير مدفوعة',
        ];
    }
}
