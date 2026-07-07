<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupplierPurchaseInvoiceAgingDrilldownController extends Controller
{
    public function index(Request $request): View
    {
        return view('reports.supplier-purchase-invoice-aging-drilldown', $this->drilldownData($request));
    }

    public function export(Request $request)
    {
        $data = $this->drilldownData($request);

        $fileName = 'supplier-purchase-invoice-aging-drilldown-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, chr(239) . chr(187) . chr(191));

            fputcsv($handle, ['تفاصيل فواتير الموردين المفتوحة']);
            fputcsv($handle, ['تاريخ إنشاء التقرير', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, ['تاريخ التقرير', $data['reportDate']->format('Y-m-d')]);
            fputcsv($handle, ['فلتر المورد', $data['selectedSupplierLabel']]);
            fputcsv($handle, ['فلتر الفرع', $data['selectedBranchLabel']]);
            fputcsv($handle, ['فلتر شريحة العمر', $data['selectedAgingBucketLabel']]);
            fputcsv($handle, []);

            fputcsv($handle, ['ملخص']);
            fputcsv($handle, ['عدد الفواتير المفتوحة', $data['summary']['invoice_count']]);
            fputcsv($handle, ['إجمالي الفواتير', number_format((float) $data['summary']['grand_total'], 2, '.', '')]);
            fputcsv($handle, ['إجمالي المدفوع', number_format((float) $data['summary']['paid_total'], 2, '.', '')]);
            fputcsv($handle, ['إجمالي المتبقي', number_format((float) $data['summary']['remaining_total'], 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'رقم الفاتورة',
                'المورد',
                'تاريخ الإصدار',
                'تاريخ الاستحقاق',
                'الإجمالي',
                'المدفوع',
                'المتبقي',
                'حالة الدفع',
            ]);

            foreach ($data['invoices'] as $invoice) {
                fputcsv($handle, [
                    $invoice->invoice_number,
                    $data['supplierNames'][$invoice->supplier_id] ?? '',
                    $invoice->issued_at ? Carbon::parse($invoice->issued_at)->format('Y-m-d') : '',
                    $invoice->due_at ? Carbon::parse($invoice->due_at)->format('Y-m-d') : '',
                    number_format((float) $invoice->grand_total, 2, '.', ''),
                    number_format((float) $invoice->paid_amount, 2, '.', ''),
                    number_format((float) $invoice->remaining_amount, 2, '.', ''),
                    $invoice->payment_status,
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function drilldownData(Request $request): array
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

        $supplierId = $request->integer('supplier_id') ?: null;
        $branchId = $request->integer('branch_id') ?: null;
        $agingBucket = $request->input('aging_bucket');

        $query = PurchaseInvoice::query()
            ->where('remaining_amount', '>', 0);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $this->applyAgingBucket($query, $agingBucket, $reportDate);

        $invoices = $query
            ->orderByRaw('due_at IS NULL')
            ->orderBy('due_at')
            ->orderBy('invoice_number')
            ->get();

        $supplierNames = Supplier::query()
            ->whereIn('id', $invoices->pluck('supplier_id')->filter()->unique())
            ->pluck('name', 'id');

        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $branches = DB::table('branches')
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedSupplierName = $supplierId
            ? Supplier::query()->whereKey($supplierId)->value('name')
            : null;

        return [
            'reportDate' => $reportDate,
            'suppliers' => $suppliers,
            'branches' => $branches,
            'agingBuckets' => $agingBuckets,
            'selectedSupplierId' => $supplierId,
            'selectedBranchId' => $branchId,
            'selectedAgingBucket' => $agingBucket,
            'selectedSupplierLabel' => $supplierId ? $selectedSupplierName . ' #' . $supplierId : 'كل الموردين',
            'selectedBranchLabel' => $this->branchLabel($request),
            'selectedAgingBucketLabel' => $agingBuckets[$agingBucket] ?? 'كل الشرائح',
            'invoices' => $invoices,
            'supplierNames' => $supplierNames,
            'summary' => [
                'invoice_count' => $invoices->count(),
                'remaining_total' => round((float) $invoices->sum('remaining_amount'), 2),
                'grand_total' => round((float) $invoices->sum('grand_total'), 2),
                'paid_total' => round((float) $invoices->sum('paid_amount'), 2),
            ],
        ];
    }

    private function branchLabel(Request $request): string
    {
        $branchId = $request->integer('branch_id') ?: null;

        if (! $branchId) {
            return 'كل الفروع';
        }

        $name = DB::table('branches')->where('id', $branchId)->value('name');

        return $name ? $name . ' #' . $branchId : 'فرع غير معروف #' . $branchId;
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
