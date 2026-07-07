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

    public function test_customer_sales_invoice_aging_report_displays_export_link_with_filters(): void
    {
        $user = User::query()->firstOrFail();

        $customer = $this->customer((int) DB::table('companies')->value('id'), 'عميل رابط تصدير أعمار ذمم العملاء', '0579851331');

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.index', [
            'customer_id' => $customer->id,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="customer-aging-report-export-link"', false);
        $response->assertSee('customer_id=' . $customer->id, false);
        $response->assertSee('aging_bucket=overdue_31_60', false);
    }

    public function test_customer_sales_invoice_aging_report_export_respects_filters(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $selectedCustomer = $this->customer($companyId, 'عميل تصدير أعمار ذمم العملاء', '0579851332');
        $otherCustomer = $this->customer($companyId, 'عميل مستبعد من تصدير أعمار ذمم العملاء', '0579851333');

        $this->invoice($companyId, $branchId, $selectedCustomer->id, 'SI-CUSTOMER-AGING-EXPORT-IN', 1500, '2026-05-20 09:00:00');
        $this->invoice($companyId, $branchId, $selectedCustomer->id, 'SI-CUSTOMER-AGING-EXPORT-NOT-DUE-OUT', 1000, '2026-07-20 09:00:00');
        $this->invoice($companyId, $branchId, $otherCustomer->id, 'SI-CUSTOMER-AGING-EXPORT-CUSTOMER-OUT', 2000, '2026-05-20 09:00:00');

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.export', [
            'customer_id' => $selectedCustomer->id,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('تقرير أعمار ذمم العملاء', $content);
        $this->assertStringContainsString('فلتر العميل', $content);
        $this->assertStringContainsString('عميل تصدير أعمار ذمم العملاء #' . $selectedCustomer->id, $content);
        $this->assertStringContainsString('فلتر شريحة العمر', $content);
        $this->assertStringContainsString('متأخرة 31 إلى 60 يوم', $content);
        $this->assertStringContainsString('1500.00', $content);
        $this->assertStringNotContainsString('SI-CUSTOMER-AGING-EXPORT-NOT-DUE-OUT', $content);
        $this->assertStringNotContainsString('SI-CUSTOMER-AGING-EXPORT-CUSTOMER-OUT', $content);
    }

    public function test_customer_sales_invoice_aging_report_export_keeps_all_labels_when_filters_are_empty(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.export'));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('"فلتر العميل",all', $content);
        $this->assertStringContainsString('"فلتر شريحة العمر",all', $content);
        $this->assertStringContainsString('ملخص عام', $content);
    }

    private function customer(int $companyId, string $name, string $phone): Customer
    {
        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => $name,
            'phone' => $phone,
            'email' => uniqid('customer-aging-export-') . '@example.com',
            'city' => 'الرياض',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return Customer::unguarded(fn () => Customer::query()->create(array_intersect_key($data, array_flip($columns))));
    }

    private function invoice(int $companyId, int $branchId, int $customerId, string $number, float $remaining, string $dueAt): SalesInvoice
    {
        $columns = Schema::getColumnListing('sales_invoices');

        $data = [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'customer_id' => $customerId,
            'user_id' => (int) DB::table('users')->value('id'),
            'invoice_number' => $number,
            'status' => 'issued',
            'payment_status' => 'partial',
            'currency' => 'SAR',
            'subtotal' => $remaining,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => $remaining,
            'paid_amount' => 0,
            'remaining_amount' => $remaining,
            'issued_at' => '2026-07-01 09:00:00',
            'due_at' => $dueAt,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return SalesInvoice::unguarded(fn () => SalesInvoice::query()->create(array_intersect_key($data, array_flip($columns))));
    }
}
