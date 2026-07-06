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

class SalesInvoiceDueDateRangeFilterTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 910;

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

    public function test_sales_invoices_index_displays_due_date_range_filters(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('sales-invoices.index'));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-due-from-filter"', false);
        $response->assertSee('data-testid="sales-invoice-due-to-filter"', false);
        $response->assertSee('من تاريخ الاستحقاق');
        $response->assertSee('إلى تاريخ الاستحقاق');
    }

    public function test_sales_invoices_index_filters_by_due_date_range(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'due_at'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل فلتر تاريخ الاستحقاق',
            'phone' => '0579801111',
            'email' => 'sales-invoice-due-date-filter@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-DUE-RANGE-IN',
            'due_at' => '2026-07-15 09:00:00',
            'grand_total' => 1300,
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-DUE-RANGE-OUT',
            'due_at' => '2026-08-05 09:00:00',
            'grand_total' => 2300,
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.index', [
            'due_from' => '2026-07-01',
            'due_to' => '2026-07-31',
        ]));

        $response->assertOk();
        $response->assertSee('SI-DUE-RANGE-IN');
        $response->assertDontSee('SI-DUE-RANGE-OUT');
        $response->assertSee('value="2026-07-01"', false);
        $response->assertSee('value="2026-07-31"', false);
    }

    public function test_sales_invoices_index_combines_due_date_with_collection_filters(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $selectedCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل فلتر استحقاق مركب',
            'phone' => '0579801112',
            'email' => 'sales-invoice-due-date-combined@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-DUE-COMBINED-IN',
            'payment_status' => 'partial',
            'grand_total' => 3000,
            'paid_amount' => 1000,
            'remaining_amount' => 2000,
            'issued_at' => '2026-07-01 09:00:00',
            'due_at' => '2026-07-05 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-DUE-COMBINED-DATE-OUT',
            'payment_status' => 'partial',
            'grand_total' => 1800,
            'paid_amount' => 400,
            'remaining_amount' => 1400,
            'issued_at' => '2026-07-01 09:00:00',
            'due_at' => '2026-08-01 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-DUE-COMBINED-PAID-OUT',
            'payment_status' => 'paid',
            'grand_total' => 900,
            'paid_amount' => 900,
            'remaining_amount' => 0,
            'issued_at' => '2026-07-01 09:00:00',
            'due_at' => '2026-07-04 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.index', [
            'customer_id' => $selectedCustomer->id,
            'payment_status' => 'partial',
            'collection_status' => 'overdue',
            'due_from' => '2026-07-01',
            'due_to' => '2026-07-31',
        ]));

        $response->assertOk();
        $response->assertSee('SI-DUE-COMBINED-IN');
        $response->assertDontSee('SI-DUE-COMBINED-DATE-OUT');
        $response->assertDontSee('SI-DUE-COMBINED-PAID-OUT');
        $response->assertSee('value="' . $selectedCustomer->id . '" selected', false);
        $response->assertSee('value="partial" selected', false);
        $response->assertSee('value="overdue" selected', false);
        $response->assertSee('value="2026-07-01"', false);
        $response->assertSee('value="2026-07-31"', false);
    }

    public function test_sales_invoice_export_respects_due_date_range_filters_and_metadata(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل تصدير تاريخ الاستحقاق',
            'phone' => '0579801113',
            'email' => 'sales-invoice-due-date-export@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-DUE-EXPORT-IN',
            'payment_status' => 'partial',
            'grand_total' => 2000,
            'paid_amount' => 500,
            'remaining_amount' => 1500,
            'due_at' => '2026-07-15 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-DUE-EXPORT-OUT',
            'payment_status' => 'partial',
            'grand_total' => 1200,
            'paid_amount' => 200,
            'remaining_amount' => 1000,
            'due_at' => '2026-08-15 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.export', [
            'customer_id' => $customer->id,
            'due_from' => '2026-07-01',
            'due_to' => '2026-07-31',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('من تاريخ الاستحقاق', $content);
        $this->assertStringContainsString('إلى تاريخ الاستحقاق', $content);
        $this->assertStringContainsString('2026-07-01', $content);
        $this->assertStringContainsString('2026-07-31', $content);
        $this->assertStringContainsString('SI-DUE-EXPORT-IN', $content);
        $this->assertStringNotContainsString('SI-DUE-EXPORT-OUT', $content);
        $this->assertStringContainsString('2000.00', $content);
        $this->assertStringContainsString('1500.00', $content);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل فلتر تاريخ الاستحقاق ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'sales-invoice-due-date-filter-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-DUE-' . uniqid(),
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
