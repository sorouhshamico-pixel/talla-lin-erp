<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\InventoryBalance;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'branch_id' => $request->input('branch_id'),
            'expense_category_id' => $request->input('expense_category_id'),
            'payment_method' => $request->input('payment_method'),
        ];

        $branches = Branch::query()
            ->where('is_active', true)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get();

        $expenseCategories = ExpenseCategory::query()
            ->orderBy('name')
            ->get();

        $paymentMethods = [
            'cash' => 'نقدًا',
            'card' => 'بطاقة',
            'bank_transfer' => 'تحويل بنكي',
            'online' => 'دفع إلكتروني',
            'other' => 'أخرى',
        ];

        $salesBaseQuery = SalesInvoice::query()
            ->whereIn('status', ['issued', 'paid']);

        $purchaseBaseQuery = PurchaseInvoice::query()
            ->where('status', 'received');

        $expenseBaseQuery = Expense::query();

        $inventoryBaseQuery = InventoryBalance::query()
            ->join('product_variants', 'inventory_balances.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'inventory_balances.product_id', '=', 'products.id');

        if (! empty($filters['branch_id'])) {
            $salesBaseQuery->where('branch_id', $filters['branch_id']);
            $purchaseBaseQuery->where('branch_id', $filters['branch_id']);
            $expenseBaseQuery->where('branch_id', $filters['branch_id']);
            $inventoryBaseQuery->where('inventory_balances.branch_id', $filters['branch_id']);
        }

        if (! empty($filters['from_date'])) {
            $salesBaseQuery->whereDate('issued_at', '>=', $filters['from_date']);
            $purchaseBaseQuery->whereDate('invoice_date', '>=', $filters['from_date']);
            $expenseBaseQuery->whereDate('expense_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $salesBaseQuery->whereDate('issued_at', '<=', $filters['to_date']);
            $purchaseBaseQuery->whereDate('invoice_date', '<=', $filters['to_date']);
            $expenseBaseQuery->whereDate('expense_date', '<=', $filters['to_date']);
        }

        if (! empty($filters['expense_category_id'])) {
            $expenseBaseQuery->where('expense_category_id', $filters['expense_category_id']);
        }

        if (! empty($filters['payment_method'])) {
            $expenseBaseQuery->where('payment_method', $filters['payment_method']);
        }

        $sales = [
            'count' => (clone $salesBaseQuery)->count(),
            'subtotal' => round((float) (clone $salesBaseQuery)->sum('subtotal'), 2),
            'discount_total' => round((float) (clone $salesBaseQuery)->sum('discount_total'), 2),
            'tax_total' => round((float) (clone $salesBaseQuery)->sum('tax_total'), 2),
            'grand_total' => round((float) (clone $salesBaseQuery)->sum('grand_total'), 2),
            'paid_amount' => round((float) (clone $salesBaseQuery)->sum('paid_amount'), 2),
            'remaining_amount' => round((float) (clone $salesBaseQuery)->sum('remaining_amount'), 2),
        ];

        $purchases = [
            'count' => (clone $purchaseBaseQuery)->count(),
            'subtotal' => round((float) (clone $purchaseBaseQuery)->sum('subtotal'), 2),
            'discount_total' => round((float) (clone $purchaseBaseQuery)->sum('discount_total'), 2),
            'tax_total' => round((float) (clone $purchaseBaseQuery)->sum('tax_total'), 2),
            'grand_total' => round((float) (clone $purchaseBaseQuery)->sum('grand_total'), 2),
            'paid_amount' => round((float) (clone $purchaseBaseQuery)->sum('paid_amount'), 2),
            'remaining_amount' => round((float) (clone $purchaseBaseQuery)->sum('remaining_amount'), 2),
        ];

        $expenses = [
            'count' => (clone $expenseBaseQuery)->count(),
            'amount' => round((float) (clone $expenseBaseQuery)->sum('amount'), 2),
            'tax_amount' => round((float) (clone $expenseBaseQuery)->sum('tax_amount'), 2),
            'paid_amount' => round((float) (clone $expenseBaseQuery)->where('is_paid', true)->sum('amount'), 2),
            'unpaid_amount' => round((float) (clone $expenseBaseQuery)->where('is_paid', false)->sum('amount'), 2),
        ];

        $expenseCategoryBreakdown = $this->expenseCategoryBreakdown(clone $expenseBaseQuery);
        $expensePaymentBreakdown = $this->expensePaymentBreakdown(clone $expenseBaseQuery, $paymentMethods);

        $inventory = [
            'products_count' => (clone $inventoryBaseQuery)->distinct('inventory_balances.product_id')->count('inventory_balances.product_id'),
            'variants_count' => (clone $inventoryBaseQuery)->distinct('inventory_balances.product_variant_id')->count('inventory_balances.product_variant_id'),
            'quantity_on_hand' => round((float) (clone $inventoryBaseQuery)->sum('inventory_balances.quantity_on_hand'), 3),
            'quantity_reserved' => round((float) (clone $inventoryBaseQuery)->sum('inventory_balances.quantity_reserved'), 3),
            'available_quantity' => round((float) (clone $inventoryBaseQuery)->selectRaw('SUM(inventory_balances.quantity_on_hand - inventory_balances.quantity_reserved) as total')->value('total'), 3),
            'cost_value' => round((float) (clone $inventoryBaseQuery)->selectRaw('SUM(inventory_balances.quantity_on_hand * product_variants.cost_price) as total')->value('total'), 2),
            'sale_value' => round((float) (clone $inventoryBaseQuery)->selectRaw('SUM(inventory_balances.quantity_on_hand * product_variants.sale_price) as total')->value('total'), 2),
            'low_stock_count' => (clone $inventoryBaseQuery)
                ->whereRaw('(inventory_balances.quantity_on_hand - inventory_balances.quantity_reserved) <= inventory_balances.reorder_level')
                ->count(),
        ];

        $grossProfitBeforeTax = round($sales['subtotal'] - $purchases['subtotal'], 2);
        $netProfitAfterExpenses = round($grossProfitBeforeTax - $expenses['amount'], 2);

        $profit = [
            'gross_profit_before_tax' => $grossProfitBeforeTax,
            'operating_expenses_total' => $expenses['amount'],
            'net_profit_after_expenses' => $netProfitAfterExpenses,
            'net_cash_flow' => round($sales['paid_amount'] - $purchases['paid_amount'], 2),
            'net_cash_flow_after_expenses' => round($sales['paid_amount'] - $purchases['paid_amount'] - $expenses['paid_amount'], 2),
            'inventory_potential_margin' => round($inventory['sale_value'] - $inventory['cost_value'], 2),
        ];

        return view('reports.index', [
            'sales' => $sales,
            'purchases' => $purchases,
            'expenses' => $expenses,
            'expenseCategoryBreakdown' => $expenseCategoryBreakdown,
            'expensePaymentBreakdown' => $expensePaymentBreakdown,
            'inventory' => $inventory,
            'profit' => $profit,
            'branches' => $branches,
            'expenseCategories' => $expenseCategories,
            'paymentMethods' => $paymentMethods,
            'filters' => $filters,
        ]);
    }

    private function expenseCategoryBreakdown(Builder $query): Collection
    {
        return $query
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->selectRaw('expense_categories.id, expense_categories.name, expense_categories.slug, COUNT(expenses.id) as expenses_count, SUM(expenses.amount) as total_amount, SUM(expenses.tax_amount) as total_tax_amount')
            ->groupBy('expense_categories.id', 'expense_categories.name', 'expense_categories.slug')
            ->orderByDesc('total_amount')
            ->get()
            ->map(function ($row): array {
                return [
                    'id' => (int) $row->id,
                    'name' => $row->name,
                    'slug' => $row->slug,
                    'expenses_count' => (int) $row->expenses_count,
                    'total_amount' => round((float) $row->total_amount, 2),
                    'total_tax_amount' => round((float) $row->total_tax_amount, 2),
                ];
            });
    }

    private function expensePaymentBreakdown(Builder $query, array $paymentMethods): Collection
    {
        return $query
            ->selectRaw('payment_method, COUNT(expenses.id) as expenses_count, SUM(amount) as total_amount')
            ->groupBy('payment_method')
            ->orderByDesc('total_amount')
            ->get()
            ->map(function ($row) use ($paymentMethods): array {
                return [
                    'payment_method' => $row->payment_method,
                    'label' => $paymentMethods[$row->payment_method] ?? $row->payment_method,
                    'expenses_count' => (int) $row->expenses_count,
                    'total_amount' => round((float) $row->total_amount, 2),
                ];
            });
    }
}
