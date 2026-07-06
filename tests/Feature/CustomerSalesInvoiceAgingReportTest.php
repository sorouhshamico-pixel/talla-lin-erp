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

class CustomerSalesInvoiceAgingReportTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 1270;

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

    public function test_customer_sales_invoice_aging_report_groups_open_invoices_by_customer(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'remaining_amount'));

        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $firstCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل أعمار ذمم أول',
            'phone' => '0579841271',
            'email' => 'customer-aging-first@example.com',
        ]);

        $secondCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل أعمار ذمم ثاني',
            'phone' => '0579841272',
            'email' => 'customer-aging-second@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $firstCustomer->id, [
            'invoice_number' => 'SI-CUSTOMER-AGING-FIRST-1',
            'grand_total' => 2500,
            'paid_amount' => 500,
            'remaining_amount' => 2000,
            'due_at' => '2026-06-20 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $firstCustomer->id, [
            'invoice_number' => 'SI-CUSTOMER-AGING-FIRST-2',
            'grand_total' => 1200,
            'paid_amount' => 200,
            'remaining_amount' => 1000,
            'due_at' => '2026-07-20 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $secondCustomer->id, [
            'invoice_number' => 'SI-CUSTOMER-AGING-SECOND-1',
            'grand_total' => 1800,
            'paid_amount' => 300,
            'remaining_amount' => 1500,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $secondCustomer->id, [
            'invoice_number' => 'SI-CUSTOMER-AGING-PAID-OUT',
            'payment_status' => 'paid',
            'grand_total' => 900,
            'paid_amount' => 900,
            'remaining_amount' => 0,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.index'));

        $response->assertOk();
        $response->assertSee('data-testid="customer-sales-invoice-aging-report-page"', false);
        $response->assertSee('تقرير أعمار ذمم العملاء');
        $response->assertSee('data-testid="customer-aging-summary-card"', false);
        $response->assertSee('data-testid="customer-aging-table-card"', false);

        $response->assertSee('عميل أعمار ذمم أول');
        $response->assertSee('عميل أعمار ذمم ثاني');

        $response->assertSee('4,500.00 ريال');
        $response->assertSee('3,500.00 ريال');
        $response->assertSee('3,000.00 ريال');
        $response->assertSee('1,500.00 ريال');
        $response->assertSee('2,000.00 ريال');
        $response->assertSee('1,000.00 ريال');

        $response->assertDontSee('SI-CUSTOMER-AGING-PAID-OUT');
        $response->assertSee('customer_id=' . $firstCustomer->id . '&amp;collection_status=outstanding', false);
    }

    public function test_reports_index_displays_customer_sales_invoice_aging_report_link(): void
    {
        if (! view()->exists('reports.index')) {
            $this->markTestSkipped('reports.index view does not exist.');
        }

        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('data-testid="customer-sales-invoice-aging-report-link"', false);
        $response->assertSee('تقرير أعمار ذمم العملاء');
        $response->assertSee(route('reports.customer-sales-invoice-aging.index'), false);
    }

    public function test_customer_sales_invoice_aging_report_displays_empty_state(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.index'));

        $response->assertOk();
        $response->assertSee('data-testid="customer-aging-empty"', false);
        $response->assertSee('لا توجد ذمم مفتوحة للعملاء.');
        $response->assertSee('0.00 ريال');
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل أعمار ذمم العملاء ' . $this->customerSequence,
            'phone' => '057984' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'customer-aging-report-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-CUSTOMER-AGING-' . uniqid(),
            'status' => 'issued',
            'payment_status' => 'partial',
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
