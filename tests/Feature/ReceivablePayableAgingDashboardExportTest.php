<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReceivablePayableAgingDashboardExportTest extends TestCase
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

    public function test_receivable_payable_aging_dashboard_displays_export_link(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.receivable-payable-aging-dashboard.index'));

        $response->assertOk();
        $response->assertSee('data-testid="aging-dashboard-export-link"', false);
        $response->assertSee(route('reports.receivable-payable-aging-dashboard.export'), false);
    }

    public function test_receivable_payable_aging_dashboard_export_contains_summary_net_and_bucket_comparison(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer([
            'name' => 'عميل تصدير لوحة أعمار الذمم',
            'phone' => '0579852031',
        ]);

        $supplier = $this->createSupplier([
            'name' => 'مورد تصدير لوحة أعمار الذمم',
            'phone' => '0579852032',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $customer->id,
            'invoice_number' => 'SI-AGING-DASHBOARD-EXPORT-001',
            'remaining_amount' => 5000,
            'grand_total' => 5000,
            'subtotal' => 5000,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PI-AGING-DASHBOARD-EXPORT-001',
            'remaining_amount' => 1800,
            'grand_total' => 1800,
            'subtotal' => 1800,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.receivable-payable-aging-dashboard.export'));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('لوحة أعمار الذمم', $content);
        $this->assertStringContainsString('تاريخ إنشاء التقرير', $content);
        $this->assertStringContainsString('تاريخ التقرير', $content);
        $this->assertStringContainsString('2026-07-06', $content);
        $this->assertStringContainsString('ملخص ذمم العملاء', $content);
        $this->assertStringContainsString('ملخص ذمم الموردين', $content);
        $this->assertStringContainsString('صافي الذمم', $content);
        $this->assertStringContainsString('صافي الذمم المفتوحة', $content);
        $this->assertStringContainsString('صافي لصالح الشركة', $content);
        $this->assertStringContainsString('صافي المتأخرات', $content);
        $this->assertStringContainsString('متأخرات لصالح الشركة', $content);
        $this->assertStringContainsString('مقارنة شرائح الأعمار', $content);
        $this->assertStringContainsString('متأخرة 31 إلى 60', $content);
        $this->assertStringContainsString('5000.00', $content);
        $this->assertStringContainsString('1800.00', $content);
        $this->assertStringContainsString('3200.00', $content);
    }

    private function createCustomer(array $overrides = []): Customer
    {
        $columns = Schema::getColumnListing('customers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'عميل اختبار تصدير لوحة أعمار الذمم',
            'phone' => '0579852000',
            'email' => uniqid('aging-dashboard-export-customer-') . '@example.com',
            'city' => 'الرياض',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return Customer::unguarded(fn () => Customer::query()->create(array_intersect_key($data, array_flip($columns))));
    }

    private function createSupplier(array $overrides = []): Supplier
    {
        $columns = Schema::getColumnListing('suppliers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'مورد اختبار تصدير لوحة أعمار الذمم',
            'phone' => '0579852001',
            'email' => uniqid('aging-dashboard-export-supplier-') . '@example.com',
            'city' => 'الرياض',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return Supplier::unguarded(fn () => Supplier::query()->create(array_intersect_key($data, array_flip($columns))));
    }

    private function createSalesInvoice(array $overrides = []): SalesInvoice
    {
        $columns = Schema::getColumnListing('sales_invoices');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'branch_id' => (int) DB::table('branches')->value('id'),
            'customer_id' => null,
            'user_id' => (int) DB::table('users')->value('id'),
            'invoice_number' => uniqid('SI-AGING-DASHBOARD-EXPORT-'),
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

    private function createPurchaseInvoice(array $overrides = []): PurchaseInvoice
    {
        $columns = Schema::getColumnListing('purchase_invoices');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'branch_id' => (int) DB::table('branches')->value('id'),
            'warehouse_id' => (int) DB::table('warehouses')->value('id'),
            'supplier_id' => null,
            'user_id' => (int) DB::table('users')->value('id'),
            'invoice_number' => uniqid('PI-AGING-DASHBOARD-EXPORT-'),
            'status' => $this->validPurchaseInvoiceStatus(),
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

        return PurchaseInvoice::unguarded(fn () => PurchaseInvoice::query()->create(array_intersect_key($data, array_flip($columns))));
    }

    private function validPurchaseInvoiceStatus(): string
    {
        foreach (glob(database_path('migrations/*.php')) as $file) {
            $migration = file_get_contents($file);

            if (! str_contains($migration, 'purchase_invoices')) {
                continue;
            }

            if (preg_match('/enum\s*\(\s*[\'"]status[\'"]\s*,\s*\[([^\]]+)\]/is', $migration, $matches)) {
                preg_match_all('/[\'"]([^\'"]+)[\'"]/', $matches[1], $values);

                if (! empty($values[1][0])) {
                    return $values[1][0];
                }
            }
        }

        return 'draft';
    }
}
