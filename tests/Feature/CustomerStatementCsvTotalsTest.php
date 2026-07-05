<?php

namespace Tests\Feature;

use App\Models\SalesInvoice;
use App\Models\User;
use App\Services\InventoryStockService;
use App\Services\SalesInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerStatementCsvTotalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_statement_csv_includes_total_debit_total_credit_and_balance(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = SalesInvoice::query()->where('invoice_number', 'INV-DEMO-001')->firstOrFail();

        $invoiceService = app(SalesInvoiceService::class);

        $invoiceService->issueInvoice($invoice, app(InventoryStockService::class));

        $invoice->refresh();

        $invoiceService->recordPayment(
            invoice: $invoice,
            user: $admin,
            amount: 300,
            method: 'bank_transfer',
            referenceNumber: 'PAY-STMT-CSV-001',
            notes: 'دفعة لاختبار إجماليات CSV.'
        );

        $response = $this->actingAs($admin)
            ->get(route('customers.statement.export', $invoice->customer_id));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $response->assertSee('"date","type","description","status","debit","credit","balance"', false);
        $response->assertSee('"summary","total_debit","","","851.00","",""', false);
        $response->assertSee('"summary","total_credit","","","","300.00",""', false);
        $response->assertSee('"summary","balance","","","","","551.00"', false);
    }
}
