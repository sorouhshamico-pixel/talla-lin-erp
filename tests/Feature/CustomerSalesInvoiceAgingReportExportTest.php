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

class CustomerSalesInvoiceAgingReportExportTest extends TestCase
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

    public function test_customer_sales_invoice_aging_report_displays_export_button_with_current_filters(): void
    {
        $user = User::query()->firstOrFail();

        $customer = $this->createCustomer([
            'name' => 'عميل زر تصدير أعمار ذمم العملاء',
            'phone' => '0579851331',
        ]);

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.index', [
            'customer_id' => $customer->id,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="customer-aging-export-button"', false);
        $response->assertSee(route('reports.customer-sales-invoice-aging.export'), false);
        $response->assertSee('customer_id=' . $customer->id, false);
        $response->assertSee('aging_bucket=overdue_31_60', false);
    }

    public function test_customer_sales_invoice_aging_report_export_contains_header_summary_and_table(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $selectedCustomer = $this->createCustomer([
            'name' => 'عميل تصدير أعمار ذمم العملاء',
            'phone' => '0579851332',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $selectedCustomer->id,
            'invoice_number' => 'SI-CUSTOMER-AGING-EXPORT-001',
            'remaining_amount' => 1500,
            'grand_total' => 1500,
            'subtotal' => 1500,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.export'));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('تقرير أعمار ذمم العملاء', $content);
        $this->assertStringContainsString('تاريخ إنشاء التقرير', $content);
        $this->assertStringContainsString('تاريخ التقرير', $content);
        $this->assertStringContainsString('2026-07-06', $content);
        $this->assertStringContainsString('فلتر العميل', $content);
        $this->assertStringContainsString('فلتر شريحة العمر', $content);
        $this->assertStringContainsString('ملخص عام', $content);
        $this->assertStringContainsString('عدد العملاء', $content);
        $this->assertStringContainsString('عدد الفواتير المفتوحة', $content);
        $this->assertStringContainsString('إجمالي الذمم المفتوحة', $content);
        $this->assertStringContainsString('إجمالي المتأخر', $content);
        $this->assertStringContainsString('العميل', $content);
        $this->assertStringContainsString('عدد الفواتير', $content);
        $this->assertStringContainsString('إجمالي المتبقي', $content);
        $this->assertStringContainsString('غير مستحقة بعد', $content);
        $this->assertStringContainsString('متأخرة 1 إلى 30', $content);
        $this->assertStringContainsString('متأخرة 31 إلى 60', $content);
        $this->assertStringContainsString('متأخرة 61 إلى 90', $content);
        $this->assertStringContainsString('أكثر من 90', $content);
        $this->assertStringContainsString('بدون تاريخ استحقاق', $content);
        $this->assertStringContainsString('أقدم استحقاق', $content);
        $this->assertStringContainsString('عميل تصدير أعمار ذمم العملاء', $content);
        $this->assertStringContainsString('1500.00', $content);
        $this->assertStringContainsString('2026-05-20', $content);
    }

    public function test_customer_sales_invoice_aging_report_export_respects_customer_and_bucket_filters(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $selectedCustomer = $this->createCustomer([
            'name' => 'عميل مطابق لفلاتر تصدير أعمار ذمم العملاء',
            'phone' => '0579851333',
        ]);

        $otherCustomer = $this->createCustomer([
            'name' => 'عميل مستبعد من فلاتر تصدير أعمار ذمم العملاء',
            'phone' => '0579851334',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $selectedCustomer->id,
            'invoice_number' => 'SI-CUSTOMER-AGING-EXPORT-IN',
            'remaining_amount' => 1500,
            'grand_total' => 1500,
            'subtotal' => 1500,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $selectedCustomer->id,
            'invoice_number' => 'SI-CUSTOMER-AGING-EXPORT-NOT-DUE-OUT',
            'remaining_amount' => 1000,
            'grand_total' => 1000,
            'subtotal' => 1000,
            'due_at' => '2026-07-20 09:00:00',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $otherCustomer->id,
            'invoice_number' => 'SI-CUSTOMER-AGING-EXPORT-CUSTOMER-OUT',
            'remaining_amount' => 2000,
            'grand_total' => 2000,
            'subtotal' => 2000,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.export', [
            'customer_id' => $selectedCustomer->id,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('عميل مطابق لفلاتر تصدير أعمار ذمم العملاء #' . $selectedCustomer->id, $content);
        $this->assertStringContainsString('متأخرة 31 إلى 60 يوم', $content);
        $this->assertStringContainsString('عميل مطابق لفلاتر تصدير أعمار ذمم العملاء', $content);
        $this->assertStringContainsString('1500.00', $content);
        $this->assertStringNotContainsString('عميل مستبعد من فلاتر تصدير أعمار ذمم العملاء', $content);
        $this->assertStringNotContainsString('1000.00', $content);
        $this->assertStringNotContainsString('2000.00', $content);
    }

    public function test_customer_sales_invoice_aging_report_export_uses_all_label_when_filters_are_empty(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.export'));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('all', $content);
        $this->assertStringContainsString('عدد العملاء', $content);
        $this->assertStringContainsString('عدد الفواتير المفتوحة', $content);
        $this->assertStringContainsString('0', $content);
    }

    private function createCustomer(array $overrides = []): Customer
    {
        $columns = Schema::getColumnListing('customers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'عميل اختبار تصدير أعمار ذمم العملاء',
            'phone' => '0579851300',
            'email' => uniqid('customer-aging-export-') . '@example.com',
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
            'invoice_number' => uniqid('SI-CUSTOMER-AGING-EXPORT-'),
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
