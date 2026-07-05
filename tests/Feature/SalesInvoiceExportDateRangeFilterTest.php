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

class SalesInvoiceExportDateRangeFilterTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 730;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_sales_invoice_export_respects_issued_date_range(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'issued_at'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل تصدير تاريخ الفواتير',
            'phone' => '0579800931',
            'email' => 'sales-invoice-export-date@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-EXPORT-DATE-IN',
            'issued_at' => '2026-07-15 09:00:00',
            'grand_total' => 1400,
            'paid_amount' => 400,
            'remaining_amount' => 1000,
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-EXPORT-DATE-OUT',
            'issued_at' => '2026-08-05 09:00:00',
            'grand_total' => 2400,
            'paid_amount' => 0,
            'remaining_amount' => 2400,
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.export', [
            'customer_id' => $customer->id,
            'issued_from' => '2026-07-01',
            'issued_to' => '2026-07-31',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('SI-EXPORT-DATE-IN', $content);
        $this->assertStringContainsString('1400.00', $content);
        $this->assertStringNotContainsString('SI-EXPORT-DATE-OUT', $content);
        $this->assertStringNotContainsString('2400.00', $content);
    }

    public function test_sales_invoice_export_combines_date_customer_payment_and_collection_filters(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $selectedCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل تصدير تاريخ مركب',
            'phone' => '0579800932',
            'email' => 'sales-invoice-export-date-combined@example.com',
        ]);

        $otherCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل تصدير تاريخ مستبعد',
            'phone' => '0579800933',
            'email' => 'sales-invoice-export-date-other@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-EXPORT-DATE-COMBINED-IN',
            'payment_status' => 'partial',
            'grand_total' => 3000,
            'paid_amount' => 1000,
            'remaining_amount' => 2000,
            'issued_at' => '2026-07-20 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-EXPORT-DATE-PAID-OUT',
            'payment_status' => 'paid',
            'grand_total' => 1200,
            'paid_amount' => 1200,
            'remaining_amount' => 0,
            'issued_at' => '2026-07-21 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $otherCustomer->id, [
            'invoice_number' => 'SI-EXPORT-DATE-CUSTOMER-OUT',
            'payment_status' => 'partial',
            'grand_total' => 1700,
            'paid_amount' => 500,
            'remaining_amount' => 1200,
            'issued_at' => '2026-07-22 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-EXPORT-DATE-RANGE-OUT',
            'payment_status' => 'partial',
            'grand_total' => 1800,
            'paid_amount' => 400,
            'remaining_amount' => 1400,
            'issued_at' => '2026-08-01 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.export', [
            'customer_id' => $selectedCustomer->id,
            'payment_status' => 'partial',
            'collection_status' => 'outstanding',
            'issued_from' => '2026-07-01',
            'issued_to' => '2026-07-31',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('SI-EXPORT-DATE-COMBINED-IN', $content);
        $this->assertStringContainsString('3000.00', $content);
        $this->assertStringContainsString('2000.00', $content);

        $this->assertStringNotContainsString('SI-EXPORT-DATE-PAID-OUT', $content);
        $this->assertStringNotContainsString('SI-EXPORT-DATE-CUSTOMER-OUT', $content);
        $this->assertStringNotContainsString('SI-EXPORT-DATE-RANGE-OUT', $content);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل تصدير تاريخ الفواتير ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'sales-invoice-export-date-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-EXPORT-DATE-' . uniqid(),
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
