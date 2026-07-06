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

class SalesInvoiceAgingReportBucketFilterTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 1240;

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

    public function test_sales_invoice_aging_report_displays_bucket_filter(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-aging.index'));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-aging-bucket-filter"', false);
        $response->assertSee('كل الشرائح');
        $response->assertSee('value="overdue_31_60"', false);
    }

    public function test_sales_invoice_aging_report_filters_by_aging_bucket(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'due_at'));

        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل فلتر شريحة أعمار الذمم',
            'phone' => '0579831241',
            'email' => 'sales-invoice-aging-bucket-filter@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-AGING-BUCKET-IN',
            'grand_total' => 2000,
            'paid_amount' => 500,
            'remaining_amount' => 1500,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-AGING-BUCKET-NOT-DUE-OUT',
            'grand_total' => 1200,
            'paid_amount' => 200,
            'remaining_amount' => 1000,
            'due_at' => '2026-07-20 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-AGING-BUCKET-OVER-90-OUT',
            'grand_total' => 1000,
            'paid_amount' => 100,
            'remaining_amount' => 900,
            'due_at' => '2026-03-01 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-aging.index', [
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();
        $response->assertSee('SI-AGING-BUCKET-IN');
        $response->assertSee('1,500.00 ريال');
        $response->assertDontSee('SI-AGING-BUCKET-NOT-DUE-OUT');
        $response->assertDontSee('SI-AGING-BUCKET-OVER-90-OUT');
        $response->assertSee('value="overdue_31_60" selected', false);
    }

    public function test_sales_invoice_aging_report_export_respects_aging_bucket_filter(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل تصدير شريحة أعمار الذمم',
            'phone' => '0579831242',
            'email' => 'sales-invoice-aging-bucket-export@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-AGING-BUCKET-EXPORT-IN',
            'grand_total' => 2200,
            'paid_amount' => 700,
            'remaining_amount' => 1500,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-AGING-BUCKET-EXPORT-OUT',
            'grand_total' => 1800,
            'paid_amount' => 300,
            'remaining_amount' => 1500,
            'due_at' => '2026-07-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-aging.export', [
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('SI-AGING-BUCKET-EXPORT-IN', $content);
        $this->assertStringContainsString('متأخرة 31 إلى 60 يوم', $content);
        $this->assertStringContainsString('1500.00', $content);
        $this->assertStringNotContainsString('SI-AGING-BUCKET-EXPORT-OUT', $content);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل فلتر شريحة أعمار الذمم ' . $this->customerSequence,
            'phone' => '057983' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'sales-invoice-aging-bucket-filter-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-AGING-BUCKET-' . uniqid(),
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
