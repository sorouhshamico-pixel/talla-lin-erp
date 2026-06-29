<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FinancialDashboardController extends Controller
{
    public function __invoke(): View
    {
        $fromDate = now()->startOfMonth()->toDateString();
        $toDate = now()->endOfMonth()->toDateString();

        $currentMonthRevenues = $this->sumAmountWithinDateRange('revenues', 'revenue_date', $fromDate, $toDate);
        $currentMonthExpenses = $this->sumAmountWithinDateRange('expenses', 'expense_date', $fromDate, $toDate);
        $currentMonthNetProfit = round($currentMonthRevenues - $currentMonthExpenses, 2);

        $uncollectedRevenues = $this->sumAmountByBooleanColumn('revenues', 'is_collected', false);
        $unpaidExpenses = $this->sumAmountByBooleanColumn('expenses', 'is_paid', false);

        return view('reports.financial-dashboard', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'currentMonthRevenues' => $currentMonthRevenues,
            'currentMonthExpenses' => $currentMonthExpenses,
            'currentMonthNetProfit' => $currentMonthNetProfit,
            'uncollectedRevenues' => $uncollectedRevenues,
            'unpaidExpenses' => $unpaidExpenses,
        ]);
    }

    private function sumAmountWithinDateRange(string $table, string $preferredDateColumn, string $fromDate, string $toDate): float
    {
        $query = DB::table($table);

        $this->applyDateRange($query, $table, $preferredDateColumn, $fromDate, $toDate);

        return round((float) $query->sum('amount'), 2);
    }

    private function sumAmountByBooleanColumn(string $table, string $column, bool $value): float
    {
        if (! Schema::hasColumn($table, $column)) {
            return 0.0;
        }

        return round((float) DB::table($table)
            ->where($column, $value)
            ->sum('amount'), 2);
    }

    private function applyDateRange(Builder $query, string $table, string $preferredDateColumn, string $fromDate, string $toDate): void
    {
        $dateColumn = $this->dateColumn($table, $preferredDateColumn);

        if ($dateColumn === null) {
            return;
        }

        $query
            ->whereDate($dateColumn, '>=', $fromDate)
            ->whereDate($dateColumn, '<=', $toDate);
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
