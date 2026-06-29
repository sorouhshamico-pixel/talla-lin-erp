<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfitLossReportController extends Controller
{
    public function __invoke(Request $request): View
    {
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
     * @return array<string, float>
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
        ];
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
