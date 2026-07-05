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

class SalesInvoiceCollectionStatusFilterUiTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 610;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_sales_invoices_index_displays_collection_status_filter(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('sales-invoices.index'));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-collection-status-filter"', false);
        $response->assertSee('كل الفواتير');
        $response->assertSee('فواتير ذات مبالغ متبقية');
    }

    public function test_sales_invoices_index_filters_by_outstanding_collection_status(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'remaining_amount'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل فلتر التحصيل',
            'phone' => '0579800811',
            'email' => 'sales-invoice-collection-filter@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-COLLECTION-OUTSTANDING',
            'payment_status' => 'partial',
            'grand_total' => 1800,
            'paid_amount' => 800,
            'remaining_amount' => 1000,
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-COLLECTION-PAID',
            'payment_status' => 'paid',
            'grand_total' => 900,
            'paid_amount' => 900,
            'remaining_amount' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.index', [
            'collection_status' => 'outstanding',
        ]));

        $response->assertOk();
        $response->assertSee('SI-COLLECTION-OUTSTANDING');
        $response->assertDontSee('SI-COLLECTION-PAID');
        $response->assertSee('value="outstanding" selected', false);
    }

    public function test_sales_invoices_index_combines_customer_payment_and_collection_filters(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $selectedCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل تحصيل محدد',
            'phone' => '0579800812',
            'email' => 'selected-collection-customer@example.com',
        ]);

        $otherCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل تحصيل مستبعد',
            'phone' => '0579800813',
            'email' => 'excluded-collection-customer@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-COLLECTION-CUSTOMER-PARTIAL-IN',
            'payment_status' => 'partial',
            'grand_total' => 2000,
            'paid_amount' => 500,
            'remaining_amount' => 1500,
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-COLLECTION-CUSTOMER-PAID-OUT',
            'payment_status' => 'paid',
            'grand_total' => 1000,
            'paid_amount' => 1000,
            'remaining_amount' => 0,
        ]);

        $this->createSalesInvoice($companyId, $branchId, $otherCustomer->id, [
            'invoice_number' => 'SI-COLLECTION-OTHER-PARTIAL-OUT',
            'payment_status' => 'partial',
            'grand_total' => 1600,
            'paid_amount' => 300,
            'remaining_amount' => 1300,
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.index', [
            'customer_id' => $selectedCustomer->id,
            'payment_status' => 'partial',
            'collection_status' => 'outstanding',
        ]));

        $response->assertOk();
        $response->assertSee('SI-COLLECTION-CUSTOMER-PARTIAL-IN');
        $response->assertDontSee('SI-COLLECTION-CUSTOMER-PAID-OUT');
        $response->assertDontSee('SI-COLLECTION-OTHER-PARTIAL-OUT');
        $response->assertSee('value="' . $selectedCustomer->id . '" selected', false);
        $response->assertSee('value="partial" selected', false);
        $response->assertSee('value="outstanding" selected', false);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل فلتر التحصيل ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'sales-invoice-collection-filter-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-COLLECTION-' . uniqid(),
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
