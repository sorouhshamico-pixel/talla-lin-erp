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

class CustomerPaidSalesInvoiceSummaryPageTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 490;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_customer_show_displays_paid_sales_invoice_summary(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'payment_status'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل ملخص الفواتير المدفوعة',
            'phone' => '0579800691',
            'email' => 'customer-paid-summary@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUST-PAID-001',
            'grand_total' => 800,
            'paid_amount' => 800,
            'remaining_amount' => 0,
            'payment_status' => 'paid',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUST-PAID-002',
            'grand_total' => 1200,
            'paid_amount' => 1200,
            'remaining_amount' => 0,
            'payment_status' => 'paid',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUST-PAID-UNPAID',
            'grand_total' => 900,
            'paid_amount' => 0,
            'remaining_amount' => 900,
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('data-testid="customer-paid-sales-invoice-summary-card"', false);
        $response->assertSee('ملخص فواتير العميل المدفوعة بالكامل');
        $response->assertSee('data-testid="customer-paid-sales-invoice-summary-count"', false);
        $response->assertSee('>2<', false);
        $response->assertSee('2,000.00 ريال');
        $response->assertSee('فواتير مدفوعة مسجلة');
        $response->assertSee('data-testid="customer-paid-sales-invoice-summary-link"', false);
        $response->assertSee('customer_id=' . $customer->id, false);
        $response->assertSee('payment_status=paid', false);
    }

    public function test_sales_invoices_index_can_filter_customer_paid_invoices(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل فلترة الفواتير المدفوعة',
            'phone' => '0579800692',
            'email' => 'customer-paid-filter@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUST-PAID-IN',
            'grand_total' => 1400,
            'paid_amount' => 1400,
            'remaining_amount' => 0,
            'payment_status' => 'paid',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUST-PAID-EXCLUDED',
            'grand_total' => 900,
            'paid_amount' => 0,
            'remaining_amount' => 900,
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.index', [
            'customer_id' => $customer->id,
            'payment_status' => 'paid',
        ]));

        $response->assertOk();
        $response->assertSee('SI-CUST-PAID-IN');
        $response->assertDontSee('SI-CUST-PAID-EXCLUDED');
    }

    public function test_customer_show_displays_zero_paid_summary_when_customer_has_no_paid_invoices(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل بدون فواتير مدفوعة',
            'phone' => '0579800693',
            'email' => 'customer-no-paid-invoices@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUST-NO-PAID',
            'grand_total' => 700,
            'paid_amount' => 0,
            'remaining_amount' => 700,
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('data-testid="customer-paid-sales-invoice-summary-card"', false);
        $response->assertSee('>0<', false);
        $response->assertSee('0.00 ريال');
        $response->assertSee('لا توجد فواتير مدفوعة بالكامل');
        $response->assertSee('payment_status=paid', false);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل ملخص المدفوع ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'customer-paid-summary-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-CUST-PAID-' . uniqid(),
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
