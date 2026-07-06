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

class SalesInvoiceAgingReportExportTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 1210;

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

    public function test_sales_invoice_aging_report_displays_export_link_with_current_filters(): void
    {
        $user = User::query()->firstOrFail();

        $customer = $this->createCustomer((int) DB::table('companies')->value('id'), [
            'name' => 'عميل رابط تصدير أعمار الذمم',
            'phone' => '0579831211',
            'email' => 'sales-invoice-aging-export-link@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-aging.index', [
            'customer_id' => $customer->id,
            'payment_status' => 'partial',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-aging-report-export-link"', false);
        $response->assertSee('customer_id=' . $customer->id, false);
        $response->assertSee('payment_status=partial', false);
    }

    public function test_sales_invoice_aging_report_export_respects_filters_and_outputs_csv(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'remaining_amount'));
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'due_at'));

        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $selectedCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل تصدير أعمار الذمم',
            'phone' => '0579831212',
            'email' => 'sales-invoice-aging-export-selected@example.com',
        ]);

        $otherCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل مستبعد من تصدير أعمار الذمم',
            'phone' => '0579831213',
            'email' => 'sales-invoice-aging-export-other@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-AGING-EXPORT-NOT-DUE',
            'payment_status' => 'partial',
            'grand_total' => 1200,
            'paid_amount' => 200,
            'remaining_amount' => 1000,
            'due_at' => '2026-07-20 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-AGING-EXPORT-OVERDUE',
            'payment_status' => 'partial',
            'grand_total' => 2500,
            'paid_amount' => 500,
            'remaining_amount' => 2000,
            'due_at' => '2026-06-20 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-AGING-EXPORT-UNPAID-OUT',
            'payment_status' => 'unpaid',
            'grand_total' => 1600,
            'paid_amount' => 0,
            'remaining_amount' => 1600,
            'due_at' => '2026-06-20 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $otherCustomer->id, [
            'invoice_number' => 'SI-AGING-EXPORT-CUSTOMER-OUT',
            'payment_status' => 'partial',
            'grand_total' => 1800,
            'paid_amount' => 300,
            'remaining_amount' => 1500,
            'due_at' => '2026-06-20 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-AGING-EXPORT-PAID-OUT',
            'payment_status' => 'paid',
            'grand_total' => 900,
            'paid_amount' => 900,
            'remaining_amount' => 0,
            'due_at' => '2026-06-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-aging.export', [
            'customer_id' => $selectedCustomer->id,
            'payment_status' => 'partial',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('تقرير أعمار ذمم فواتير المبيعات', $content);
        $this->assertStringContainsString('فلتر العميل', $content);
        $this->assertStringContainsString('عميل تصدير أعمار الذمم #' . $selectedCustomer->id, $content);
        $this->assertStringContainsString('فلتر حالة الدفع', $content);
        $this->assertStringContainsString('مدفوعة جزئيًا', $content);

        $this->assertStringContainsString('ملخص شرائح الأعمار', $content);
        $this->assertStringContainsString('غير مستحقة بعد', $content);
        $this->assertStringContainsString('متأخرة 1 إلى 30 يوم', $content);

        $this->assertStringContainsString('SI-AGING-EXPORT-NOT-DUE', $content);
        $this->assertStringContainsString('SI-AGING-EXPORT-OVERDUE', $content);
        $this->assertStringContainsString('1000.00', $content);
        $this->assertStringContainsString('2000.00', $content);
        $this->assertStringContainsString('إجمالي الفواتير المفتوحة', $content);
        $this->assertStringContainsString('3000.00', $content);

        $this->assertStringNotContainsString('SI-AGING-EXPORT-UNPAID-OUT', $content);
        $this->assertStringNotContainsString('SI-AGING-EXPORT-CUSTOMER-OUT', $content);
        $this->assertStringNotContainsString('SI-AGING-EXPORT-PAID-OUT', $content);
    }

    public function test_sales_invoice_aging_report_export_keeps_all_labels_when_filters_are_empty(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-aging.export'));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('"فلتر العميل",all', $content);
        $this->assertStringContainsString('"فلتر حالة الدفع",all', $content);
        $this->assertStringContainsString('إجمالي الفواتير المفتوحة', $content);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل تصدير أعمار الذمم ' . $this->customerSequence,
            'phone' => '057983' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'sales-invoice-aging-export-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-AGING-EXPORT-' . uniqid(),
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
