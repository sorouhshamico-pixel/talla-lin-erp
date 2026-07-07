<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CustomerSalesInvoiceAgingDrilldownController extends Controller
{
    public function index(Request $request): View
    {
        $reportDate = now()->startOfDay();

        $agingBuckets = [
            'not_due' => 'غير مستحقة بعد',
            'overdue_1_30' => 'متأخرة 1 إلى 30 يوم',
            'overdue_31_60' => 'متأخرة 31 إلى 60 يوم',
            'overdue_61_90' => 'متأخرة 61 إلى 90 يوم',
            'overdue_more_than_90' => 'أكثر من 90 يوم',
            'without_due_date' => 'بدون تاريخ استحقاق',
        ];

        $customerId = $request->integer('customer_id') ?: null;
        $agingBucket = $request->input('aging_bucket');

        $query = SalesInvoice::query()
            ->where('remaining_amount', '>', 0);

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $this->applyAgingBucket($query, $agingBucket, $reportDate);

        $invoices = $query
            ->orderByRaw('due_at IS NULL')
            ->orderBy('due_at')
            ->orderBy('invoice_number')
            ->get();

        $customerNames = Customer::query()
            ->whereIn('id', $invoices->pluck('customer_id')->filter()->unique())
            ->pluck('name', 'id');

        $customers = Customer::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedCustomerLabel = $customerId
            ? Customer::query()->whereKey($customerId)->value('name') . ' #' . $customerId
            : 'كل العملاء';

        return view('reports.customer-sales-invoice-aging-drilldown', [
            'reportDate' => $reportDate,
            'customers' => $customers,
            'agingBuckets' => $agingBuckets,
            'selectedCustomerId' => $customerId,
            'selectedAgingBucket' => $agingBucket,
            'selectedCustomerLabel' => $selectedCustomerLabel,
            'selectedAgingBucketLabel' => $agingBuckets[$agingBucket] ?? 'كل الشرائح',
            'invoices' => $invoices,
            'customerNames' => $customerNames,
            'summary' => [
                'invoice_count' => $invoices->count(),
                'remaining_total' => round((float) $invoices->sum('remaining_amount'), 2),
                'grand_total' => round((float) $invoices->sum('grand_total'), 2),
                'paid_total' => round((float) $invoices->sum('paid_amount'), 2),
            ],
        ]);
    }

    private function applyAgingBucket($query, ?string $agingBucket, Carbon $reportDate): void
    {
        if (! $agingBucket) {
            return;
        }

        match ($agingBucket) {
            'not_due' => $query
                ->whereNotNull('due_at')
                ->whereDate('due_at', '>=', $reportDate->toDateString()),

            'overdue_1_30' => $query
                ->whereNotNull('due_at')
                ->whereDate('due_at', '<', $reportDate->toDateString())
                ->whereDate('due_at', '>=', $reportDate->copy()->subDays(30)->toDateString()),

            'overdue_31_60' => $query
                ->whereNotNull('due_at')
                ->whereDate('due_at', '<', $reportDate->copy()->subDays(30)->toDateString())
                ->whereDate('due_at', '>=', $reportDate->copy()->subDays(60)->toDateString()),

            'overdue_61_90' => $query
                ->whereNotNull('due_at')
                ->whereDate('due_at', '<', $reportDate->copy()->subDays(60)->toDateString())
                ->whereDate('due_at', '>=', $reportDate->copy()->subDays(90)->toDateString()),

            'overdue_more_than_90' => $query
                ->whereNotNull('due_at')
                ->whereDate('due_at', '<', $reportDate->copy()->subDays(90)->toDateString()),

            'without_due_date' => $query->whereNull('due_at'),

            default => null,
        };
    }
}
