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

class SalesInvoicePaymentStatusFilterUiTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 580;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_sales_invoices_index_displays_payment_status_filter(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('sales-invoices.index'));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-payment-status-filter"', false);
        $response->assertSee('كل الحالات');
        $response->assertSee('غير مدفوعة');
        $response->assertSee('مدفوعة جزئيًا');
        $response->assertSee('مدفوعة بالكامل');
    }

    public function test_sales_invoices_index_filters_by_payment_status(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'payment_status'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل فلتر حالة الدفع',
            'phone' => '0579800781',
            'email' => 'sales-invoice-payment-status-filter@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-PAYMENT-STATUS-PAID',
            'payment_status' => 'paid',
            'grand_total' => 1800,
            'paid_amount' => 1800,
            'remaining_amount' => 0,
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-PAYMENT-STATUS-UNPAID',
            'payment_status' => 'unpaid',
            'grand_total' => 900,
            'paid_amount' => 0,
            'remaining_amount' => 900,
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.index', [
            'payment_status' => 'paid',
        ]));

        $response->assertOk();
        $response->assertSee('SI-PAYMENT-STATUS-PAID');
        $response->assertDontSee('SI-PAYMENT-STATUS-UNPAID');
        $response->assertSee('value="paid" selected', false);
    }

    public function test_sales_invoices_index_combines_customer_and_payment_status_filters(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $selectedCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل حالة الدفع المحدد',
            'phone' => '0579800782',
            'email' => 'selected-payment-status-customer@example.com',
        ]);

        $otherCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل حالة الدفع مستبعد',
            'phone' => '0579800783',
            'email' => 'excluded-payment-status-customer@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-CUSTOMER-PAID-IN',
            'payment_status' => 'paid',
            'grand_total' => 1000,
            'paid_amount' => 1000,
            'remaining_amount' => 0,
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-CUSTOMER-UNPAID-OUT',
            'payment_status' => 'unpaid',
            'grand_total' => 1000,
            'paid_amount' => 0,
            'remaining_amount' => 1000,
        ]);

        $this->createSalesInvoice($companyId, $branchId, $otherCustomer->id, [
            'invoice_number' => 'SI-OTHER-CUSTOMER-PAID-OUT',
            'payment_status' => 'paid',
            'grand_total' => 1500,
            'paid_amount' => 1500,
            'remaining_amount' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.index', [
            'customer_id' => $selectedCustomer->id,
            'payment_status' => 'paid',
        ]));

        $response->assertOk();
        $response->assertSee('SI-CUSTOMER-PAID-IN');
        $response->assertDontSee('SI-CUSTOMER-UNPAID-OUT');
        $response->assertDontSee('SI-OTHER-CUSTOMER-PAID-OUT');
        $response->assertSee('value="' . $selectedCustomer->id . '" selected', false);
        $response->assertSee('value="paid" selected', false);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل فلتر حالة الدفع ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'sales-invoice-payment-status-filter-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-PAYMENT-STATUS-' . uniqid(),
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
