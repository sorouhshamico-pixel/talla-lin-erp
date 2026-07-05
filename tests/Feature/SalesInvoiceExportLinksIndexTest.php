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

class SalesInvoiceExportLinksIndexTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 670;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_sales_invoices_index_displays_export_links(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('sales-invoices.index'));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-export-links-card"', false);
        $response->assertSee('تصدير فواتير المبيعات');
        $response->assertSee('data-testid="sales-invoice-export-filtered-link"', false);
        $response->assertSee('data-testid="sales-invoice-export-all-link"', false);
        $response->assertSee('/sales-invoices/export', false);
    }

    public function test_sales_invoices_index_export_link_keeps_current_filters(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل رابط تصدير فواتير المبيعات',
            'phone' => '0579800871',
            'email' => 'sales-invoice-index-export-link@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.index', [
            'customer_id' => $customer->id,
            'payment_status' => 'partial',
            'collection_status' => 'outstanding',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-export-filtered-link"', false);
        $response->assertSee('customer_id=' . $customer->id, false);
        $response->assertSee('payment_status=partial', false);
        $response->assertSee('collection_status=outstanding', false);
    }

    public function test_sales_invoices_index_export_link_download_respects_filters(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'customer_id'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل تصدير نتائج فواتير المبيعات',
            'phone' => '0579800872',
            'email' => 'sales-invoice-index-export-download@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-INDEX-EXPORT-IN',
            'payment_status' => 'partial',
            'grand_total' => 2000,
            'paid_amount' => 500,
            'remaining_amount' => 1500,
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-INDEX-EXPORT-PAID-OUT',
            'payment_status' => 'paid',
            'grand_total' => 900,
            'paid_amount' => 900,
            'remaining_amount' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.export', [
            'customer_id' => $customer->id,
            'payment_status' => 'partial',
            'collection_status' => 'outstanding',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('SI-INDEX-EXPORT-IN', $content);
        $this->assertStringNotContainsString('SI-INDEX-EXPORT-PAID-OUT', $content);
        $this->assertStringContainsString('2000.00', $content);
        $this->assertStringContainsString('1500.00', $content);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل رابط تصدير فواتير المبيعات ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'sales-invoice-index-export-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-INDEX-EXPORT-' . uniqid(),
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
