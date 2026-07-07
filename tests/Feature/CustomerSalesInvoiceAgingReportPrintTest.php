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

class CustomerSalesInvoiceAgingReportPrintTest extends TestCase
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

    public function test_customer_sales_invoice_aging_report_displays_print_link_with_current_filters(): void
    {
        $user = User::query()->firstOrFail();

        $customer = $this->createCustomer([
            'name' => 'عميل زر طباعة أعمار ذمم العملاء',
            'phone' => '0579851431',
        ]);

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.index', [
            'customer_id' => $customer->id,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="customer-aging-print-link"', false);
        $response->assertSee(route('reports.customer-sales-invoice-aging.print'), false);
        $response->assertSee('customer_id=' . $customer->id, false);
        $response->assertSee('aging_bucket=overdue_31_60', false);
    }

    public function test_customer_sales_invoice_aging_report_print_contains_summary_and_table(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $customer = $this->createCustomer([
            'name' => 'عميل طباعة أعمار ذمم العملاء',
            'phone' => '0579851432',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $customer->id,
            'invoice_number' => 'SI-CUSTOMER-AGING-PRINT-001',
            'remaining_amount' => 1500,
            'grand_total' => 1500,
            'subtotal' => 1500,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.print'));

        $response->assertOk();
        $response->assertSee('تقرير أعمار ذمم العملاء');
        $response->assertSee('تاريخ التقرير: 2026-07-06');
        $response->assertSee('data-testid="customer-aging-print-summary"', false);
        $response->assertSee('عدد العملاء');
        $response->assertSee('عدد الفواتير المفتوحة');
        $response->assertSee('إجمالي الذمم المفتوحة');
        $response->assertSee('إجمالي المتأخر');
        $response->assertSee('data-testid="customer-aging-print-table"', false);
        $response->assertSee('عميل طباعة أعمار ذمم العملاء');
        $response->assertSee('1,500.00');
        $response->assertSee('2026-05-20');
    }

    public function test_customer_sales_invoice_aging_report_print_respects_customer_and_bucket_filters(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $selectedCustomer = $this->createCustomer([
            'name' => 'عميل مطابق لفلاتر طباعة أعمار ذمم العملاء',
            'phone' => '0579851433',
        ]);

        $otherCustomer = $this->createCustomer([
            'name' => 'عميل مستبعد من فلاتر طباعة أعمار ذمم العملاء',
            'phone' => '0579851434',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $selectedCustomer->id,
            'invoice_number' => 'SI-CUSTOMER-AGING-PRINT-IN',
            'remaining_amount' => 1500,
            'grand_total' => 1500,
            'subtotal' => 1500,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $selectedCustomer->id,
            'invoice_number' => 'SI-CUSTOMER-AGING-PRINT-NOT-DUE-OUT',
            'remaining_amount' => 1000,
            'grand_total' => 1000,
            'subtotal' => 1000,
            'due_at' => '2026-07-20 09:00:00',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $otherCustomer->id,
            'invoice_number' => 'SI-CUSTOMER-AGING-PRINT-CUSTOMER-OUT',
            'remaining_amount' => 2000,
            'grand_total' => 2000,
            'subtotal' => 2000,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.print', [
            'customer_id' => $selectedCustomer->id,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();
        $response->assertSee('عميل مطابق لفلاتر طباعة أعمار ذمم العملاء #' . $selectedCustomer->id);
        $response->assertSee('متأخرة 31 إلى 60 يوم');
        $response->assertSee('عميل مطابق لفلاتر طباعة أعمار ذمم العملاء');
        $response->assertSee('1,500.00');
        $response->assertDontSee('عميل مستبعد من فلاتر طباعة أعمار ذمم العملاء');
        $response->assertDontSee('1,000.00');
        $response->assertDontSee('2,000.00');
    }

    public function test_customer_sales_invoice_aging_report_print_displays_empty_state(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.print'));

        $response->assertOk();
        $response->assertSee('data-testid="customer-aging-print-empty"', false);
        $response->assertSee('لا توجد ذمم مفتوحة للعملاء.');
        $response->assertSee('0.00');
    }

    private function createCustomer(array $overrides = []): Customer
    {
        $columns = Schema::getColumnListing('customers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'عميل اختبار طباعة أعمار ذمم العملاء',
            'phone' => '0579851400',
            'email' => uniqid('customer-aging-print-') . '@example.com',
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
            'invoice_number' => uniqid('SI-CUSTOMER-AGING-PRINT-'),
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
