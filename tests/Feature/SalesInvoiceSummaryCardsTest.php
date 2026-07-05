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

class SalesInvoiceSummaryCardsTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 640;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_sales_invoices_index_displays_summary_cards(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'grand_total'));
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'paid_amount'));
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'remaining_amount'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل ملخص صفحة فواتير المبيعات',
            'phone' => '0579800841',
            'email' => 'sales-invoice-summary-cards@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-SUMMARY-CARD-001',
            'payment_status' => 'partial',
            'grand_total' => 1000,
            'paid_amount' => 400,
            'remaining_amount' => 600,
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-SUMMARY-CARD-002',
            'payment_status' => 'paid',
            'grand_total' => 500,
            'paid_amount' => 500,
            'remaining_amount' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.index', [
            'customer_id' => $customer->id,
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-summary-card"', false);
        $response->assertSee('ملخص فواتير المبيعات');
        $response->assertSee('data-testid="sales-invoice-summary-count"', false);
        $response->assertSee('>2<', false);
        $response->assertSee('1,500.00 ريال');
        $response->assertSee('900.00 ريال');
        $response->assertSee('600.00 ريال');
        $response->assertSee('data-testid="sales-invoice-summary-outstanding-count"', false);
        $response->assertSee('data-testid="sales-invoice-summary-paid-count"', false);
    }

    public function test_sales_invoices_summary_cards_respect_customer_filter(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $selectedCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل ملخص الفلاتر المحدد',
            'phone' => '0579800842',
            'email' => 'sales-invoice-summary-selected@example.com',
        ]);

        $otherCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل ملخص الفلاتر المستبعد',
            'phone' => '0579800843',
            'email' => 'sales-invoice-summary-other@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-SUMMARY-FILTER-IN-001',
            'payment_status' => 'partial',
            'grand_total' => 2000,
            'paid_amount' => 500,
            'remaining_amount' => 1500,
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-SUMMARY-FILTER-IN-002',
            'payment_status' => 'paid',
            'grand_total' => 1000,
            'paid_amount' => 1000,
            'remaining_amount' => 0,
        ]);

        $this->createSalesInvoice($companyId, $branchId, $otherCustomer->id, [
            'invoice_number' => 'SI-SUMMARY-FILTER-OUT',
            'payment_status' => 'unpaid',
            'grand_total' => 9999,
            'paid_amount' => 0,
            'remaining_amount' => 9999,
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.index', [
            'customer_id' => $selectedCustomer->id,
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-summary-card"', false);
        $response->assertSee('SI-SUMMARY-FILTER-IN-001');
        $response->assertSee('SI-SUMMARY-FILTER-IN-002');
        $response->assertDontSee('SI-SUMMARY-FILTER-OUT');
        $response->assertSee('>2<', false);
        $response->assertSee('3,000.00 ريال');
        $response->assertSee('1,500.00 ريال');
        $response->assertDontSee('9,999.00 ريال');
    }

    public function test_sales_invoices_summary_cards_respect_payment_and_collection_filters(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل ملخص حسب الدفع والتحصيل',
            'phone' => '0579800844',
            'email' => 'sales-invoice-summary-payment-collection@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-SUMMARY-PARTIAL-IN',
            'payment_status' => 'partial',
            'grand_total' => 2500,
            'paid_amount' => 700,
            'remaining_amount' => 1800,
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-SUMMARY-PARTIAL-PAID-OUT',
            'payment_status' => 'paid',
            'grand_total' => 1100,
            'paid_amount' => 1100,
            'remaining_amount' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.index', [
            'payment_status' => 'partial',
            'collection_status' => 'outstanding',
        ]));

        $response->assertOk();
        $response->assertSee('SI-SUMMARY-PARTIAL-IN');
        $response->assertDontSee('SI-SUMMARY-PARTIAL-PAID-OUT');
        $response->assertSee('2,500.00 ريال');
        $response->assertSee('700.00 ريال');
        $response->assertSee('1,800.00 ريال');
        $response->assertSee('value="partial" selected', false);
        $response->assertSee('value="outstanding" selected', false);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل ملخص صفحة الفواتير ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'sales-invoice-summary-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-SUMMARY-' . uniqid(),
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
