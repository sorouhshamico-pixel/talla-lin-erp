<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\ReportSavedViewService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfitLossReportController extends Controller
{
    private const REPORT_KEY = 'profit-loss';

    private const FILTER_KEYS = [
        'from_date',
        'to_date',
        'branch_id',
    ];

    public function __invoke(Request $request, ReportSavedViewService $savedViews): View
    {
        $request = $this->requestWithDefaultSavedView($request, $savedViews);

        $filters = $this->filters($request);
        $summary = $this->summary($filters);

        $branches = Branch::query()
            ->where('is_active', true)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get();

        return view('reports.profit-loss', array_merge($summary, [
            'filters' => $filters,
            'branches' => $branches,
            'savedViews' => $savedViews->listForReport($request->user(), self::REPORT_KEY),
        ]));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $summary = $this->summary($filters);

        $fileName = 'profit-loss-report-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($summary, $filters): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['البند', 'القيمة']);

            fputcsv($handle, ['من تاريخ', $filters['from_date'] ?: 'كل الفترات']);
            fputcsv($handle, ['إلى تاريخ', $filters['to_date'] ?: 'كل الفترات']);
            fputcsv($handle, ['رقم الفرع', $filters['branch_id'] ?: 'كل الفروع']);
            fputcsv($handle, ['إجمالي الإيرادات', number_format((float) $summary['totalRevenues'], 2, '.', '')]);
            fputcsv($handle, ['إجمالي المصروفات', number_format((float) $summary['totalExpenses'], 2, '.', '')]);
            fputcsv($handle, ['صافي الربح / الخسارة', number_format((float) $summary['netProfit'], 2, '.', '')]);
            fputcsv($handle, ['ضريبة الإيرادات', number_format((float) $summary['totalRevenueTax'], 2, '.', '')]);
            fputcsv($handle, ['ضريبة المصروفات', number_format((float) $summary['totalExpenseTax'], 2, '.', '')]);
            fputcsv($handle, ['فرق الضريبة', number_format((float) $summary['taxDifference'], 2, '.', '')]);

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function storeSavedView(Request $request, ReportSavedViewService $savedViews): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'branch_id' => ['nullable', 'integer'],
            'is_default' => ['nullable'],
        ]);

        $filters = array_filter([
            'from_date' => $validated['from_date'] ?? null,
            'to_date' => $validated['to_date'] ?? null,
            'branch_id' => $validated['branch_id'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $savedViews->save(
            $request->user(),
            self::REPORT_KEY,
            $validated['name'],
            $filters,
            $request->boolean('is_default')
        );

        return redirect()
            ->route('reports.profit-loss', $filters)
            ->with('status', 'تم حفظ عرض تقرير الأرباح والخسائر بنجاح.');
    }

    private function requestWithDefaultSavedView(Request $request, ReportSavedViewService $savedViews): Request
    {
        foreach (self::FILTER_KEYS as $key) {
            if ($request->filled($key)) {
                return $request;
            }
        }

        $user = $request->user();

        if (! $user) {
            return $request;
        }

        $defaultSavedView = $savedViews->getDefault($user, self::REPORT_KEY);

        if (! $defaultSavedView) {
            return $request;
        }

        $filters = array_filter(
            $defaultSavedView->filters ?? [],
            fn ($value) => $value !== null && $value !== ''
        );

        if ($filters === []) {
            return $request;
        }

        return $request->merge($filters);
    }

    /**
     * @return array{from_date: mixed, to_date: mixed, branch_id: mixed}
     */
    private function filters(Request $request): array
    {
        return [
            'from_date' => $request->query('from_date'),
            'to_date' => $request->query('to_date'),
            'branch_id' => $request->query('branch_id'),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function summary(array $filters): array
    {
        $revenueQuery = DB::table('revenues');
        $this->applyFilters($revenueQuery, 'revenues', $filters, 'revenue_date');

        $expenseQuery = DB::table('expenses');
        $this->applyFilters($expenseQuery, 'expenses', $filters, 'expense_date');

        $totalRevenues = round((float) (clone $revenueQuery)->sum('amount'), 2);
        $totalRevenueTax = round((float) (clone $revenueQuery)->sum('tax_amount'), 2);

        $totalExpenses = round((float) (clone $expenseQuery)->sum('amount'), 2);
        $totalExpenseTax = round((float) (clone $expenseQuery)->sum('tax_amount'), 2);

        $netProfit = round($totalRevenues - $totalExpenses, 2);
        $taxDifference = round($totalRevenueTax - $totalExpenseTax, 2);

        return [
            'totalRevenues' => $totalRevenues,
            'totalRevenueTax' => $totalRevenueTax,
            'totalExpenses' => $totalExpenses,
            'totalExpenseTax' => $totalExpenseTax,
            'netProfit' => $netProfit,
            'taxDifference' => $taxDifference,
            'monthlySummary' => $this->monthlySummary($filters),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array{month: string, revenues: float, expenses: float, net_profit: float}>
     */
    private function monthlySummary(array $filters): array
    {
        $revenues = $this->monthlyTotals('revenues', 'revenue_date', $filters);
        $expenses = $this->monthlyTotals('expenses', 'expense_date', $filters);

        $months = collect(array_unique(array_merge(
            array_keys($revenues),
            array_keys($expenses)
        )))
            ->sort()
            ->values();

        return $months
            ->map(function (string $month) use ($revenues, $expenses): array {
                $monthRevenues = round((float) ($revenues[$month] ?? 0), 2);
                $monthExpenses = round((float) ($expenses[$month] ?? 0), 2);

                return [
                    'month' => $month,
                    'revenues' => $monthRevenues,
                    'expenses' => $monthExpenses,
                    'net_profit' => round($monthRevenues - $monthExpenses, 2),
                ];
            })
            ->all();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, float>
     */
    private function monthlyTotals(string $table, string $preferredDateColumn, array $filters): array
    {
        $dateColumn = $this->dateColumn($table, $preferredDateColumn);

        if ($dateColumn === null) {
            return [];
        }

        $query = DB::table($table)
            ->selectRaw("substr({$dateColumn}, 1, 7) as report_month")
            ->selectRaw('SUM(amount) as total_amount')
            ->whereNotNull($dateColumn)
            ->groupBy('report_month');

        $this->applyFilters($query, $table, $filters, $preferredDateColumn);

        return $query
            ->pluck('total_amount', 'report_month')
            ->map(fn ($amount): float => round((float) $amount, 2))
            ->all();
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyFilters(Builder $query, string $table, array $filters, string $preferredDateColumn): void
    {
        if (! empty($filters['branch_id']) && Schema::hasColumn($table, 'branch_id')) {
            $query->where('branch_id', $filters['branch_id']);
        }

        $dateColumn = $this->dateColumn($table, $preferredDateColumn);

        if ($dateColumn !== null && ! empty($filters['from_date'])) {
            $query->whereDate($dateColumn, '>=', $filters['from_date']);
        }

        if ($dateColumn !== null && ! empty($filters['to_date'])) {
            $query->whereDate($dateColumn, '<=', $filters['to_date']);
        }
    }

    private function dateColumn(string $table, string $preferredDateColumn): ?string
    {
        if (Schema::hasColumn($table, $preferredDateColumn)) {
            return $preferredDateColumn;
        }

        if (Schema::hasColumn($table, 'date')) {
            return 'date';
        }

        if (Schema::hasColumn($table, 'created_at')) {
            return 'created_at';
        }

        return null;
    }
}
