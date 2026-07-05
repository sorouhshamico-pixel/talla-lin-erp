<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PartyStatementService
{
    public function customerStatement(int $customerId, ?string $from = null, ?string $to = null): array
    {
        if (Schema::hasTable('sales_invoices') && Schema::hasTable('sales_invoice_payments')) {
            return $this->buildCustomerSalesInvoiceStatement($customerId, $from, $to);
        }

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


    private function buildCustomerSalesInvoiceStatement(int $customerId, ?string $from, ?string $to): array
    {
        $invoiceColumns = Schema::getColumnListing('sales_invoices');
        $paymentColumns = Schema::getColumnListing('sales_invoice_payments');

        foreach (['id', 'customer_id', 'invoice_number', 'grand_total'] as $requiredColumn) {
            if (! in_array($requiredColumn, $invoiceColumns, true)) {
                return [
                    'rows' => collect(),
                    'total_debit' => 0.0,
                    'total_credit' => 0.0,
                    'balance' => 0.0,
                    'count' => 0,
                    'has_data_source' => false,
                    'source_table' => null,
                ];
            }
        }

        foreach (['id', 'sales_invoice_id', 'amount'] as $requiredColumn) {
            if (! in_array($requiredColumn, $paymentColumns, true)) {
                return [
                    'rows' => collect(),
                    'total_debit' => 0.0,
                    'total_credit' => 0.0,
                    'balance' => 0.0,
                    'count' => 0,
                    'has_data_source' => false,
                    'source_table' => null,
                ];
            }
        }

        $invoiceDateColumn = in_array('issued_at', $invoiceColumns, true) ? 'issued_at' : 'created_at';
        $paymentDateColumn = in_array('paid_at', $paymentColumns, true) ? 'paid_at' : 'created_at';

        $invoicesQuery = DB::table('sales_invoices')
            ->where('customer_id', $customerId);

        if ($from) {
            $invoicesQuery->whereDate($invoiceDateColumn, '>=', $from);
        }

        if ($to) {
            $invoicesQuery->whereDate($invoiceDateColumn, '<=', $to);
        }

        $invoiceRows = $invoicesQuery
            ->get()
            ->map(function ($invoice) use ($invoiceDateColumn) {
                $amount = (float) ($invoice->grand_total ?? 0);

                return [
                    'date' => (string) ($invoice->{$invoiceDateColumn} ?? '-'),
                    'sort_date' => (string) ($invoice->{$invoiceDateColumn} ?? ''),
                    'sort_id' => (int) $invoice->id,
                    'type' => 'فاتورة بيع',
                    'description' => 'فاتورة بيع رقم ' . ($invoice->invoice_number ?? $invoice->id),
                    'status' => (string) ($invoice->payment_status ?? $invoice->status ?? '-'),
                    'debit' => $amount,
                    'credit' => 0.0,
                    'balance' => 0.0,
                ];
            });

        $paymentsQuery = DB::table('sales_invoice_payments')
            ->join('sales_invoices', 'sales_invoice_payments.sales_invoice_id', '=', 'sales_invoices.id')
            ->where('sales_invoices.customer_id', $customerId)
            ->select([
                'sales_invoice_payments.*',
                'sales_invoices.invoice_number',
            ]);

        if ($from) {
            $paymentsQuery->whereDate('sales_invoice_payments.' . $paymentDateColumn, '>=', $from);
        }

        if ($to) {
            $paymentsQuery->whereDate('sales_invoice_payments.' . $paymentDateColumn, '<=', $to);
        }

        $paymentRows = $paymentsQuery
            ->get()
            ->map(function ($payment) use ($paymentDateColumn) {
                $amount = (float) ($payment->amount ?? 0);
                $reference = $payment->reference_number ? ' — مرجع ' . $payment->reference_number : '';

                return [
                    'date' => (string) ($payment->{$paymentDateColumn} ?? '-'),
                    'sort_date' => (string) ($payment->{$paymentDateColumn} ?? ''),
                    'sort_id' => (int) $payment->id,
                    'type' => 'دفعة',
                    'description' => 'دفعة على فاتورة رقم ' . ($payment->invoice_number ?? '-') . $reference,
                    'status' => (string) ($payment->method ?? '-'),
                    'debit' => 0.0,
                    'credit' => $amount,
                    'balance' => 0.0,
                ];
            });

        $runningBalance = 0.0;

        $rows = $invoiceRows
            ->merge($paymentRows)
            ->sortBy([
                ['sort_date', 'asc'],
                ['sort_id', 'asc'],
            ])
            ->values()
            ->map(function (array $row) use (&$runningBalance) {
                $runningBalance = round($runningBalance + (float) $row['debit'] - (float) $row['credit'], 2);

                $row['balance'] = $runningBalance;

                unset($row['sort_date'], $row['sort_id']);

                return $row;
            });

        $totalDebit = (float) $rows->sum('debit');
        $totalCredit = (float) $rows->sum('credit');

        return [
            'rows' => $rows,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'balance' => round($totalDebit - $totalCredit, 2),
            'count' => $rows->count(),
            'has_data_source' => true,
            'source_table' => 'sales_invoices',
        ];
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
