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

class SalesInvoiceDateRangeFilterTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 700;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_sales_invoices_index_displays_date_range_filters(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('sales-invoices.index'));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-issued-from-filter"', false);
        $response->assertSee('data-testid="sales-invoice-issued-to-filter"', false);
        $response->assertSee('من تاريخ');
        $response->assertSee('إلى تاريخ');
    }

    public function test_sales_invoices_index_filters_by_issued_date_range(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'issued_at'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل فلتر تاريخ الفواتير',
            'phone' => '0579800901',
            'email' => 'sales-invoice-date-filter@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-DATE-RANGE-IN',
            'issued_at' => '2026-07-10 09:00:00',
            'grand_total' => 1300,
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-DATE-RANGE-OUT',
            'issued_at' => '2026-08-05 09:00:00',
            'grand_total' => 2300,
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.index', [
            'issued_from' => '2026-07-01',
            'issued_to' => '2026-07-31',
        ]));

        $response->assertOk();
        $response->assertSee('SI-DATE-RANGE-IN');
        $response->assertDontSee('SI-DATE-RANGE-OUT');
        $response->assertSee('value="2026-07-01"', false);
        $response->assertSee('value="2026-07-31"', false);
    }

    public function test_sales_invoices_index_combines_date_customer_payment_and_collection_filters(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $selectedCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل فلتر تاريخ مركب',
            'phone' => '0579800902',
            'email' => 'sales-invoice-date-combined@example.com',
        ]);

        $otherCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل تاريخ مستبعد',
            'phone' => '0579800903',
            'email' => 'sales-invoice-date-other@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-DATE-COMBINED-IN',
            'payment_status' => 'partial',
            'grand_total' => 3000,
            'paid_amount' => 1000,
            'remaining_amount' => 2000,
            'issued_at' => '2026-07-15 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-DATE-COMBINED-PAID-OUT',
            'payment_status' => 'paid',
            'grand_total' => 1200,
            'paid_amount' => 1200,
            'remaining_amount' => 0,
            'issued_at' => '2026-07-16 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $otherCustomer->id, [
            'invoice_number' => 'SI-DATE-COMBINED-CUSTOMER-OUT',
            'payment_status' => 'partial',
            'grand_total' => 1700,
            'paid_amount' => 500,
            'remaining_amount' => 1200,
            'issued_at' => '2026-07-17 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-DATE-COMBINED-DATE-OUT',
            'payment_status' => 'partial',
            'grand_total' => 1800,
            'paid_amount' => 400,
            'remaining_amount' => 1400,
            'issued_at' => '2026-08-01 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.index', [
            'customer_id' => $selectedCustomer->id,
            'payment_status' => 'partial',
            'collection_status' => 'outstanding',
            'issued_from' => '2026-07-01',
            'issued_to' => '2026-07-31',
        ]));

        $response->assertOk();
        $response->assertSee('SI-DATE-COMBINED-IN');
        $response->assertDontSee('SI-DATE-COMBINED-PAID-OUT');
        $response->assertDontSee('SI-DATE-COMBINED-CUSTOMER-OUT');
        $response->assertDontSee('SI-DATE-COMBINED-DATE-OUT');
        $response->assertSee('value="' . $selectedCustomer->id . '" selected', false);
        $response->assertSee('value="partial" selected', false);
        $response->assertSee('value="outstanding" selected', false);
        $response->assertSee('value="2026-07-01"', false);
        $response->assertSee('value="2026-07-31"', false);
    }

    public function test_sales_invoice_export_link_keeps_date_filters(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('sales-invoices.index', [
            'issued_from' => '2026-07-01',
            'issued_to' => '2026-07-31',
        ]));

        $response->assertOk();
        $response->assertSee('issued_from=2026-07-01', false);
        $response->assertSee('issued_to=2026-07-31', false);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل فلتر التاريخ ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'sales-invoice-date-filter-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-DATE-' . uniqid(),
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
