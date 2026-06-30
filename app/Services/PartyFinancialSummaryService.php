<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PartyFinancialSummaryService
{
    public function customerSummary(int $customerId): array
    {
        return $this->buildSummary(
            table: 'revenues',
            foreignKey: 'customer_id',
            id: $customerId,
            totalLabel: 'إجمالي الإيرادات',
            paidLabel: 'إجمالي المحصل',
            pendingLabel: 'غير محصل'
        );
    }

    public function supplierSummary(int $supplierId): array
    {
        return $this->buildSummary(
            table: 'expenses',
            foreignKey: 'supplier_id',
            id: $supplierId,
            totalLabel: 'إجمالي المصروفات',
            paidLabel: 'إجمالي المدفوع',
            pendingLabel: 'غير مدفوع'
        );
    }

    private function buildSummary(
        string $table,
        string $foreignKey,
        int $id,
        string $totalLabel,
        string $paidLabel,
        string $pendingLabel
    ): array {
        $empty = $this->emptySummary($totalLabel, $paidLabel, $pendingLabel);

        if (! Schema::hasTable($table)) {
            return $empty;
        }

        $columns = Schema::getColumnListing($table);

        if (! in_array($foreignKey, $columns, true)) {
            return $empty;
        }

        $amountColumn = $this->firstExistingColumn($columns, [
            'amount',
            'total_amount',
            'value',
            'total',
            'price',
            'cost',
        ]);

        if (! $amountColumn) {
            return $empty;
        }

        $paidAmountColumn = $this->firstExistingColumn($columns, [
            'paid_amount',
            'collected_amount',
            'received_amount',
            'amount_paid',
        ]);

        $statusColumn = $this->firstExistingColumn($columns, [
            'payment_status',
            'status',
            'paid_status',
            'collection_status',
        ]);

        $baseQuery = DB::table($table)->where($foreignKey, $id);

        $count = (clone $baseQuery)->count();
        $total = (float) (clone $baseQuery)->sum($amountColumn);

        $paid = 0.0;
        $pending = 0.0;

        if ($paidAmountColumn) {
            $paid = (float) (clone $baseQuery)->sum($paidAmountColumn);
            $pending = max($total - $paid, 0);
        } elseif ($statusColumn) {
            $paidStatuses = [
                'paid',
                'collected',
                'received',
                'completed',
                'مدفوع',
                'محصل',
                'مكتمل',
            ];

            $paid = (float) (clone $baseQuery)
                ->whereIn($statusColumn, $paidStatuses)
                ->sum($amountColumn);

            $pending = max($total - $paid, 0);
        } else {
            $pending = $total;
        }

        return [
            'count' => $count,
            'total' => $total,
            'paid' => $paid,
            'pending' => $pending,
            'total_label' => $totalLabel,
            'paid_label' => $paidLabel,
            'pending_label' => $pendingLabel,
            'source_table' => $table,
            'amount_column' => $amountColumn,
            'has_data_source' => true,
        ];
    }

    private function emptySummary(string $totalLabel, string $paidLabel, string $pendingLabel): array
    {
        return [
            'count' => 0,
            'total' => 0.0,
            'paid' => 0.0,
            'pending' => 0.0,
            'total_label' => $totalLabel,
            'paid_label' => $paidLabel,
            'pending_label' => $pendingLabel,
            'source_table' => null,
            'amount_column' => null,
            'has_data_source' => false,
        ];
    }

    private function firstExistingColumn(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
