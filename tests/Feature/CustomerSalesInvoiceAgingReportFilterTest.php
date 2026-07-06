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

class CustomerSalesInvoiceAgingReportFilterTest extends TestCase
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

    public function test_customer_sales_invoice_aging_report_displays_filters(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.index'));

        $response->assertOk();
        $response->assertSee('data-testid="customer-aging-filters-card"', false);
        $response->assertSee('data-testid="customer-aging-customer-filter"', false);
        $response->assertSee('data-testid="customer-aging-bucket-filter"', false);
        $response->assertSee('data-testid="customer-aging-apply-filters-button"', false);
        $response->assertSee('data-testid="customer-aging-reset-filters-link"', false);
    }

    public function test_customer_sales_invoice_aging_report_filters_by_customer_and_bucket(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $selectedCustomer = $this->customer($companyId, 'عميل فلتر أعمار ذمم العملاء', '0579851301');
        $otherCustomer = $this->customer($companyId, 'عميل مستبعد من فلتر أعمار ذمم العملاء', '0579851302');

        $this->invoice($companyId, $branchId, $selectedCustomer->id, 'SI-CUSTOMER-AGING-FILTER-IN', 1500, '2026-05-20 09:00:00');
        $this->invoice($companyId, $branchId, $selectedCustomer->id, 'SI-CUSTOMER-AGING-FILTER-NOT-DUE-OUT', 1000, '2026-07-20 09:00:00');
        $this->invoice($companyId, $branchId, $otherCustomer->id, 'SI-CUSTOMER-AGING-FILTER-CUSTOMER-OUT', 2000, '2026-05-20 09:00:00');

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.index', [
            'customer_id' => $selectedCustomer->id,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();
        $response->assertSee('عميل فلتر أعمار ذمم العملاء');
        $response->assertSee('1,500.00 ريال');
        $response->assertDontSee('1,000.00 ريال');
        $response->assertDontSee('2,000.00 ريال');
        $response->assertSee('value="' . $selectedCustomer->id . '" selected', false);
        $response->assertSee('value="overdue_31_60" selected', false);
    }

    private function customer(int $companyId, string $name, string $phone): Customer
    {
        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => $name,
            'phone' => $phone,
            'email' => uniqid('customer-aging-filter-') . '@example.com',
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
