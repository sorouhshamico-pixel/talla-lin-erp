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

class CustomerSalesInvoiceAgingDrilldownTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_customer_sales_invoice_aging_drilldown_page_loads(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.drilldown'));

        $response->assertOk();
        $response->assertSee('data-testid="customer-sales-invoice-aging-drilldown"', false);
        $response->assertSee('تفاصيل فواتير العملاء المفتوحة');
        $response->assertSee('تاريخ التقرير: 2026-07-06');
        $response->assertSee('data-testid="customer-aging-drilldown-filters"', false);
        $response->assertSee('data-testid="customer-aging-drilldown-summary"', false);
        $response->assertSee('عدد الفواتير المفتوحة');
        $response->assertSee('إجمالي المتبقي');
    }

    public function test_customer_sales_invoice_aging_report_displays_drilldown_link(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.index'));

        $response->assertOk();
        $response->assertSee('data-testid="customer-aging-drilldown-link"', false);
        $response->assertSee(route('reports.customer-sales-invoice-aging.drilldown'), false);
    }

    public function test_customer_sales_invoice_aging_drilldown_respects_customer_and_bucket_filters(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $selectedCustomer = $this->createCustomer([
            'name' => 'عميل مطابق لتفاصيل أعمار الذمم',
            'phone' => '0579852531',
        ]);

        $otherCustomer = $this->createCustomer([
            'name' => 'عميل مستبعد من تفاصيل أعمار الذمم',
            'phone' => '0579852532',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $selectedCustomer->id,
            'invoice_number' => 'SI-CUSTOMER-DRILLDOWN-IN',
            'remaining_amount' => 1500,
            'grand_total' => 1500,
            'subtotal' => 1500,
            'paid_amount' => 0,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $selectedCustomer->id,
            'invoice_number' => 'SI-CUSTOMER-DRILLDOWN-NOT-DUE-OUT',
            'remaining_amount' => 1000,
            'grand_total' => 1000,
            'subtotal' => 1000,
            'paid_amount' => 0,
            'due_at' => '2026-07-20 09:00:00',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $otherCustomer->id,
            'invoice_number' => 'SI-CUSTOMER-DRILLDOWN-CUSTOMER-OUT',
            'remaining_amount' => 2000,
            'grand_total' => 2000,
            'subtotal' => 2000,
            'paid_amount' => 0,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.drilldown', [
            'customer_id' => $selectedCustomer->id,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();
        $response->assertSee('عميل مطابق لتفاصيل أعمار الذمم #' . $selectedCustomer->id);
        $response->assertSee('متأخرة 31 إلى 60 يوم');
        $response->assertSee('SI-CUSTOMER-DRILLDOWN-IN');
        $response->assertSee('1,500.00 ريال');
        $response->assertSee('2026-05-20');
        $response->assertDontSee('SI-CUSTOMER-DRILLDOWN-NOT-DUE-OUT');
        $response->assertDontSee('SI-CUSTOMER-DRILLDOWN-CUSTOMER-OUT');
        $response->assertDontSee('1,000.00 ريال');
        $response->assertDontSee('2,000.00 ريال');
    }

    public function test_customer_sales_invoice_aging_drilldown_displays_empty_state(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.drilldown'));

        $response->assertOk();
        $response->assertSee('data-testid="customer-aging-drilldown-empty"', false);
        $response->assertSee('لا توجد فواتير عملاء مفتوحة حسب الفلاتر الحالية.');
    }

    private function createCustomer(array $overrides = []): Customer
    {
        $columns = Schema::getColumnListing('customers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'عميل اختبار تفاصيل أعمار الذمم',
            'phone' => '0579852500',
            'email' => uniqid('customer-aging-drilldown-') . '@example.com',
            'city' => 'الرياض',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return Customer::unguarded(fn () => Customer::query()->create(array_intersect_key($data, array_flip($columns))));
    }

    private function createSalesInvoice(array $overrides = []): SalesInvoice
    {
        $columns = Schema::getColumnListing('sales_invoices');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'branch_id' => (int) DB::table('branches')->value('id'),
            'customer_id' => null,
            'user_id' => (int) DB::table('users')->value('id'),
            'invoice_number' => uniqid('SI-CUSTOMER-DRILLDOWN-'),
            'status' => 'issued',
            'payment_status' => 'partial',
            'currency' => 'SAR',
            'subtotal' => 1000,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 1000,
            'paid_amount' => 0,
            'remaining_amount' => 1000,
            'issued_at' => '2026-07-01 09:00:00',
            'due_at' => '2026-07-01 09:00:00',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return SalesInvoice::unguarded(fn () => SalesInvoice::query()->create(array_intersect_key($data, array_flip($columns))));
    }
}
