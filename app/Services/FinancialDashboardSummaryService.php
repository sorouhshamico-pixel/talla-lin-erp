<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FinancialDashboardSummaryService
{
    public function __construct(
        private readonly CustomerSalesInvoiceAgingReportBuilder $customerAgingBuilder,
        private readonly SupplierPurchaseInvoiceAgingReportBuilder $supplierAgingBuilder
    ) {
    }

    public function summary(?Request $request = null): array
    {
        $request ??= request();

        $customerQuery = SalesInvoice::query()
            ->where('remaining_amount', '>', 0);

        $supplierQuery = PurchaseInvoice::query()
            ->where('remaining_amount', '>', 0);

        $this->applyBranchFilter($customerQuery, $request);
        $this->applyBranchFilter($supplierQuery, $request);

        $reportDate = $this->reportDate($request);

        $expectedInflows = round((float) (clone $customerQuery)->sum('remaining_amount'), 2);
        $expectedOutflows = round((float) (clone $supplierQuery)->sum('remaining_amount'), 2);

        $overdueInflows = round((float) (clone $customerQuery)
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<', $reportDate->toDateString())
            ->sum('remaining_amount'), 2);

        $overdueOutflows = round((float) (clone $supplierQuery)
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<', $reportDate->toDateString())
            ->sum('remaining_amount'), 2);

        $netExpectedCash = round($expectedInflows - $expectedOutflows, 2);
        $netOverduePressure = round($overdueOutflows - $overdueInflows, 2);

        $cashCoverageRatio = $expectedOutflows > 0
            ? round(($expectedInflows / $expectedOutflows) * 100, 2)
            : null;

        return [
            'customers_count' => (clone $customerQuery)->whereNotNull('customer_id')->distinct()->count('customer_id'),
            'customer_open_invoice_count' => (clone $customerQuery)->count(),
            'expected_inflows' => $expectedInflows,
            'overdue_inflows' => $overdueInflows,

            'suppliers_count' => (clone $supplierQuery)->whereNotNull('supplier_id')->distinct()->count('supplier_id'),
            'supplier_open_invoice_count' => (clone $supplierQuery)->count(),
            'expected_outflows' => $expectedOutflows,
            'overdue_outflows' => $overdueOutflows,

            'net_expected_cash' => $netExpectedCash,
            'position_label' => $netExpectedCash >= 0
                ? 'صافي تدفق نقدي متوقع لصالح الشركة'
                : 'صافي التزامات نقدية متوقعة على الشركة',

            'net_overdue_pressure' => $netOverduePressure,
            'cash_coverage_ratio' => $cashCoverageRatio,
            'cash_coverage_label' => $cashCoverageRatio === null
                ? 'لا توجد التزامات موردين مفتوحة'
                : ($cashCoverageRatio >= 100 ? 'تغطية نقدية متوقعة كافية' : 'تغطية نقدية متوقعة غير كافية'),
            'risk_label' => $this->riskLabel($netOverduePressure, $cashCoverageRatio, $expectedOutflows),
        ];
    }

    public function topOverdueCustomers(?Request $request = null, int $limit = 5): array
    {
        $request ??= request();

        $reportDate = $this->reportDate($request);

        $query = SalesInvoice::query()
            ->where('remaining_amount', '>', 0)
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<', $reportDate->toDateString());

        $this->applyBranchFilter($query, $request);

        $invoices = $query->get([
            'id',
            'customer_id',
            'invoice_number',
            'remaining_amount',
            'due_at',
        ]);

        if ($invoices->isEmpty()) {
            return [];
        }

        $customerNames = Customer::query()
            ->whereIn('id', $invoices->pluck('customer_id')->filter()->unique())
            ->pluck('name', 'id');

        return $invoices
            ->groupBy(fn ($invoice) => $invoice->customer_id ?: 'without_customer')
            ->map(function ($group, $customerId) use ($customerNames, $reportDate): array {
                $oldestDueAt = $group
                    ->pluck('due_at')
                    ->filter()
                    ->map(fn ($date) => Carbon::parse($date)->startOfDay())
                    ->sort()
                    ->first();

                $normalizedCustomerId = $customerId === 'without_customer' ? null : (int) $customerId;

                return [
                    'customer_id' => $normalizedCustomerId,
                    'customer_name' => $normalizedCustomerId
                        ? ($customerNames[$normalizedCustomerId] ?? 'عميل غير معروف')
                        : 'بدون عميل محدد',
                    'invoice_count' => $group->count(),
                    'overdue_total' => round((float) $group->sum('remaining_amount'), 2),
                    'oldest_due_at' => $oldestDueAt?->format('Y-m-d'),
                    'max_days_overdue' => $oldestDueAt ? (int) $oldestDueAt->diffInDays($reportDate) : null,
                ];
            })
            ->sortByDesc('overdue_total')
            ->values()
            ->take($limit)
            ->all();
    }

    public function topOverdueSuppliers(?Request $request = null, int $limit = 5): array
    {
        $request ??= request();

        $reportDate = $this->reportDate($request);

        $query = PurchaseInvoice::query()
            ->where('remaining_amount', '>', 0)
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<', $reportDate->toDateString());

        $this->applyBranchFilter($query, $request);

        $invoices = $query->get([
            'id',
            'supplier_id',
            'invoice_number',
            'remaining_amount',
            'due_at',
        ]);

        if ($invoices->isEmpty()) {
            return [];
        }

        $supplierNames = Supplier::query()
            ->whereIn('id', $invoices->pluck('supplier_id')->filter()->unique())
            ->pluck('name', 'id');

        return $invoices
            ->groupBy(fn ($invoice) => $invoice->supplier_id ?: 'without_supplier')
            ->map(function ($group, $supplierId) use ($supplierNames, $reportDate): array {
                $oldestDueAt = $group
                    ->pluck('due_at')
                    ->filter()
                    ->map(fn ($date) => Carbon::parse($date)->startOfDay())
                    ->sort()
                    ->first();

                $normalizedSupplierId = $supplierId === 'without_supplier' ? null : (int) $supplierId;

                return [
                    'supplier_id' => $normalizedSupplierId,
                    'supplier_name' => $normalizedSupplierId
                        ? ($supplierNames[$normalizedSupplierId] ?? 'مورد غير معروف')
                        : 'بدون مورد محدد',
                    'invoice_count' => $group->count(),
                    'overdue_total' => round((float) $group->sum('remaining_amount'), 2),
                    'oldest_due_at' => $oldestDueAt?->format('Y-m-d'),
                    'max_days_overdue' => $oldestDueAt ? (int) $oldestDueAt->diffInDays($reportDate) : null,
                ];
            })
            ->sortByDesc('overdue_total')
            ->values()
            ->take($limit)
            ->all();
    }

    private function reportDate(Request $request): Carbon
    {
        $asOfDate = $request->input('as_of_date');

        if ($asOfDate) {
            try {
                return Carbon::parse($asOfDate)->startOfDay();
            } catch (\Throwable) {
                return now()->startOfDay();
            }
        }

        return now()->startOfDay();
    }
    private function applyBranchFilter($query, Request $request): void
    {
        $branchId = $request->integer('branch_id') ?: null;

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
    }

    private function riskLabel(float $netOverduePressure, ?float $cashCoverageRatio, float $expectedOutflows): string
    {
        if ($expectedOutflows <= 0) {
            return 'لا توجد التزامات موردين مفتوحة';
        }

        if ($netOverduePressure > 0 && $cashCoverageRatio !== null && $cashCoverageRatio < 100) {
            return 'يتطلب متابعة نقدية';
        }

        if ($netOverduePressure > 0) {
            return 'يوجد ضغط متأخر';
        }

        if ($cashCoverageRatio !== null && $cashCoverageRatio < 100) {
            return 'تغطية نقدية غير كافية';
        }

        return 'الوضع المالي المتوقع مستقر';
    }
}
