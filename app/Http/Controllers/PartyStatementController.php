<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Supplier;
use App\Services\PartyStatementService;
use Illuminate\Http\Request;

class PartyStatementController extends Controller
{
    public function customer(Request $request, Customer $customer, PartyStatementService $service)
    {
        $from = $request->query('from');
        $to = $request->query('to');

        return view('party-statements.show', [
            'party' => $customer,
            'partyType' => 'customer',
            'partyTypeLabel' => 'عميل',
            'title' => 'كشف حساب العميل',
            'statement' => $service->customerStatement($customer->id, $from, $to),
            'from' => $from,
            'to' => $to,
            'backRoute' => route('customers.show', $customer),
            'exportRoute' => route('customers.statement.export', array_filter([
                'customer' => $customer->id,
                'from' => $from,
                'to' => $to,
            ])),
        ]);
    }

    public function supplier(Request $request, Supplier $supplier, PartyStatementService $service)
    {
        $from = $request->query('from');
        $to = $request->query('to');

        return view('party-statements.show', [
            'party' => $supplier,
            'partyType' => 'supplier',
            'partyTypeLabel' => 'مورد',
            'title' => 'كشف حساب المورد',
            'statement' => $service->supplierStatement($supplier->id, $from, $to),
            'from' => $from,
            'to' => $to,
            'backRoute' => route('suppliers.show', $supplier),
            'exportRoute' => route('suppliers.statement.export', array_filter([
                'supplier' => $supplier->id,
                'from' => $from,
                'to' => $to,
            ])),
        ]);
    }

    public function customerCsv(Request $request, Customer $customer, PartyStatementService $service)
    {
        $statement = $service->customerStatement(
            $customer->id,
            $request->query('from'),
            $request->query('to')
        );

        return $this->csvResponse($statement['rows'], 'customer-statement-' . $customer->id . '.csv');
    }

    public function supplierCsv(Request $request, Supplier $supplier, PartyStatementService $service)
    {
        $statement = $service->supplierStatement(
            $supplier->id,
            $request->query('from'),
            $request->query('to')
        );

        return $this->csvResponse($statement['rows'], 'supplier-statement-' . $supplier->id . '.csv');
    }

    private function csvResponse($rows, string $filename)
    {
        $lines = [];
        $lines[] = ['date', 'type', 'description', 'status', 'debit', 'credit', 'balance'];

        foreach ($rows as $row) {
            $lines[] = [
                $row['date'],
                $row['type'],
                $row['description'],
                $row['status'],
                number_format((float) $row['debit'], 2, '.', ''),
                number_format((float) $row['credit'], 2, '.', ''),
                number_format((float) $row['balance'], 2, '.', ''),
            ];
        }

        $csv = "\xEF\xBB\xBF";

        foreach ($lines as $line) {
            $csv .= implode(',', array_map(function ($value) {
                $value = str_replace('"', '""', (string) $value);

                return '"' . $value . '"';
            }, $line)) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
