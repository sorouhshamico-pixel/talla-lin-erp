<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ProfitLossReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = [
            'from_date' => $request->query('from_date'),
            'to_date' => $request->query('to_date'),
            'branch_id' => $request->query('branch_id'),
        ];

        $branches = Branch::query()
            ->where('is_active', true)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get();

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

        return view('reports.profit-loss', [
            'filters' => $filters,
            'branches' => $branches,
            'totalRevenues' => $totalRevenues,
            'totalRevenueTax' => $totalRevenueTax,
            'totalExpenses' => $totalExpenses,
            'totalExpenseTax' => $totalExpenseTax,
            'netProfit' => $netProfit,
            'taxDifference' => $taxDifference,
        ]);
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
