<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    private const EXPENSE_CODE_PREFIX = 'EXP-';

    public function index(Request $request): View
    {
        $filters = $this->expenseFilters($request);

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
        $attachmentStatuses = $this->attachmentStatuses();

        $expensesQuery = $this->filteredExpensesQuery($filters);

        $expenseTotals = [
            'count' => (clone $expensesQuery)->count(),
            'amount' => round((float) (clone $expensesQuery)->sum('amount'), 2),
            'tax_amount' => round((float) (clone $expensesQuery)->sum('tax_amount'), 2),
            'paid_amount' => round((float) (clone $expensesQuery)->where('is_paid', true)->sum('amount'), 2),
            'unpaid_amount' => round((float) (clone $expensesQuery)->where('is_paid', false)->sum('amount'), 2),
        ];

        $filters['large_amount'] = $request->query('large_amount');

        $largeAmountAlert = $this->largeAmountAlert($filters);
        $largeAmountTopExpenses = $this->largeAmountTopExpenses($filters);
        $largeAmountTopExpensesTotal = round((float) $largeAmountTopExpenses->sum('amount'), 2);
        $unpaidAlert = $this->unpaidExpenseAlert($filters);
        $monthlySummary = $this->monthlyExpenseSummary($filters);
        $missingAttachmentSummary = $this->missingAttachmentSummary($filters);
        // 11Q large amount list filter
        if (($filters['large_amount'] ?? null) === '1') {
            $expensesQuery->where('amount', '>=', 1000);
        }


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
            'attachmentStatuses' => $attachmentStatuses,
            'filters' => $filters,
            'expenseTotals' => $expenseTotals,
            'largeAmountAlert' => $largeAmountAlert,
            'largeAmountTopExpenses' => $largeAmountTopExpenses,
            'largeAmountTopExpensesTotal' => $largeAmountTopExpensesTotal,
            'unpaidAlert' => $unpaidAlert,
            'monthlySummary' => $monthlySummary,
            'missingAttachmentSummary' => $missingAttachmentSummary,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->expenseFilters($request);

        $expenses = $this->filteredExpensesQuery($filters)
            // 11S CSV large amount filter
            ->when($request->query('large_amount') === '1', fn ($query) => $query->where('amount', '>=', 1000))
            ->latest('expense_date')
            ->latest('id')
            ->get();

        $fileName = 'expenses-report-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($expenses): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'الكود',
                'التاريخ',
                'الوصف',
                'الفرع',
                'التصنيف',
                'طريقة الدفع',
                'حالة الدفع',
                'المبلغ',
                'الضريبة',
                'رقم المرجع',
                'المرفق',
            ]);

            foreach ($expenses as $expense) {
                fputcsv($handle, [
                    $expense->code,
                    $expense->expense_date?->format('Y-m-d'),
                    $expense->description,
                    $expense->branch?->name_ar ?? $expense->branch?->name ?? $expense->branch?->name_en ?? '',
                    $expense->category?->name ?? '',
                    $expense->displayPaymentMethod(),
                    $expense->is_paid ? 'مدفوع' : 'غير مدفوع',
                    number_format((float) $expense->amount, 2, '.', ''),
                    number_format((float) $expense->tax_amount, 2, '.', ''),
                    $expense->reference_number,
                    $expense->hasAttachment() ? ($expense->attachment_original_name ?: $expense->attachment_path) : '',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
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

        $attachmentData = $this->storeAttachment($request);

        Expense::query()->create([
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'user_id' => $request->user()?->id,
            'code' => $this->generateNextExpenseCode((int) $branch->company_id),
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

    private function expenseFilters(Request $request): array
    {
        return [
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'branch_id' => $request->input('branch_id'),
            'expense_category_id' => $request->input('expense_category_id'),
            'payment_method' => $request->input('payment_method'),
            'payment_status' => $request->input('payment_status'),
            'attachment_status' => $request->input('attachment_status'),
        ];
    }

    private function filteredExpensesQuery(array $filters): Builder
    {
        $expensesQuery = Expense::query()
            ->with(['branch', 'category', 'user']);

        if (! empty($filters['from_date'])) {
            $expensesQuery->whereDate('expense_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $expensesQuery->whereDate('expense_date', '<=', $filters['to_date']);
        }

        $this->applyNonDateExpenseFilters($expensesQuery, $filters);

        if (($filters['large_amount'] ?? null) === '1') {
            $expensesQuery->where('amount', '>=', 1000);
        }

        return $expensesQuery;
    }

    private function largeAmountTopExpenses(array $filters)
    {
        return $this->filteredExpensesQuery($filters)
            ->where('amount', '>=', 1000)
            ->orderByDesc('amount')
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();
    }
    private function largeAmountAlert(array $filters): array
    {
        $threshold = 1000;

        $largeAmountQuery = $this->filteredExpensesQuery($filters)
            ->where('amount', '>=', $threshold);

        $quickFilter = $filters;
        unset($quickFilter['page']);
        $quickFilter['large_amount'] = '1';

        $quickFilter = array_filter(
            $quickFilter,
            fn ($value): bool => $value !== null && $value !== ''
        );

        return [
            'threshold' => $threshold,
            'count' => (clone $largeAmountQuery)->count(),
            'total_amount' => round((float) (clone $largeAmountQuery)->sum('amount'), 2),
            'highest' => (clone $largeAmountQuery)
                ->orderByDesc('amount')
                ->orderByDesc('expense_date')
                ->orderByDesc('id')
                ->first(),
            'quick_filter_url' => route('expenses.index', $quickFilter),
        ];
    }
    private function unpaidExpenseAlert(array $filters): array
    {
        $unpaidQuery = $this->filteredExpensesQuery($filters)
            ->where('is_paid', false);

        $oldestExpense = (clone $unpaidQuery)
            ->oldest('expense_date')
            ->oldest('id')
            ->first();

        return [
            'count' => (clone $unpaidQuery)->count(),
            'total_amount' => round((float) (clone $unpaidQuery)->sum('amount'), 2),
            'oldest_expense' => $oldestExpense,
            'oldest_date' => $oldestExpense?->expense_date?->format('Y-m-d'),
        ];
    }

    private function monthlyExpenseSummary(array $filters): array
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $monthlyQuery = Expense::query()
            ->with(['category'])
            ->whereDate('expense_date', '>=', $monthStart)
            ->whereDate('expense_date', '<=', $monthEnd);

        $this->applyNonDateExpenseFilters($monthlyQuery, $filters);

        $topCategoryRow = (clone $monthlyQuery)
            ->selectRaw('expense_category_id, SUM(amount) as total_amount')
            ->groupBy('expense_category_id')
            ->orderByDesc('total_amount')
            ->first();

        $topCategory = null;

        if ($topCategoryRow) {
            $category = ExpenseCategory::query()->find($topCategoryRow->expense_category_id);

            $topCategory = [
                'name' => $category?->name ?? 'غير محدد',
                'amount' => round((float) $topCategoryRow->total_amount, 2),
            ];
        }

        return [
            'month_label' => now()->format('Y-m'),
            'total_amount' => round((float) (clone $monthlyQuery)->sum('amount'), 2),
            'paid_amount' => round((float) (clone $monthlyQuery)->where('is_paid', true)->sum('amount'), 2),
            'unpaid_amount' => round((float) (clone $monthlyQuery)->where('is_paid', false)->sum('amount'), 2),
            'top_category' => $topCategory,
        ];
    }

    private function missingAttachmentSummary(array $filters): array
    {
        $missingAttachmentQuery = $this->filteredExpensesQuery($filters);

        $this->applyAttachmentStatusFilter($missingAttachmentQuery, 'without_attachment');

        return [
            'count' => (clone $missingAttachmentQuery)->count(),
            'total_amount' => round((float) (clone $missingAttachmentQuery)->sum('amount'), 2),
        ];
    }

    private function applyNonDateExpenseFilters(Builder $expensesQuery, array $filters): void
    {
        if (! empty($filters['branch_id'])) {
            $expensesQuery->where('branch_id', $filters['branch_id']);
        }

        if (! empty($filters['expense_category_id'])) {
            $expensesQuery->where('expense_category_id', $filters['expense_category_id']);
        }

        if (! empty($filters['payment_method'])) {
            $expensesQuery->where('payment_method', $filters['payment_method']);
        }

        if (($filters['payment_status'] ?? null) === 'paid') {
            $expensesQuery->where('is_paid', true);
        }

        if (($filters['payment_status'] ?? null) === 'unpaid') {
            $expensesQuery->where('is_paid', false);
        }

        $this->applyAttachmentStatusFilter($expensesQuery, $filters['attachment_status'] ?? null);
    }

    private function applyAttachmentStatusFilter(Builder $expensesQuery, ?string $attachmentStatus): void
    {
        if ($attachmentStatus === 'with_attachment') {
            $expensesQuery->whereNotNull('attachment_path')
                ->where('attachment_path', '!=', '');
        }

        if ($attachmentStatus === 'without_attachment') {
            $expensesQuery->where(function (Builder $query): void {
                $query->whereNull('attachment_path')
                    ->orWhere('attachment_path', '');
            });
        }
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

    private function generateNextExpenseCode(int $companyId): string
    {
        $codes = Expense::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', self::EXPENSE_CODE_PREFIX . '%')
            ->pluck('code');

        $lastNumber = 0;

        foreach ($codes as $code) {
            if (! preg_match('/^' . preg_quote(self::EXPENSE_CODE_PREFIX, '/') . '(\d+)$/', (string) $code, $matches)) {
                continue;
            }

            $lastNumber = max($lastNumber, (int) $matches[1]);
        }

        return self::EXPENSE_CODE_PREFIX . str_pad((string) ($lastNumber + 1), 6, '0', STR_PAD_LEFT);
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

    private function attachmentStatuses(): array
    {
        return [
            'with_attachment' => 'بها مرفق',
            'without_attachment' => 'بدون مرفق',
        ];
    }
}
