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
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->revenueFilters($request);

        $revenues = $this->filteredRevenuesQuery($filters)
            ->latest('revenue_date')
            ->latest('id')
            ->get();

        $fileName = 'revenues-report-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($revenues): void {
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
                'طريقة التحصيل',
                'حالة التحصيل',
                'المبلغ',
                'الضريبة',
                'رقم المرجع',
                'ملاحظات',
                'حالة الأرشفة',
            ]);

            foreach ($revenues as $revenue) {
                fputcsv($handle, [
                    $revenue->code,
                    $revenue->revenue_date?->format('Y-m-d'),
                    $revenue->description,
                    $revenue->branch?->name_ar ?? $revenue->branch?->name ?? $revenue->branch?->name_en ?? '',
                    $revenue->category?->name ?? '',
                    $revenue->displayCollectionMethod(),
                    $revenue->is_collected ? 'محصل' : 'غير محصل',
                    number_format((float) $revenue->amount, 2, '.', ''),
                    number_format((float) $revenue->tax_amount, 2, '.', ''),
                    $revenue->reference_number,
                    $revenue->notes,
                    $revenue->archived_at ? 'مؤرشف' : 'نشط',
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

    public function edit(Revenue $revenue): View
    {
        $branches = Branch::query()
            ->where('is_active', true)
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

        $revenue->update([
            'branch_id' => $validated['branch_id'],
            'revenue_category_id' => $validated['revenue_category_id'],
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
        $nextId = ((int) Revenue::query()->max('id')) + 1;

        return 'REV-' . Str::padLeft((string) $nextId, 5, '0');
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

            $lookupName = static function (?int $id, string $table): string {
                if ($id === null || ! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                    return '';
                }

                $record = \Illuminate\Support\Facades\DB::table($table)->where('id', $id)->first();

                if (! $record) {
                    return '';
                }

                foreach (['name', 'title', 'description', 'code'] as $column) {
                    if (property_exists($record, $column) && $record->{$column} !== null && $record->{$column} !== '') {
                        return (string) $record->{$column};
                    }
                }

                return (string) $id;
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
                            $lookupName(isset($revenue->branch_id) ? (int) $revenue->branch_id : null, 'branches'),
                            $lookupName(isset($revenue->revenue_category_id) ? (int) $revenue->revenue_category_id : null, 'revenue_categories'),
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

    private function applyRevenueCsvRequestFilters($query, \Illuminate\Http\Request $request, array $columns): void
    {
        foreach ([
            'company_id',
            'branch_id',
            'customer_id',
            'client_id',
            'project_id',
            'invoice_id',
            'status',
            'payment_status',
            'collection_status',
        ] as $column) {
            if (in_array($column, $columns, true) && $request->filled($column)) {
                $query->where($column, $request->input($column));
            }
        }

        if (in_array('is_collected', $columns, true) && $request->filled('is_collected')) {
            $query->where('is_collected', filter_var($request->input('is_collected'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($this->revenueCsvUncollectedFilterRequested($request)) {
            $this->applyRevenueCsvUncollectedFilter($query, $columns);
        }

        $dateColumn = $this->revenueFilteredCsvDateColumn($columns);

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
                'reference',
                'invoice_number',
                'customer_name',
                'client_name',
                'source',
                'title',
                'name',
            ]));

            if ($searchableColumns !== []) {
                $query->where(function ($subQuery) use ($searchableColumns, $search) {
                    foreach ($searchableColumns as $column) {
                        $subQuery->orWhere($column, 'like', '%' . $search . '%');
                    }
                });
            }
        }
    }

    private function revenueCsvUncollectedFilterRequested(\Illuminate\Http\Request $request): bool
    {
        foreach (['uncollected', 'uncollected_only', 'only_uncollected'] as $key) {
            if ($request->has($key) && filter_var($request->input($key), FILTER_VALIDATE_BOOLEAN)) {
                return true;
            }
        }

        foreach (['quick_filter', 'filter', 'status', 'payment_status', 'collection_status'] as $key) {
            if (! $request->filled($key)) {
                continue;
            }

            if (in_array($request->input($key), [
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
            ], true)) {
                return true;
            }
        }

        return false;
    }

    private function applyRevenueCsvUncollectedFilter($query, array $columns): void
    {
        if (in_array('is_collected', $columns, true)) {
            $query->where('is_collected', false);

            return;
        }

        if (in_array('collected_at', $columns, true)) {
            $query->whereNull('collected_at');

            return;
        }

        foreach (['remaining_amount', 'balance', 'due_amount', 'uncollected_amount'] as $remainingColumn) {
            if (in_array($remainingColumn, $columns, true)) {
                $query->where($remainingColumn, '>', 0);

                return;
            }
        }

        foreach ([
            ['amount', 'collected_amount'],
            ['amount', 'paid_amount'],
            ['amount', 'received_amount'],
            ['total_amount', 'collected_amount'],
            ['total_amount', 'paid_amount'],
            ['total_amount', 'received_amount'],
            ['invoice_amount', 'collected_amount'],
            ['invoice_amount', 'paid_amount'],
            ['invoice_amount', 'received_amount'],
        ] as [$amountColumn, $collectedColumn]) {
            if (
                in_array($amountColumn, $columns, true)
                && in_array($collectedColumn, $columns, true)
            ) {
                $query->where(function ($subQuery) use ($amountColumn, $collectedColumn) {
                    $subQuery
                        ->whereNull($collectedColumn)
                        ->orWhereColumn($collectedColumn, '<', $amountColumn);
                });

                return;
            }
        }

        foreach (['collection_status', 'payment_status', 'status'] as $statusColumn) {
            if (in_array($statusColumn, $columns, true)) {
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

                return;
            }
        }

        $query->whereRaw('1 = 0');
    }

    private function revenueFilteredCsvDateColumn(array $columns): ?string
    {
        foreach (['revenue_date', 'date', 'created_at'] as $column) {
            if (in_array($column, $columns, true)) {
                return $column;
            }
        }

        return null;
    }

    private function revenueFilteredCsvOrderColumn(array $columns): string
    {
        foreach (['revenue_date', 'date', 'created_at', 'id'] as $column) {
            if (in_array($column, $columns, true)) {
                return $column;
            }
        }

        return (new \App\Models\Revenue())->getKeyName();
    }

    public function exportRevenuesFilteredCsv12JFinal(\Illuminate\Http\Request $request)
    {
        $model = new \App\Models\Revenue();
        $table = $model->getTable();
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);

        $fileName = 'revenues-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($request, $columns) {
            $output = fopen('php://output', 'w');

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $columns);

            $query = \App\Models\Revenue::query();

            if ($this->revenueCsv12JFinalBooleanFilter($request, ['uncollected', 'uncollected_only', 'only_uncollected'])) {
                $this->applyRevenueCsv12JFinalUncollectedFilter($query, $columns);
            }

            foreach ([
                'company_id',
                'branch_id',
                'customer_id',
                'client_id',
                'project_id',
                'invoice_id',
                'status',
                'payment_status',
                'collection_status',
            ] as $column) {
                if (in_array($column, $columns, true) && $request->filled($column)) {
                    $query->where($column, $request->input($column));
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
                    'reference',
                    'invoice_number',
                    'customer_name',
                    'client_name',
                    'source',
                    'title',
                    'name',
                ]));

                if ($searchableColumns !== []) {
                    $query->where(function ($subQuery) use ($searchableColumns, $search) {
                        foreach ($searchableColumns as $column) {
                            $subQuery->orWhere($column, 'like', '%' . $search . '%');
                        }
                    });
                }
            }

            $orderColumn = 'id';

            foreach (['revenue_date', 'date', 'created_at', 'id'] as $candidateColumn) {
                if (in_array($candidateColumn, $columns, true)) {
                    $orderColumn = $candidateColumn;
                    break;
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

    private function revenueCsv12JFinalBooleanFilter(\Illuminate\Http\Request $request, array $keys): bool
    {
        foreach ($keys as $key) {
            if (! $request->query->has($key)) {
                continue;
            }

            $value = $request->query($key);

            return ! in_array($value, [false, 0, '0', 'false', 'off', 'no', null, ''], true);
        }

        return false;
    }

    private function applyRevenueCsv12JFinalUncollectedFilter($query, array $columns): void
    {
        $uncollectedValues = [
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
        ];

        foreach (['collection_status', 'payment_status', 'status'] as $statusColumn) {
            if (in_array($statusColumn, $columns, true)) {
                $query->whereIn($statusColumn, $uncollectedValues);

                return;
            }
        }

        if (in_array('is_collected', $columns, true)) {
            $query->where('is_collected', false);

            return;
        }

        if (in_array('collected_at', $columns, true)) {
            $query->whereNull('collected_at');

            return;
        }

        foreach (['remaining_amount', 'balance', 'due_amount', 'uncollected_amount'] as $remainingColumn) {
            if (in_array($remainingColumn, $columns, true)) {
                $query->where($remainingColumn, '>', 0);

                return;
            }
        }

        foreach ([
            ['amount', 'collected_amount'],
            ['amount', 'paid_amount'],
            ['amount', 'received_amount'],
            ['total_amount', 'collected_amount'],
            ['total_amount', 'paid_amount'],
            ['total_amount', 'received_amount'],
            ['invoice_amount', 'collected_amount'],
            ['invoice_amount', 'paid_amount'],
            ['invoice_amount', 'received_amount'],
        ] as [$amountColumn, $collectedColumn]) {
            if (
                in_array($amountColumn, $columns, true)
                && in_array($collectedColumn, $columns, true)
            ) {
                $query->where(function ($subQuery) use ($amountColumn, $collectedColumn) {
                    $subQuery
                        ->whereNull($collectedColumn)
                        ->orWhereColumn($collectedColumn, '<', $amountColumn);
                });

                return;
            }
        }

        $query->whereRaw('1 = 0');
    }
}
