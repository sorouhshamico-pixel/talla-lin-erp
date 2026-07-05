<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SalesInvoiceExportTotalsRowTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 760;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_sales_invoice_export_includes_totals_row_for_filtered_results(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'grand_total'));
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'paid_amount'));
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'remaining_amount'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل إجماليات تصدير فواتير المبيعات',
            'phone' => '0579800961',
            'email' => 'sales-invoice-export-totals@example.com',
        ]);

        $otherCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل مستبعد من إجماليات التصدير',
            'phone' => '0579800962',
            'email' => 'sales-invoice-export-totals-other@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-EXPORT-TOTALS-001',
            'payment_status' => 'partial',
            'grand_total' => 1000,
            'paid_amount' => 400,
            'remaining_amount' => 600,
            'issued_at' => '2026-07-10 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-EXPORT-TOTALS-002',
            'payment_status' => 'partial',
            'grand_total' => 700,
            'paid_amount' => 100,
            'remaining_amount' => 600,
            'issued_at' => '2026-07-11 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $otherCustomer->id, [
            'invoice_number' => 'SI-EXPORT-TOTALS-OUT',
            'payment_status' => 'partial',
            'grand_total' => 9999,
            'paid_amount' => 0,
            'remaining_amount' => 9999,
            'issued_at' => '2026-07-12 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.export', [
            'customer_id' => $customer->id,
            'payment_status' => 'partial',
            'collection_status' => 'outstanding',
            'issued_from' => '2026-07-01',
            'issued_to' => '2026-07-31',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('SI-EXPORT-TOTALS-001', $content);
        $this->assertStringContainsString('SI-EXPORT-TOTALS-002', $content);
        $this->assertStringNotContainsString('SI-EXPORT-TOTALS-OUT', $content);

        $this->assertStringContainsString('إجمالي النتائج', $content);
        $this->assertStringContainsString('1700.00', $content);
        $this->assertStringContainsString('500.00', $content);
        $this->assertStringContainsString('1200.00', $content);
        $this->assertStringContainsString('عدد الفواتير: 2', $content);
        $this->assertStringNotContainsString('9999.00', $content);
    }

    public function test_sales_invoice_export_totals_row_handles_empty_filtered_results(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل بدون نتائج تصدير',
            'phone' => '0579800963',
            'email' => 'sales-invoice-export-empty-totals@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.export', [
            'customer_id' => $customer->id,
            'payment_status' => 'paid',
            'issued_from' => '2026-07-01',
            'issued_to' => '2026-07-31',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('إجمالي النتائج', $content);
        $this->assertStringContainsString('0.00', $content);
        $this->assertStringContainsString('عدد الفواتير: 0', $content);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل إجماليات تصدير فواتير المبيعات ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'sales-invoice-export-totals-' . $this->customerSequence . '@example.com',
            'city' => 'الرياض',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $data = array_merge($data, $overrides);
        $data = array_intersect_key($data, array_flip($columns));

        return Customer::unguarded(fn () => Customer::query()->create($data));
    }

    private function createSalesInvoice(int $companyId, int $branchId, int $customerId, array $overrides = []): SalesInvoice
    {
        $columns = Schema::getColumnListing('sales_invoices');

        $data = [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'customer_id' => $customerId,
            'user_id' => (int) DB::table('users')->value('id'),
            'invoice_number' => 'SI-EXPORT-TOTALS-' . uniqid(),
            'status' => 'issued',
            'payment_status' => 'unpaid',
            'currency' => 'SAR',
            'subtotal' => 500,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 500,
            'paid_amount' => 0,
            'remaining_amount' => 500,
            'issued_at' => '2026-07-05 09:00:00',
            'due_at' => '2026-07-20 09:00:00',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $data = array_merge($data, $overrides);
        $data = array_intersect_key($data, array_flip($columns));

        return SalesInvoice::unguarded(fn () => SalesInvoice::query()->create($data));
    }
}
