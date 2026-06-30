<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PartyStatementService
{
    public function customerStatement(int $customerId, ?string $from = null, ?string $to = null): array
    {
        return $this->buildStatement(
            table: 'revenues',
            foreignKey: 'customer_id',
            id: $customerId,
            typeLabel: 'إيراد',
            amountDirection: 'credit',
            from: $from,
            to: $to
        );
    }

    public function supplierStatement(int $supplierId, ?string $from = null, ?string $to = null): array
    {
        return $this->buildStatement(
            table: 'expenses',
            foreignKey: 'supplier_id',
            id: $supplierId,
            typeLabel: 'مصروف',
            amountDirection: 'debit',
            from: $from,
            to: $to
        );
    }

    private function buildStatement(
        string $table,
        string $foreignKey,
        int $id,
        string $typeLabel,
        string $amountDirection,
        ?string $from,
        ?string $to
    ): array {
        $empty = [
            'rows' => collect(),
            'total_debit' => 0.0,
            'total_credit' => 0.0,
            'balance' => 0.0,
            'count' => 0,
            'has_data_source' => false,
            'source_table' => null,
        ];

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

        $dateColumn = $this->firstExistingColumn($columns, [
            'date',
            'revenue_date',
            'expense_date',
            'paid_at',
            'collected_at',
            'created_at',
        ]);

        $descriptionColumn = $this->firstExistingColumn($columns, [
            'description',
            'notes',
            'note',
            'title',
            'name',
            'reference',
        ]);

        $statusColumn = $this->firstExistingColumn($columns, [
            'payment_status',
            'status',
            'paid_status',
            'collection_status',
        ]);

        $query = DB::table($table)->where($foreignKey, $id);

        if ($dateColumn && $from) {
            $query->whereDate($dateColumn, '>=', $from);
        }

        if ($dateColumn && $to) {
            $query->whereDate($dateColumn, '<=', $to);
        }

        if ($dateColumn) {
            $query->orderBy($dateColumn);
        }

        $query->orderBy('id');

        $records = $query->get();

        $rows = $records->map(function ($record) use ($typeLabel, $amountDirection, $amountColumn, $dateColumn, $descriptionColumn, $statusColumn) {
            $amount = (float) ($record->{$amountColumn} ?? 0);

            $debit = $amountDirection === 'debit' ? $amount : 0.0;
            $credit = $amountDirection === 'credit' ? $amount : 0.0;

            return [
                'date' => $dateColumn ? (string) ($record->{$dateColumn} ?? '-') : '-',
                'type' => $typeLabel,
                'description' => $descriptionColumn ? (string) ($record->{$descriptionColumn} ?? '-') : '-',
                'status' => $statusColumn ? (string) ($record->{$statusColumn} ?? '-') : '-',
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $credit - $debit,
            ];
        });

        $totalDebit = (float) $rows->sum('debit');
        $totalCredit = (float) $rows->sum('credit');

        return [
            'rows' => $rows,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'balance' => $totalCredit - $totalDebit,
            'count' => $rows->count(),
            'has_data_source' => true,
            'source_table' => $table,
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
