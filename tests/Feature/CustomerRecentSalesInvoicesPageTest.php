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

class CustomerRecentSalesInvoicesPageTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 430;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_customer_show_displays_recent_sales_invoices(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'customer_id'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل آخر فواتير المبيعات',
            'phone' => '0579800631',
            'email' => 'customer-recent-sales@example.com',
        ]);

        $otherCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل فاتورة غير ظاهرة',
            'phone' => '0579800632',
            'email' => 'customer-recent-sales-other@example.com',
        ]);

        $invoice = $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUST-RECENT-IN',
            'grand_total' => 1800,
            'paid_amount' => 600,
            'remaining_amount' => 1200,
            'payment_status' => 'partial',
            'issued_at' => '2026-07-05 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $otherCustomer->id, [
            'invoice_number' => 'SI-CUST-RECENT-OUT',
            'grand_total' => 9900,
            'paid_amount' => 0,
            'remaining_amount' => 9900,
            'issued_at' => '2026-07-06 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('data-testid="customer-recent-sales-invoices-card"', false);
        $response->assertSee('آخر فواتير مبيعات العميل');
        $response->assertSee('SI-CUST-RECENT-IN');
        $response->assertSee('1,800.00 ريال');
        $response->assertSee('600.00 ريال');
        $response->assertSee('1,200.00 ريال');
        $response->assertSee('مدفوعة جزئيًا');
        $response->assertSee('2026-07-05');
        $response->assertSee(route('sales-invoices.show', $invoice), false);
        $response->assertDontSee('SI-CUST-RECENT-OUT');
    }

    public function test_customer_show_displays_empty_recent_sales_invoices_message(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل بدون آخر فواتير',
            'phone' => '0579800633',
            'email' => 'customer-no-recent-sales@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('data-testid="customer-recent-sales-invoices-card"', false);
        $response->assertSee('لا توجد فواتير مبيعات مرتبطة بهذا العميل بعد.');
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل آخر فواتير المبيعات ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'customer-recent-sales-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-CUST-REC-' . uniqid(),
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
