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

class SalesInvoiceAgingReportTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 1150;

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

    public function test_sales_invoice_aging_report_displays_aging_buckets_and_open_invoices(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'remaining_amount'));
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'due_at'));

        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل تقرير أعمار الذمم',
            'phone' => '0579831151',
            'email' => 'sales-invoice-aging-report@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-AGING-NOT-DUE',
            'grand_total' => 1200,
            'paid_amount' => 200,
            'remaining_amount' => 1000,
            'due_at' => '2026-07-20 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-AGING-1-30',
            'grand_total' => 2500,
            'paid_amount' => 500,
            'remaining_amount' => 2000,
            'due_at' => '2026-06-20 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-AGING-31-60',
            'grand_total' => 1800,
            'paid_amount' => 300,
            'remaining_amount' => 1500,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-AGING-61-90',
            'grand_total' => 1500,
            'paid_amount' => 300,
            'remaining_amount' => 1200,
            'due_at' => '2026-04-20 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-AGING-90-PLUS',
            'grand_total' => 1000,
            'paid_amount' => 100,
            'remaining_amount' => 900,
            'due_at' => '2026-03-01 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-AGING-PAID-OUT',
            'payment_status' => 'paid',
            'grand_total' => 700,
            'paid_amount' => 700,
            'remaining_amount' => 0,
            'due_at' => '2026-03-01 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-aging.index'));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-aging-report-page"', false);
        $response->assertSee('تقرير أعمار ذمم فواتير المبيعات');
        $response->assertSee('data-testid="sales-invoice-aging-summary-card"', false);

        $response->assertSee('غير مستحقة بعد');
        $response->assertSee('متأخرة 1 إلى 30 يوم');
        $response->assertSee('متأخرة 31 إلى 60 يوم');
        $response->assertSee('متأخرة 61 إلى 90 يوم');
        $response->assertSee('أكثر من 90 يوم');

        $response->assertSee('1,000.00 ريال');
        $response->assertSee('2,000.00 ريال');
        $response->assertSee('1,500.00 ريال');
        $response->assertSee('1,200.00 ريال');
        $response->assertSee('900.00 ريال');
        $response->assertSee('6,600.00 ريال');

        $response->assertSee('SI-AGING-NOT-DUE');
        $response->assertSee('SI-AGING-1-30');
        $response->assertSee('SI-AGING-31-60');
        $response->assertSee('SI-AGING-61-90');
        $response->assertSee('SI-AGING-90-PLUS');
        $response->assertDontSee('SI-AGING-PAID-OUT');
    }

    public function test_reports_index_displays_sales_invoice_aging_report_link(): void
    {
        if (! view()->exists('reports.index')) {
            $this->markTestSkipped('reports.index view does not exist.');
        }

        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-aging-report-link"', false);
        $response->assertSee('تقرير أعمار ذمم فواتير المبيعات');
        $response->assertSee(route('reports.sales-invoice-aging.index'), false);
    }

    public function test_sales_invoice_aging_report_displays_empty_state(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-aging.index'));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-aging-empty"', false);
        $response->assertSee('لا توجد فواتير مفتوحة.');
        $response->assertSee('0.00 ريال');
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل تقرير أعمار الذمم ' . $this->customerSequence,
            'phone' => '057983' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'sales-invoice-aging-report-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-AGING-' . uniqid(),
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
