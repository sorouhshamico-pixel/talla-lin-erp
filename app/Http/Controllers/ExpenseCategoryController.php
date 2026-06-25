<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    public function index(): View
    {
        $expenseCategories = ExpenseCategory::query()
            ->withCount('expenses')
            ->orderBy('name')
            ->paginate(15);

        return view('expense-categories.index', compact('expenseCategories'));
    }

    public function create(): View
    {
        return view('expense-categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['nullable', 'integer', Rule::exists('companies', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $slug = $this->normalizeSlug($validated['slug'] ?: $validated['name']);

        if ($this->slugExists($slug)) {
            return back()
                ->withErrors(['slug' => 'الـ slug مستخدم من قبل.'])
                ->withInput();
        }

        $payload = [
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ];

        if (Schema::hasColumn('expense_categories', 'company_id')) {
            $companyId = $this->resolveCompanyId($request);

            if (! $companyId) {
                return back()
                    ->withErrors(['company_id' => 'لم يتم تحديد الشركة المرتبطة بالتصنيف.'])
                    ->withInput();
            }

            $payload['company_id'] = $companyId;
        }

        ExpenseCategory::query()->create($payload);

        return redirect()
            ->route('expense-categories.index')
            ->with('success', 'تم إنشاء تصنيف المصروف بنجاح.');
    }

    public function edit(ExpenseCategory $expenseCategory): View
    {
        return view('expense-categories.edit', compact('expenseCategory'));
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['nullable', 'integer', Rule::exists('companies', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = $this->normalizeSlug($validated['slug'] ?: $validated['name']);

        if ($this->slugExists($slug, $expenseCategory->id)) {
            return back()
                ->withErrors(['slug' => 'الـ slug مستخدم من قبل.'])
                ->withInput();
        }

        $payload = [
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];

        if (Schema::hasColumn('expense_categories', 'company_id') && $request->filled('company_id')) {
            $payload['company_id'] = (int) $request->input('company_id');
        }

        $expenseCategory->update($payload);

        return redirect()
            ->route('expense-categories.index')
            ->with('success', 'تم تحديث تصنيف المصروف بنجاح.');
    }

    public function toggle(ExpenseCategory $expenseCategory): RedirectResponse
    {
        return $this->toggleExpenseCategoryStatus($expenseCategory);
    }

    public function toggleStatus(ExpenseCategory $expenseCategory): RedirectResponse
    {
        return $this->toggleExpenseCategoryStatus($expenseCategory);
    }

    public function destroy(ExpenseCategory $expenseCategory): RedirectResponse
    {
        if ($expenseCategory->expenses()->exists()) {
            return redirect()
                ->route('expense-categories.index')
                ->with('error', 'لا يمكن حذف تصنيف مرتبط بمصاريف مسجلة.');
        }

        $expenseCategory->delete();

        return redirect()
            ->route('expense-categories.index')
            ->with('success', 'تم حذف تصنيف المصروف بنجاح.');
    }

    private function toggleExpenseCategoryStatus(ExpenseCategory $expenseCategory): RedirectResponse
    {
        $expenseCategory->update([
            'is_active' => ! $expenseCategory->is_active,
        ]);

        return redirect()
            ->route('expense-categories.index')
            ->with(
                'success',
                $expenseCategory->is_active
                    ? 'تم تفعيل تصنيف المصروف بنجاح.'
                    : 'تم تعطيل تصنيف المصروف بنجاح.'
            );
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $query = ExpenseCategory::query()->where('slug', $slug);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    private function resolveCompanyId(Request $request): ?int
    {
        if ($request->filled('company_id')) {
            return (int) $request->input('company_id');
        }

        $user = $request->user();

        if ($user && $user->getAttribute('company_id')) {
            return (int) $user->getAttribute('company_id');
        }

        if (Schema::hasTable('companies')) {
            $companyId = DB::table('companies')->value('id');

            if ($companyId) {
                return (int) $companyId;
            }
        }

        return null;
    }

    private function normalizeSlug(string $value): string
    {
        $slug = Str::slug($value);

        if ($slug === '') {
            $slug = preg_replace('/\s+/u', '-', trim($value));
            $slug = mb_strtolower($slug);
        }

        return trim($slug, '-');
    }
}
