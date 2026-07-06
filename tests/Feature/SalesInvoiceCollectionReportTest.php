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

class SalesInvoiceCollectionReportTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 970;

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

    public function test_sales_invoice_collection_report_displays_summary_and_follow_up_invoices(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'remaining_amount'));

        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل تقرير التحصيل',
            'phone' => '0579801171',
            'email' => 'sales-invoice-collection-report@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-COLLECTION-REPORT-OVERDUE',
            'payment_status' => 'partial',
            'grand_total' => 3000,
            'paid_amount' => 1000,
            'remaining_amount' => 2000,
            'due_at' => '2026-07-01 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-COLLECTION-REPORT-UNPAID',
            'payment_status' => 'unpaid',
            'grand_total' => 1500,
            'paid_amount' => 0,
            'remaining_amount' => 1500,
            'due_at' => '2026-07-20 09:00:00',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-COLLECTION-REPORT-PAID-OUT',
            'payment_status' => 'paid',
            'grand_total' => 900,
            'paid_amount' => 900,
            'remaining_amount' => 0,
            'due_at' => '2026-07-01 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-collections.index'));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-collection-report-page"', false);
        $response->assertSee('تقرير تحصيل فواتير المبيعات');
        $response->assertSee('data-testid="sales-invoice-collection-summary-card"', false);

        $response->assertSee('SI-COLLECTION-REPORT-OVERDUE');
        $response->assertSee('SI-COLLECTION-REPORT-UNPAID');
        $response->assertDontSee('SI-COLLECTION-REPORT-PAID-OUT');

        $response->assertSee('3,500.00 ريال');
        $response->assertSee('2,000.00 ريال');
        $response->assertSee('1,500.00 ريال');
        $response->assertSee('متأخرة');
        $response->assertSee(route('sales-invoices.index', ['collection_status' => 'overdue']), false);
    }

    public function test_reports_index_displays_sales_invoice_collection_report_link_when_reports_index_exists(): void
    {
        if (! view()->exists('reports.index')) {
            $this->markTestSkipped('reports.index view does not exist.');
        }

        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-collection-report-link"', false);
        $response->assertSee('تقرير تحصيل فواتير المبيعات');
        $response->assertSee(route('reports.sales-invoice-collections.index'), false);
    }

    public function test_sales_invoice_collection_report_displays_empty_state(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-collections.index'));

        $response->assertOk();
        $response->assertSee('data-testid="collection-invoices-empty"', false);
        $response->assertSee('لا توجد فواتير تحتاج متابعة تحصيل.');
        $response->assertSee('0.00 ريال');
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل تقرير التحصيل ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'sales-invoice-collection-report-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-COLLECTION-REPORT-' . uniqid(),
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
