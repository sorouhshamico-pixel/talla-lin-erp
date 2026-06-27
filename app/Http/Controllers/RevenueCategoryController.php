<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\RevenueCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RevenueCategoryController extends Controller
{
    public function index(): View
    {
        $categories = RevenueCategory::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('revenue_categories.index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = Company::query()->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $slug = $this->normalizeSlug($validated['slug'] ?? $validated['name']);

        $this->ensureSlugIsUnique($company->id, $slug);

        RevenueCategory::query()->create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return redirect()
            ->route('revenue-categories.index')
            ->with('success', 'تم إضافة تصنيف الإيراد بنجاح.');
    }

    public function edit(RevenueCategory $revenueCategory): View
    {
        return view('revenue_categories.edit', [
            'category' => $revenueCategory,
        ]);
    }

    public function update(Request $request, RevenueCategory $revenueCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $slug = $this->normalizeSlug($validated['slug'] ?? $validated['name']);

        $this->ensureSlugIsUnique($revenueCategory->company_id, $slug, $revenueCategory->id);

        $revenueCategory->update([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('revenue-categories.index')
            ->with('success', 'تم تحديث تصنيف الإيراد بنجاح.');
    }

    public function toggle(RevenueCategory $revenueCategory): RedirectResponse
    {
        $revenueCategory->update([
            'is_active' => ! $revenueCategory->is_active,
        ]);

        return redirect()
            ->route('revenue-categories.index')
            ->with('success', $revenueCategory->is_active ? 'تم تفعيل التصنيف.' : 'تم تعطيل التصنيف.');
    }

    private function normalizeSlug(string $value): string
    {
        $slug = Str::slug($value);

        if ($slug === '') {
            $slug = Str::slug(Str::ascii($value));
        }

        if ($slug === '') {
            $slug = 'revenue-category-' . now()->format('YmdHis');
        }

        return $slug;
    }

    private function ensureSlugIsUnique(int $companyId, string $slug, ?int $ignoreId = null): void
    {
        $exists = RevenueCategory::query()
            ->where('company_id', $companyId)
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'slug' => 'هذا الرابط المختصر مستخدم من قبل.',
            ]);
        }
    }
}
