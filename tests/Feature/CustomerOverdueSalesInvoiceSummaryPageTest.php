<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerOverdueSalesInvoiceSummaryPageTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 880;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-06 10:00:00');

        $this->seed(InitialSetupSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_customer_show_displays_overdue_sales_invoice_summary(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'due_at'));
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'remaining_amount'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل ملخص فواتير متأخرة',
            'phone' => '0579801081',
            'email' => 'customer-overdue-summary@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUSTOMER-OVERDUE-001',
            'payment_status' => 'partial',
            'grand_total' => 2000,
            'paid_amount' => 500,
            'remaining_amount' => 1500,
            'due_at' => '2026-07-01 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUSTOMER-OVERDUE-002',
            'payment_status' => 'unpaid',
            'grand_total' => 800,
            'paid_amount' => 0,
            'remaining_amount' => 800,
            'due_at' => '2026-07-02 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUSTOMER-OVERDUE-FUTURE-OUT',
            'payment_status' => 'partial',
            'grand_total' => 1200,
            'paid_amount' => 200,
            'remaining_amount' => 1000,
            'due_at' => '2026-07-20 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUSTOMER-OVERDUE-PAID-OUT',
            'payment_status' => 'paid',
            'grand_total' => 900,
            'paid_amount' => 900,
            'remaining_amount' => 0,
            'due_at' => '2026-07-01 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('data-testid="customer-overdue-sales-invoice-summary-card"', false);
        $response->assertSee('ملخص فواتير العميل المتأخرة التحصيل');
        $response->assertSee('data-testid="customer-overdue-sales-invoice-summary-count"', false);
        $response->assertSee('>2<', false);
        $response->assertSee('2,300.00 ريال');
        $response->assertSee('يحتاج متابعة عاجلة');
        $response->assertSee('data-testid="customer-overdue-sales-invoice-summary-link"', false);
        $response->assertSee('customer_id=' . $customer->id, false);
        $response->assertSee('collection_status=overdue', false);
    }

    public function test_customer_show_displays_zero_overdue_summary_when_no_invoice_is_overdue(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل بدون فواتير متأخرة',
            'phone' => '0579801082',
            'email' => 'customer-no-overdue@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUSTOMER-NO-OVERDUE',
            'payment_status' => 'partial',
            'grand_total' => 1000,
            'paid_amount' => 300,
            'remaining_amount' => 700,
            'due_at' => '2026-07-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('data-testid="customer-overdue-sales-invoice-summary-card"', false);
        $response->assertSee('>0<', false);
        $response->assertSee('0.00 ريال');
        $response->assertSee('لا توجد فواتير متأخرة');
        $response->assertSee('collection_status=overdue', false);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل ملخص فواتير متأخرة ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'customer-overdue-summary-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-CUSTOMER-OVERDUE-' . uniqid(),
            'status' => 'issued',
            'payment_status' => 'unpaid',
            'currency' => 'SAR',
            'subtotal' => 500,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 500,
            'paid_amount' => 0,
            'remaining_amount' => 500,
            'issued_at' => '2026-07-01 09:00:00',
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
