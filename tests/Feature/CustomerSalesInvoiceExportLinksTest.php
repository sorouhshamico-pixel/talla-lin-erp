<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerSalesInvoiceExportLinksTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 520;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_customer_show_displays_sales_invoice_export_links(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل روابط تصدير فواتير المبيعات',
            'phone' => '0579800721',
            'email' => 'customer-sales-export-links@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('data-testid="customer-sales-invoice-export-links-card"', false);
        $response->assertSee('تصدير فواتير مبيعات العميل');
        $response->assertSee('data-testid="customer-sales-invoice-export-all-link"', false);
        $response->assertSee('data-testid="customer-sales-invoice-export-outstanding-link"', false);
        $response->assertSee('data-testid="customer-sales-invoice-export-paid-link"', false);
        $response->assertSee('/sales-invoices/export?customer_id=' . $customer->id, false);
        $response->assertSee('customer_id=' . $customer->id . '&amp;collection_status=outstanding', false);
        $response->assertSee('customer_id=' . $customer->id . '&amp;payment_status=paid', false);
    }

    public function test_sales_invoice_export_respects_customer_filter(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل تصدير فواتير محدد',
            'phone' => '0579800722',
            'email' => 'customer-sales-export-filter@example.com',
        ]);

        $otherCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل تصدير فواتير مستبعد',
            'phone' => '0579800723',
            'email' => 'customer-sales-export-filter-other@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUST-EXPORT-IN',
            'grand_total' => 1600,
            'paid_amount' => 300,
            'remaining_amount' => 1300,
            'payment_status' => 'partial',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $otherCustomer->id, [
            'invoice_number' => 'SI-CUST-EXPORT-OUT',
            'grand_total' => 2600,
            'paid_amount' => 0,
            'remaining_amount' => 2600,
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.export', [
            'customer_id' => $customer->id,
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('رقم الفاتورة', $content);
        $this->assertStringContainsString('SI-CUST-EXPORT-IN', $content);
        $this->assertStringContainsString('عميل تصدير فواتير محدد', $content);
        $this->assertStringContainsString('1600.00', $content);
        $this->assertStringNotContainsString('SI-CUST-EXPORT-OUT', $content);
        $this->assertStringNotContainsString('عميل تصدير فواتير مستبعد', $content);
    }

    public function test_sales_invoice_export_respects_outstanding_and_paid_filters(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل تصدير فواتير حسب الحالة',
            'phone' => '0579800724',
            'email' => 'customer-sales-export-status@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUST-EXPORT-OUTSTANDING',
            'grand_total' => 1200,
            'paid_amount' => 200,
            'remaining_amount' => 1000,
            'payment_status' => 'partial',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-CUST-EXPORT-PAID',
            'grand_total' => 900,
            'paid_amount' => 900,
            'remaining_amount' => 0,
            'payment_status' => 'paid',
        ]);

        $outstandingResponse = $this->actingAs($user)->get(route('sales-invoices.export', [
            'customer_id' => $customer->id,
            'collection_status' => 'outstanding',
        ]));

        $outstandingResponse->assertOk();

        $outstandingContent = $outstandingResponse->streamedContent();

        $this->assertStringContainsString('SI-CUST-EXPORT-OUTSTANDING', $outstandingContent);
        $this->assertStringNotContainsString('SI-CUST-EXPORT-PAID', $outstandingContent);

        $paidResponse = $this->actingAs($user)->get(route('sales-invoices.export', [
            'customer_id' => $customer->id,
            'payment_status' => 'paid',
        ]));

        $paidResponse->assertOk();

        $paidContent = $paidResponse->streamedContent();

        $this->assertStringContainsString('SI-CUST-EXPORT-PAID', $paidContent);
        $this->assertStringNotContainsString('SI-CUST-EXPORT-OUTSTANDING', $paidContent);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل روابط تصدير فواتير المبيعات ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'customer-sales-export-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-CUST-EXPORT-' . uniqid(),
            'status' => 'issued',
            'payment_status' => 'unpaid',
            'currency' => 'SAR',
            'subtotal' => 500,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 500,
            'paid_amount' => 0,
            'remaining_amount' => 500,
            'issued_at' => '2026-07-05 09:00:00',
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
