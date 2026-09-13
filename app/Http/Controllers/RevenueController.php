<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $archiveStatuses = $this->archiveStatuses();

        $revenuesQuery = $this->filteredRevenuesQuery($filters);

        $revenueTotals = [
            'count' => (clone $revenuesQuery)->count(),
            'amount' => round((float) (clone $revenuesQuery)->sum('amount'), 2),
            'tax_amount' => round((float) (clone $revenuesQuery)->sum('tax_amount'), 2),
            'collected_amount' => round((float) (clone $revenuesQuery)->where('is_collected', true)->sum('amount'), 2),
            'uncollected_amount' => round((float) (clone $revenuesQuery)->where('is_collected', false)->sum('amount'), 2),
        ];

        $uncollectedRevenueSummary = $this->uncollectedRevenueSummary($filters);

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
            'archiveStatuses' => $archiveStatuses,
            'filters' => $filters,
            'revenueTotals' => $revenueTotals,
            'uncollectedRevenueSummary' => $uncollectedRevenueSummary,
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

        $branch = Branch::query()->findOrFail($validated['branch_id']);
        $category = RevenueCategory::query()->findOrFail($validated['revenue_category_id']);

        $companyValidationResponse = $this->validateCategoryAndBranchCompany($category, $branch);

        if ($companyValidationResponse) {
            return $companyValidationResponse;
        }

        try {
            DB::transaction(function () use ($branch, $category, $validated): void {
                Revenue::query()->create([
                    'company_id' => $branch->company_id,
                    'branch_id' => $branch->id,
                    'revenue_category_id' => $category->id,
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
            });
        } catch (UniqueConstraintViolationException) {
            return back()
                ->withErrors(['code' => 'حدث تعارض أثناء توليد رقم الإيراد، الرجاء إعادة المحاولة.'])
                ->withInput();
        }

        return redirect()
            ->route('revenues.index')
            ->with('success', 'تم إضافة الإيراد بنجاح.');
    }

    public function edit(Revenue $revenue): View
    {
        $branches = Branch::query()
            ->where(function ($query) use ($revenue): void {
                $query->where('is_active', true)
                    ->orWhere('id', $revenue->branch_id);
            })
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get();

        $categories = RevenueCategory::query()
            ->where(function ($query) use ($revenue): void {
                $query->where('is_active', true)
                    ->orWhere('id', $revenue->revenue_category_id);
            })
            ->orderBy('name')
            ->get();

        return view('revenues.edit', [
            'revenue' => $revenue,
            'branches' => $branches,
            'categories' => $categories,
            'collectionMethods' => $this->collectionMethods(),
            'collectionStatuses' => $this->collectionStatuses(),
        ]);
    }

    public function update(Request $request, Revenue $revenue): RedirectResponse
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

        $branch = Branch::query()->findOrFail($validated['branch_id']);
        $category = RevenueCategory::query()->findOrFail($validated['revenue_category_id']);

        $companyValidationResponse = $this->validateCategoryAndBranchCompany($category, $branch);

        if ($companyValidationResponse) {
            return $companyValidationResponse;
        }

        $revenue->update([
            'branch_id' => $branch->id,
            'revenue_category_id' => $category->id,
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
            ->with('success', 'تم تحديث الإيراد بنجاح.');
    }

    public function toggleCollection(Revenue $revenue): RedirectResponse
    {
        $revenue->update([
            'is_collected' => ! $revenue->is_collected,
        ]);

        return redirect()
            ->route('revenues.index')
            ->with('success', $revenue->is_collected ? 'تم تعليم الإيراد كمحصل.' : 'تم تعليم الإيراد كغير محصل.');
    }

    public function archive(Revenue $revenue): RedirectResponse
    {
        $revenue->update([
            'archived_at' => now(),
        ]);

        return redirect()
            ->route('revenues.index')
            ->with('success', 'تمت أرشفة الإيراد بنجاح.');
    }

    public function restore(Revenue $revenue): RedirectResponse
    {
        $revenue->update([
            'archived_at' => null,
        ]);

        return redirect()
            ->route('revenues.index', ['archive_status' => 'archived'])
            ->with('success', 'تمت استعادة الإيراد بنجاح.');
    }

    private function uncollectedRevenueSummary(array $filters): array
    {
        $query = $this->filteredRevenuesQuery($filters)
            ->where('is_collected', false);

        return [
            'count' => (clone $query)->count(),
            'amount' => round((float) (clone $query)->sum('amount'), 2),
        ];
    }
    private function filteredRevenuesQuery(array $filters): Builder
    {
        $query = Revenue::query()
            ->with(['branch', 'category']);

        if (($filters['archive_status'] ?? 'active') === 'archived') {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

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
            'archive_status' => $request->query('archive_status', 'active'),
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


    private function archiveStatuses(): array
    {
        return [
            'active' => 'الإيرادات النشطة',
            'archived' => 'الإيرادات المؤرشفة',
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
        $nextId = ((int) Revenue::query()->lockForUpdate()->max('id')) + 1;

        return 'REV-' . Str::padLeft((string) $nextId, 5, '0');
    }

    private function validateCategoryAndBranchCompany(RevenueCategory $category, Branch $branch): ?RedirectResponse
    {
        if ($category->company_id !== $branch->company_id) {
            return back()
                ->withErrors(['revenue_category_id' => 'تصنيف الإيراد لا يتبع نفس شركة الفرع.'])
                ->withInput();
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function preloadNameMap(string $table): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
            return [];
        }

        $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);
        $candidateColumns = array_values(array_intersect(['name', 'title', 'description', 'code'], $columns));

        if ($candidateColumns === []) {
            return [];
        }

        return \Illuminate\Support\Facades\DB::table($table)
            ->get(array_merge(['id'], $candidateColumns))
            ->mapWithKeys(function ($record) use ($candidateColumns) {
                foreach ($candidateColumns as $column) {
                    if (($record->{$column} ?? null) !== null && $record->{$column} !== '') {
                        return [$record->id => (string) $record->{$column}];
                    }
                }

                return [$record->id => (string) $record->id];
            })
            ->all();
    }

    public function exportUncollectedCsv()
    {
        $revenue = new \App\Models\Revenue();
        $table = $revenue->getTable();
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);

        $orderColumn = 'id';

        foreach (['revenue_date', 'date', 'created_at', 'id'] as $candidateColumn) {
            if (in_array($candidateColumn, $columns, true)) {
                $orderColumn = $candidateColumn;
                break;
            }
        }

        $fileName = 'uncollected-revenues-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($columns, $orderColumn) {
            $output = fopen('php://output', 'w');

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $columns);

            $query = \App\Models\Revenue::query();
            if (in_array('is_collected', $columns, true)) {
                $query->where('is_collected', false);
            } elseif (in_array('collected_at', $columns, true)) {
                $query->whereNull('collected_at');
            } else {
                $remainingColumn = null;

                foreach (['remaining_amount', 'balance', 'due_amount', 'uncollected_amount'] as $candidateColumn) {
                    if (in_array($candidateColumn, $columns, true)) {
                        $remainingColumn = $candidateColumn;
                        break;
                    }
                }

                if ($remainingColumn !== null) {
                    $query->where($remainingColumn, '>', 0);
                } else {
                    $amountPairs = [
                        ['amount', 'collected_amount'],
                        ['amount', 'paid_amount'],
                        ['amount', 'received_amount'],
                        ['total_amount', 'collected_amount'],
                        ['total_amount', 'paid_amount'],
                        ['total_amount', 'received_amount'],
                        ['invoice_amount', 'collected_amount'],
                        ['invoice_amount', 'paid_amount'],
                        ['invoice_amount', 'received_amount'],
                    ];

                    $amountFilterApplied = false;

                    foreach ($amountPairs as [$amountColumn, $collectedColumn]) {
                        if (
                            in_array($amountColumn, $columns, true)
                            && in_array($collectedColumn, $columns, true)
                        ) {
                            $query->where(function ($subQuery) use ($amountColumn, $collectedColumn) {
                                $subQuery
                                    ->whereNull($collectedColumn)
                                    ->orWhereColumn($collectedColumn, '<', $amountColumn);
                            });

                            $amountFilterApplied = true;
                            break;
                        }
                    }

                    if (! $amountFilterApplied) {
                        $statusColumn = null;

                        foreach (['collection_status', 'payment_status', 'status'] as $candidateColumn) {
                            if (in_array($candidateColumn, $columns, true)) {
                                $statusColumn = $candidateColumn;
                                break;
                            }
                        }

                        if ($statusColumn !== null) {
                            $query->whereIn($statusColumn, [
                                'uncollected',
                                'unpaid',
                                'pending',
                                'partial',
                                'partially_paid',
                                'overdue',
                                'due',
                                'not_collected',
                                'not_paid',
                                'غير محصل',
                                'غير محصلة',
                                'غير مدفوع',
                                'جزئي',
                            ]);
                        } else {
                            $query->whereRaw('1 = 0');
                        }
                    }
                }
            }

            $query
                ->orderBy($orderColumn, 'desc')
                ->chunk(200, function ($revenues) use ($output, $columns) {
                    foreach ($revenues as $revenue) {
                        $row = [];

                        foreach ($columns as $column) {
                            $value = $revenue->{$column};

                            if ($value instanceof \Carbon\CarbonInterface) {
                                $value = $value->format('Y-m-d H:i:s');
                            }

                            if (is_bool($value)) {
                                $value = $value ? '1' : '0';
                            }

                            if (is_array($value) || is_object($value)) {
                                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                            }

                            $row[] = $value;
                        }

                        fputcsv($output, $row);
                    }
                });

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportCsv(\Illuminate\Http\Request $request)
    {
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing((new \App\Models\Revenue())->getTable());

        $fileName = 'revenues-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($request, $columns) {
            $output = fopen('php://output', 'w');

            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'الكود',
                'التاريخ',
                'الوصف',
                'الفرع',
                'التصنيف',
                'طريقة التحصيل',
                'حالة التحصيل',
                'المبلغ',
                'الضريبة',
                'رقم المرجع',
                'ملاحظات',
                'حالة الأرشفة',
            ]);

            $query = \App\Models\Revenue::query();

            if (in_array('is_collected', $columns, true) && $request->has('is_collected')) {
                $isCollected = filter_var($request->input('is_collected'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($isCollected === null) {
                    $isCollected = in_array((string) $request->input('is_collected'), ['1', 'yes', 'on', 'true'], true);
                }

                $query->where('is_collected', $isCollected);
            }

            if (in_array('is_collected', $columns, true) && $request->boolean('uncollected')) {
                $query->where('is_collected', false);
            }

            // 12J_COLLECTION_STATUS_TO_IS_COLLECTED_FILTER
            if (in_array('is_collected', $columns, true)) {
                $collectionStatusFilter = $request->input('collection_status')
                    ?? $request->input('payment_status')
                    ?? $request->input('collection_filter')
                    ?? $request->input('payment_filter')
                    ?? $request->input('collected')
                    ?? null;

                if ($collectionStatusFilter !== null && $collectionStatusFilter !== '') {
                    $normalizedCollectionStatus = trim((string) $collectionStatusFilter);

                    if (in_array($normalizedCollectionStatus, [
                        'collected',
                        'paid',
                        'received',
                        'محصل',
                        'محصلة',
                        'مدفوع',
                        '1',
                        'true',
                        'yes',
                        'on',
                    ], true)) {
                        $query->where('is_collected', true);
                    } elseif (in_array($normalizedCollectionStatus, [
                        'uncollected',
                        'unpaid',
                        'not_collected',
                        'not_paid',
                        'pending',
                        'غير محصل',
                        'غير محصلة',
                        'غير مدفوع',
                        '0',
                        'false',
                        'no',
                        'off',
                    ], true)) {
                        $query->where('is_collected', false);
                    }
                }
            }

            if (in_array('collection_method', $columns, true) && $request->filled('collection_method')) {
                $query->where('collection_method', $request->input('collection_method'));
            }

            foreach ([
                'company_id',
                'branch_id',
                'revenue_category_id',
                'customer_id',
                'client_id',
                'project_id',
                'invoice_id',
            ] as $filterColumn) {
                if (in_array($filterColumn, $columns, true) && $request->filled($filterColumn)) {
                    $query->where($filterColumn, $request->input($filterColumn));
                }
            }

            $archiveFilter = $request->input('archived')
                ?? $request->input('archive_status')
                ?? $request->input('status');

            if (in_array('archived_at', $columns, true) && $archiveFilter !== null && $archiveFilter !== '') {
                if (in_array($archiveFilter, ['active', 'نشط', '0', 0, false], true)) {
                    $query->whereNull('archived_at');
                }

                if (in_array($archiveFilter, ['archived', 'مؤرشف', '1', 1, true], true)) {
                    $query->whereNotNull('archived_at');
                }
            }

            $dateColumn = null;

            foreach (['revenue_date', 'date', 'created_at'] as $candidateColumn) {
                if (in_array($candidateColumn, $columns, true)) {
                    $dateColumn = $candidateColumn;
                    break;
                }
            }

            if ($dateColumn !== null) {
                $from = $request->input('from')
                    ?? $request->input('date_from')
                    ?? $request->input('start_date')
                    ?? $request->input('from_date');

                $to = $request->input('to')
                    ?? $request->input('date_to')
                    ?? $request->input('end_date')
                    ?? $request->input('to_date');

                if (! empty($from)) {
                    $query->whereDate($dateColumn, '>=', $from);
                }

                if (! empty($to)) {
                    $query->whereDate($dateColumn, '<=', $to);
                }
            }

            $search = $request->input('search')
                ?? $request->input('q')
                ?? $request->input('keyword');

            if (! empty($search)) {
                $searchableColumns = array_values(array_intersect($columns, [
                    'description',
                    'notes',
                    'reference_number',
                    'reference',
                    'invoice_number',
                    'customer_name',
                    'client_name',
                    'source',
                    'title',
                    'name',
                    'code',
                ]));

                if ($searchableColumns !== []) {
                    $query->where(function ($subQuery) use ($searchableColumns, $search) {
                        foreach ($searchableColumns as $column) {
                            $subQuery->orWhere($column, 'like', '%' . $search . '%');
                        }
                    });
                }
            }

            $branchNames = $this->preloadNameMap('branches');
            $categoryNames = $this->preloadNameMap('revenue_categories');

            $lookupName = static function (?int $id, array $map): string {
                if ($id === null) {
                    return '';
                }

                return $map[$id] ?? '';
            };

            $formatDate = static function ($value): string {
                if ($value === null || $value === '') {
                    return '';
                }

                try {
                    return \Carbon\Carbon::parse($value)->format('Y-m-d');
                } catch (\Throwable $exception) {
                    return (string) $value;
                }
            };

            $collectionMethodLabel = static function ($value): string {
                return match ((string) $value) {
                    'cash' => 'نقدًا',
                    'bank_transfer' => 'تحويل بنكي',
                    'transfer' => 'تحويل بنكي',
                    'card' => 'بطاقة',
                    'cheque', 'check' => 'شيك',
                    default => (string) $value,
                };
            };

            $collectionStatusLabel = static function ($value): string {
                return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'محصل' : 'غير محصل';
            };

            $archiveStatusLabel = static function ($value): string {
                return $value === null || $value === '' ? 'نشط' : 'مؤرشف';
            };

            $orderColumn = in_array('id', $columns, true) ? 'id' : 'created_at';

            $query
                ->orderBy($orderColumn, 'desc')
                ->chunk(200, function ($revenues) use (
                    $output,
                    $lookupName,
                    $branchNames,
                    $categoryNames,
                    $formatDate,
                    $collectionMethodLabel,
                    $collectionStatusLabel,
                    $archiveStatusLabel
                ) {
                    foreach ($revenues as $revenue) {
                        fputcsv($output, [
                            $revenue->code ?? '',
                            $formatDate($revenue->revenue_date ?? $revenue->date ?? $revenue->created_at ?? null),
                            $revenue->description ?? '',
                            $lookupName(isset($revenue->branch_id) ? (int) $revenue->branch_id : null, $branchNames),
                            $lookupName(isset($revenue->revenue_category_id) ? (int) $revenue->revenue_category_id : null, $categoryNames),
                            $collectionMethodLabel($revenue->collection_method ?? ''),
                            $collectionStatusLabel($revenue->is_collected ?? false),
                            $revenue->amount ?? '',
                            $revenue->tax_amount ?? '',
                            $revenue->reference_number ?? $revenue->reference ?? '',
                            $revenue->notes ?? '',
                            $archiveStatusLabel($revenue->archived_at ?? null),
                        ]);
                    }
                });

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

}
