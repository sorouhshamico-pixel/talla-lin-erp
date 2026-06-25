<?php

namespace App\Http\Controllers;

use App\Models\Branch;
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

        if (! empty($filters['branch_id'])) {
            $salesBaseQuery->where('branch_id', $filters['branch_id']);
            $purchaseBaseQuery->where('branch_id', $filters['branch_id']);
        }

        if (! empty($filters['from_date'])) {
            $salesBaseQuery->whereDate('issued_at', '>=', $filters['from_date']);
            $purchaseBaseQuery->whereDate('invoice_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $salesBaseQuery->whereDate('issued_at', '<=', $filters['to_date']);
            $purchaseBaseQuery->whereDate('invoice_date', '<=', $filters['to_date']);
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

        $profit = [
            'gross_profit_before_tax' => round($sales['subtotal'] - $purchases['subtotal'], 2),
            'net_cash_flow' => round($sales['paid_amount'] - $purchases['paid_amount'], 2),
        ];

        return view('reports.index', [
            'sales' => $sales,
            'purchases' => $purchases,
            'profit' => $profit,
            'branches' => $branches,
            'filters' => $filters,
        ]);
    }
}
