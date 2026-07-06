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

class SalesInvoiceOverdueCollectionFilterTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 850;

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

    public function test_sales_invoices_index_displays_overdue_collection_filter_option(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('sales-invoices.index'));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-collection-status-filter"', false);
        $response->assertSee('فواتير متأخرة التحصيل');
        $response->assertSee('value="overdue"', false);
    }

    public function test_sales_invoices_index_filters_overdue_collection_invoices(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'due_at'));
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'remaining_amount'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل فواتير متأخرة التحصيل',
            'phone' => '0579801051',
            'email' => 'sales-invoice-overdue-filter@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-OVERDUE-IN',
            'payment_status' => 'partial',
            'grand_total' => 2000,
            'paid_amount' => 500,
            'remaining_amount' => 1500,
            'issued_at' => '2026-06-20 09:00:00',
            'due_at' => '2026-07-01 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-OVERDUE-FUTURE-OUT',
            'payment_status' => 'partial',
            'grand_total' => 1800,
            'paid_amount' => 300,
            'remaining_amount' => 1500,
            'issued_at' => '2026-07-02 09:00:00',
            'due_at' => '2026-07-20 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-OVERDUE-PAID-OUT',
            'payment_status' => 'paid',
            'grand_total' => 900,
            'paid_amount' => 900,
            'remaining_amount' => 0,
            'issued_at' => '2026-06-15 09:00:00',
            'due_at' => '2026-07-01 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.index', [
            'collection_status' => 'overdue',
        ]));

        $response->assertOk();
        $response->assertSee('SI-OVERDUE-IN');
        $response->assertDontSee('SI-OVERDUE-FUTURE-OUT');
        $response->assertDontSee('SI-OVERDUE-PAID-OUT');
        $response->assertSee('value="overdue" selected', false);
    }

    public function test_sales_invoices_index_combines_overdue_with_customer_and_payment_filters(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $selectedCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل متأخر محدد',
            'phone' => '0579801052',
            'email' => 'sales-invoice-overdue-selected@example.com',
        ]);

        $otherCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل متأخر مستبعد',
            'phone' => '0579801053',
            'email' => 'sales-invoice-overdue-other@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-OVERDUE-COMBINED-IN',
            'payment_status' => 'partial',
            'grand_total' => 3000,
            'paid_amount' => 1000,
            'remaining_amount' => 2000,
            'due_at' => '2026-07-01 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-OVERDUE-COMBINED-UNPAID-OUT',
            'payment_status' => 'unpaid',
            'grand_total' => 1400,
            'paid_amount' => 0,
            'remaining_amount' => 1400,
            'due_at' => '2026-07-01 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $otherCustomer->id, [
            'invoice_number' => 'SI-OVERDUE-COMBINED-CUSTOMER-OUT',
            'payment_status' => 'partial',
            'grand_total' => 1600,
            'paid_amount' => 400,
            'remaining_amount' => 1200,
            'due_at' => '2026-07-01 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.index', [
            'customer_id' => $selectedCustomer->id,
            'payment_status' => 'partial',
            'collection_status' => 'overdue',
        ]));

        $response->assertOk();
        $response->assertSee('SI-OVERDUE-COMBINED-IN');
        $response->assertDontSee('SI-OVERDUE-COMBINED-UNPAID-OUT');
        $response->assertDontSee('SI-OVERDUE-COMBINED-CUSTOMER-OUT');
        $response->assertSee('value="' . $selectedCustomer->id . '" selected', false);
        $response->assertSee('value="partial" selected', false);
        $response->assertSee('value="overdue" selected', false);
    }

    public function test_sales_invoice_export_respects_overdue_collection_filter_and_label(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل تصدير فواتير متأخرة',
            'phone' => '0579801054',
            'email' => 'sales-invoice-overdue-export@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-OVERDUE-EXPORT-IN',
            'payment_status' => 'partial',
            'grand_total' => 2200,
            'paid_amount' => 700,
            'remaining_amount' => 1500,
            'due_at' => '2026-07-01 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-OVERDUE-EXPORT-FUTURE-OUT',
            'payment_status' => 'partial',
            'grand_total' => 1200,
            'paid_amount' => 200,
            'remaining_amount' => 1000,
            'due_at' => '2026-07-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.export', [
            'customer_id' => $customer->id,
            'collection_status' => 'overdue',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('فواتير متأخرة التحصيل', $content);
        $this->assertStringContainsString('SI-OVERDUE-EXPORT-IN', $content);
        $this->assertStringNotContainsString('SI-OVERDUE-EXPORT-FUTURE-OUT', $content);
        $this->assertStringContainsString('2200.00', $content);
        $this->assertStringContainsString('1500.00', $content);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل فواتير متأخرة التحصيل ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'sales-invoice-overdue-filter-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-OVERDUE-' . uniqid(),
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
