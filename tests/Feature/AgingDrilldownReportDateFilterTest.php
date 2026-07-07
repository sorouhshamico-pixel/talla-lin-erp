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
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AgingDrilldownReportDateFilterTest extends TestCase
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

    public function test_customer_drilldown_uses_as_of_date_for_aging_bucket(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $customer = $this->createCustomer(['name' => 'عميل تفاصيل بتاريخ تقرير']);

        $this->createSalesInvoice([
            'customer_id' => $customer->id,
            'invoice_number' => 'SI-CUSTOMER-AS-OF-IN',
            'remaining_amount' => 1500,
            'grand_total' => 1500,
            'subtotal' => 1500,
            'due_at' => '2026-07-10 09:00:00',
        ]);

        $defaultResponse = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.drilldown', [
            'aging_bucket' => 'overdue_1_30',
        ]));

        $defaultResponse->assertOk();
        $defaultResponse->assertDontSee('SI-CUSTOMER-AS-OF-IN');

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.drilldown', [
            'as_of_date' => '2026-07-20',
            'aging_bucket' => 'overdue_1_30',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="customer-aging-drilldown-as-of-date-input"', false);
        $response->assertSee('data-testid="customer-aging-drilldown-as-of-date-filter"', false);
        $response->assertSee('value="2026-07-20"', false);
        $response->assertSee('تاريخ التقرير:');
        $response->assertSee('2026-07-20');
        $response->assertSee('SI-CUSTOMER-AS-OF-IN');
        $response->assertSee('1,500.00 ريال');
        $response->assertSee('as_of_date=2026-07-20', false);
    }

    public function test_supplier_drilldown_uses_as_of_date_for_aging_bucket(): void
    {
        $user = User::query()->firstOrFail();

        PurchaseInvoice::query()->delete();

        $supplier = $this->createSupplier(['name' => 'مورد تفاصيل بتاريخ تقرير']);

        $this->createPurchaseInvoice([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PI-SUPPLIER-AS-OF-IN',
            'remaining_amount' => 2500,
            'grand_total' => 2500,
            'subtotal' => 2500,
            'due_at' => '2026-07-10 09:00:00',
        ]);

        $defaultResponse = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.drilldown', [
            'aging_bucket' => 'overdue_1_30',
        ]));

        $defaultResponse->assertOk();
        $defaultResponse->assertDontSee('PI-SUPPLIER-AS-OF-IN');

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.drilldown', [
            'as_of_date' => '2026-07-20',
            'aging_bucket' => 'overdue_1_30',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-aging-drilldown-as-of-date-input"', false);
        $response->assertSee('data-testid="supplier-aging-drilldown-as-of-date-filter"', false);
        $response->assertSee('value="2026-07-20"', false);
        $response->assertSee('تاريخ التقرير:');
        $response->assertSee('2026-07-20');
        $response->assertSee('PI-SUPPLIER-AS-OF-IN');
        $response->assertSee('2,500.00 ريال');
        $response->assertSee('as_of_date=2026-07-20', false);
    }

    public function test_customer_and_supplier_drilldown_exports_include_as_of_date_context(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer(['name' => 'عميل تصدير تفاصيل بتاريخ تقرير']);
        $supplier = $this->createSupplier(['name' => 'مورد تصدير تفاصيل بتاريخ تقرير']);

        $this->createSalesInvoice([
            'customer_id' => $customer->id,
            'invoice_number' => 'SI-CUSTOMER-AS-OF-EXPORT-IN',
            'remaining_amount' => 1500,
            'grand_total' => 1500,
            'subtotal' => 1500,
            'due_at' => '2026-07-10 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PI-SUPPLIER-AS-OF-EXPORT-IN',
            'remaining_amount' => 2500,
            'grand_total' => 2500,
            'subtotal' => 2500,
            'due_at' => '2026-07-10 09:00:00',
        ]);

        $customerResponse = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.drilldown.export', [
            'as_of_date' => '2026-07-20',
            'aging_bucket' => 'overdue_1_30',
        ]));

        $customerResponse->assertOk();

        $customerContent = $customerResponse->streamedContent();

        $this->assertStringContainsString('تاريخ التقرير', $customerContent);
        $this->assertStringContainsString('2026-07-20', $customerContent);
        $this->assertStringContainsString('SI-CUSTOMER-AS-OF-EXPORT-IN', $customerContent);
        $this->assertStringContainsString('1500.00', $customerContent);

        $supplierResponse = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.drilldown.export', [
            'as_of_date' => '2026-07-20',
            'aging_bucket' => 'overdue_1_30',
        ]));

        $supplierResponse->assertOk();

        $supplierContent = $supplierResponse->streamedContent();

        $this->assertStringContainsString('تاريخ التقرير', $supplierContent);
        $this->assertStringContainsString('2026-07-20', $supplierContent);
        $this->assertStringContainsString('PI-SUPPLIER-AS-OF-EXPORT-IN', $supplierContent);
        $this->assertStringContainsString('2500.00', $supplierContent);
    }

    public function test_main_dashboard_preserves_as_of_date_on_aging_drilldown_links(): void
    {
        if (! Route::has('dashboard')) {
            $this->markTestSkipped('dashboard route does not exist.');
        }

        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('dashboard', [
            'as_of_date' => '2026-07-20',
        ]));

        $response->assertOk();
        $response->assertSee('as_of_date=2026-07-20', false);
        $response->assertSee('customer-sales-invoice-aging', false);
        $response->assertSee('supplier-purchase-invoice-aging', false);
    }

    private function createCustomer(array $overrides = []): Customer
    {
        $columns = Schema::getColumnListing('customers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'عميل اختبار تاريخ تقرير تفاصيل الأعمار',
            'phone' => '0579854100',
            'email' => uniqid('aging-as-of-customer-') . '@example.com',
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
            'name' => 'مورد اختبار تاريخ تقرير تفاصيل الأعمار',
            'phone' => '0579854101',
            'email' => uniqid('aging-as-of-supplier-') . '@example.com',
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
            'invoice_number' => uniqid('SI-AGING-AS-OF-'),
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
            'invoice_number' => uniqid('PI-AGING-AS-OF-'),
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
