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

class SalesInvoiceExportMetadataRowsTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 790;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_sales_invoice_export_includes_report_metadata_rows(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'customer_id'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل بيانات تعريف التصدير',
            'phone' => '0579800991',
            'email' => 'sales-invoice-export-metadata@example.com',
        ]);

        $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-EXPORT-METADATA-IN',
            'payment_status' => 'partial',
            'grand_total' => 1800,
            'paid_amount' => 300,
            'remaining_amount' => 1500,
            'issued_at' => '2026-07-12 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('sales-invoices.export', [
            'customer_id' => $customer->id,
            'payment_status' => 'partial',
            'collection_status' => 'outstanding',
            'issued_from' => '2026-07-01',
            'issued_to' => '2026-07-31',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('تقرير فواتير المبيعات', $content);
        $this->assertStringContainsString('تاريخ إنشاء التقرير', $content);
        $this->assertStringContainsString('فلتر العميل', $content);
        $this->assertStringContainsString((string) $customer->id, $content);
        $this->assertStringContainsString('فلتر حالة الدفع', $content);
        $this->assertStringContainsString('partial', $content);
        $this->assertStringContainsString('فلتر حالة التحصيل', $content);
        $this->assertStringContainsString('outstanding', $content);
        $this->assertStringContainsString('من تاريخ', $content);
        $this->assertStringContainsString('2026-07-01', $content);
        $this->assertStringContainsString('إلى تاريخ', $content);
        $this->assertStringContainsString('2026-07-31', $content);
        $this->assertStringContainsString('SI-EXPORT-METADATA-IN', $content);
        $this->assertStringContainsString('إجمالي النتائج', $content);
    }

    public function test_sales_invoice_export_metadata_uses_all_when_filters_are_empty(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('sales-invoices.export'));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('تقرير فواتير المبيعات', $content);
        $this->assertStringContainsString('"فلتر العميل",all', $content);
        $this->assertStringContainsString('"فلتر حالة الدفع",all', $content);
        $this->assertStringContainsString('"فلتر حالة التحصيل",all', $content);
        $this->assertStringContainsString('"من تاريخ",all', $content);
        $this->assertStringContainsString('"إلى تاريخ",all', $content);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل بيانات تعريف تصدير فواتير المبيعات ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'sales-invoice-export-metadata-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-EXPORT-METADATA-' . uniqid(),
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
