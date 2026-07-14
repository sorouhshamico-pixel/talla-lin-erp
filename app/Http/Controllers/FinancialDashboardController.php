<?php

namespace App\Http\Controllers;

use App\Services\ReportSavedViewService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FinancialDashboardController extends Controller
{
    private const REPORT_KEY = 'financial-dashboard';

    public function __invoke(ReportSavedViewService $savedViews): View
    {
        return view('reports.financial-dashboard', [
            ...$this->dashboardData(),
            'savedViews' => $this->savedViewsForCurrentUser($savedViews),
        ]);
    }

    public function json(): JsonResponse
    {
        return response()->json($this->dashboardData());
    }

    public function storeSavedView(Request $request, ReportSavedViewService $savedViews): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $savedViews->save(
            $request->user(),
            self::REPORT_KEY,
            $validated['name'],
            [],
            (bool) ($validated['is_default'] ?? false)
        );

        return redirect()
            ->route('reports.financial-dashboard')
            ->with('status', 'تم حفظ عرض الداشبورد المالية بنجاح.');
    }

    /**
     * @return array<string, float|string>
     */
    private function dashboardData(): array
    {
        $fromDate = now()->startOfMonth()->toDateString();
        $toDate = now()->endOfMonth()->toDateString();

        $currentMonthRevenues = $this->sumAmountWithinDateRange('revenues', 'revenue_date', $fromDate, $toDate);
        $currentMonthExpenses = $this->sumAmountWithinDateRange('expenses', 'expense_date', $fromDate, $toDate);
        $currentMonthNetProfit = round($currentMonthRevenues - $currentMonthExpenses, 2);

        return [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'currentMonthRevenues' => $currentMonthRevenues,
            'currentMonthExpenses' => $currentMonthExpenses,
            'currentMonthNetProfit' => $currentMonthNetProfit,
            'uncollectedRevenues' => $this->sumAmountByBooleanColumn('revenues', 'is_collected', false),
            'unpaidExpenses' => $this->sumAmountByBooleanColumn('expenses', 'is_paid', false),
        ];
    }

    private function savedViewsForCurrentUser(ReportSavedViewService $savedViews): Collection
    {
        $user = auth()->user();

        if ($user === null || ! Schema::hasTable('report_saved_views')) {
            return collect();
        }

        return $savedViews->list($user, self::REPORT_KEY);
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
