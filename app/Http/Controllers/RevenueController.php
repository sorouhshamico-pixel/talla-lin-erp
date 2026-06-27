<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RevenueController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->revenueFilters($request);

        $branches = Branch::query()
            ->where('is_active', true)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get();

        $categories = RevenueCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $collectionMethods = $this->collectionMethods();
        $collectionStatuses = $this->collectionStatuses();

        $revenuesQuery = $this->filteredRevenuesQuery($filters);

        $revenueTotals = [
            'count' => (clone $revenuesQuery)->count(),
            'amount' => round((float) (clone $revenuesQuery)->sum('amount'), 2),
            'tax_amount' => round((float) (clone $revenuesQuery)->sum('tax_amount'), 2),
            'collected_amount' => round((float) (clone $revenuesQuery)->where('is_collected', true)->sum('amount'), 2),
            'uncollected_amount' => round((float) (clone $revenuesQuery)->where('is_collected', false)->sum('amount'), 2),
        ];

        $revenues = $revenuesQuery
            ->latest('revenue_date')
            ->latest('id')
            ->get();

        return view('revenues.index', [
            'revenues' => $revenues,
            'branches' => $branches,
            'categories' => $categories,
            'collectionMethods' => $collectionMethods,
            'collectionStatuses' => $collectionStatuses,
            'filters' => $filters,
            'revenueTotals' => $revenueTotals,
        ]);
    }

    public function create(): View
    {
        $branches = Branch::query()
            ->where('is_active', true)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get();

        $categories = RevenueCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('revenues.create', [
            'branches' => $branches,
            'categories' => $categories,
            'collectionMethods' => $this->collectionMethods(),
            'collectionStatuses' => $this->collectionStatuses(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'revenue_category_id' => ['required', 'integer', 'exists:revenue_categories,id'],
            'revenue_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'collection_method' => ['required', 'string', 'in:' . implode(',', array_keys($this->collectionMethods()))],
            'collection_status' => ['required', 'string', 'in:collected,uncollected'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $company = Company::query()->firstOrFail();

        Revenue::query()->create([
            'company_id' => $company->id,
            'branch_id' => $validated['branch_id'],
            'revenue_category_id' => $validated['revenue_category_id'],
            'code' => $this->nextRevenueCode(),
            'revenue_date' => $validated['revenue_date'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'tax_amount' => $validated['tax_amount'] ?? 0,
            'collection_method' => $validated['collection_method'],
            'is_collected' => $validated['collection_status'] === 'collected',
            'reference_number' => $validated['reference_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('revenues.index')
            ->with('success', 'تم إضافة الإيراد بنجاح.');
    }

    private function filteredRevenuesQuery(array $filters): Builder
    {
        $query = Revenue::query()
            ->with(['branch', 'category']);

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (! empty($filters['revenue_category_id'])) {
            $query->where('revenue_category_id', $filters['revenue_category_id']);
        }

        if (! empty($filters['collection_method'])) {
            $query->where('collection_method', $filters['collection_method']);
        }

        if (($filters['collection_status'] ?? null) === 'collected') {
            $query->where('is_collected', true);
        }

        if (($filters['collection_status'] ?? null) === 'uncollected') {
            $query->where('is_collected', false);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('revenue_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('revenue_date', '<=', $filters['date_to']);
        }

        return $query;
    }

    private function revenueFilters(Request $request): array
    {
        return [
            'branch_id' => $request->query('branch_id'),
            'revenue_category_id' => $request->query('revenue_category_id'),
            'collection_method' => $request->query('collection_method'),
            'collection_status' => $request->query('collection_status'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];
    }

    private function collectionMethods(): array
    {
        return [
            'cash' => 'نقدًا',
            'bank_transfer' => 'تحويل بنكي',
            'mada' => 'مدى',
            'visa' => 'بطاقة',
            'cheque' => 'شيك',
        ];
    }

    private function collectionStatuses(): array
    {
        return [
            'collected' => 'محصل',
            'uncollected' => 'غير محصل',
        ];
    }

    private function nextRevenueCode(): string
    {
        $nextId = ((int) Revenue::query()->max('id')) + 1;

        return 'REV-' . Str::padLeft((string) $nextId, 5, '0');
    }
}
