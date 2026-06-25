<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\InventoryBalance;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'branch_id' => $request->input('branch_id'),
        ];

        $branches = Branch::query()
            ->where('is_active', true)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get();

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
        ];

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

        $profit = [
            'gross_profit_before_tax' => round($sales['subtotal'] - $purchases['subtotal'], 2),
            'net_profit_after_expenses' => round($sales['subtotal'] - $purchases['subtotal'] - $expenses['amount'], 2),
            'net_cash_flow' => round($sales['paid_amount'] - $purchases['paid_amount'], 2),
            'net_cash_flow_after_expenses' => round($sales['paid_amount'] - $purchases['paid_amount'] - $expenses['paid_amount'], 2),
            'inventory_potential_margin' => round($inventory['sale_value'] - $inventory['cost_value'], 2),
        ];

        return view('reports.index', [
            'sales' => $sales,
            'purchases' => $purchases,
            'expenses' => $expenses,
            'inventory' => $inventory,
            'profit' => $profit,
            'branches' => $branches,
            'filters' => $filters,
        ]);
    }
}
