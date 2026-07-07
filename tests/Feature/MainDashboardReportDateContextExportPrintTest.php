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

class MainDashboardReportDateContextExportPrintTest extends TestCase
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

    public function test_financial_summary_export_includes_as_of_date_context_and_uses_it(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer(['name' => 'عميل تصدير تاريخ الاحتساب']);
        $supplier = $this->createSupplier(['name' => 'مورد تصدير تاريخ الاحتساب']);

        $this->createSalesInvoice([
            'customer_id' => $customer->id,
            'remaining_amount' => 5000,
            'grand_total' => 5000,
            'subtotal' => 5000,
            'due_at' => '2026-07-10 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $supplier->id,
            'remaining_amount' => 3000,
            'grand_total' => 3000,
            'subtotal' => 3000,
            'due_at' => '2026-07-10 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.financial-summary.export', [
            'as_of_date' => '2026-07-20',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('تاريخ الاحتساب', $content);
        $this->assertStringContainsString('2026-07-20', $content);
        $this->assertStringContainsString('5000.00', $content);
        $this->assertStringContainsString('3000.00', $content);
        $this->assertStringContainsString('2000.00', $content);
    }

    public function test_financial_summary_print_includes_as_of_date_context_and_uses_it(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $customer = $this->createCustomer(['name' => 'عميل طباعة تاريخ الاحتساب']);

        $this->createSalesInvoice([
            'customer_id' => $customer->id,
            'remaining_amount' => 5000,
            'grand_total' => 5000,
            'subtotal' => 5000,
            'due_at' => '2026-07-10 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.financial-summary.print', [
            'as_of_date' => '2026-07-20',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="main-dashboard-financial-print-as-of-date-label"', false);
        $response->assertSee('تاريخ الاحتساب:');
        $response->assertSee('2026-07-20');
        $response->assertSee('5,000.00 ريال');
    }

    public function test_top_overdue_exports_include_as_of_date_context_and_use_it(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer(['name' => 'عميل متأخر بتاريخ احتساب']);
        $supplier = $this->createSupplier(['name' => 'مورد متأخر بتاريخ احتساب']);

        $this->createSalesInvoice([
            'customer_id' => $customer->id,
            'remaining_amount' => 5000,
            'grand_total' => 5000,
            'subtotal' => 5000,
            'due_at' => '2026-07-10 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $supplier->id,
            'remaining_amount' => 7000,
            'grand_total' => 7000,
            'subtotal' => 7000,
            'due_at' => '2026-07-10 09:00:00',
        ]);

        $customerResponse = $this->actingAs($user)->get(route('dashboard.top-overdue-customers.export', [
            'as_of_date' => '2026-07-20',
        ]));

        $customerResponse->assertOk();

        $customerContent = $customerResponse->streamedContent();

        $this->assertStringContainsString('تاريخ الاحتساب', $customerContent);
        $this->assertStringContainsString('2026-07-20', $customerContent);
        $this->assertStringContainsString('عميل متأخر بتاريخ احتساب', $customerContent);
        $this->assertStringContainsString('5000.00', $customerContent);
        $this->assertStringContainsString('10', $customerContent);

        $supplierResponse = $this->actingAs($user)->get(route('dashboard.top-overdue-suppliers.export', [
            'as_of_date' => '2026-07-20',
        ]));

        $supplierResponse->assertOk();

        $supplierContent = $supplierResponse->streamedContent();

        $this->assertStringContainsString('تاريخ الاحتساب', $supplierContent);
        $this->assertStringContainsString('2026-07-20', $supplierContent);
        $this->assertStringContainsString('مورد متأخر بتاريخ احتساب', $supplierContent);
        $this->assertStringContainsString('7000.00', $supplierContent);
        $this->assertStringContainsString('10', $supplierContent);
    }

    public function test_top_overdue_print_includes_as_of_date_context_and_uses_it(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $customer = $this->createCustomer(['name' => 'عميل طباعة متأخر بتاريخ احتساب']);

        $this->createSalesInvoice([
            'customer_id' => $customer->id,
            'remaining_amount' => 5000,
            'grand_total' => 5000,
            'subtotal' => 5000,
            'due_at' => '2026-07-10 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.top-overdue.print', [
            'as_of_date' => '2026-07-20',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="main-dashboard-top-overdue-print-as-of-date-label"', false);
        $response->assertSee('تاريخ الاحتساب:');
        $response->assertSee('2026-07-20');
        $response->assertSee('عميل طباعة متأخر بتاريخ احتساب');
        $response->assertSee('5,000.00 ريال');
        $response->assertSee('10 يوم');
    }

    private function createCustomer(array $overrides = []): Customer
    {
        $columns = Schema::getColumnListing('customers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'عميل اختبار سياق تاريخ الاحتساب',
            'phone' => '0579854000',
            'email' => uniqid('dashboard-as-of-context-customer-') . '@example.com',
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
            'name' => 'مورد اختبار سياق تاريخ الاحتساب',
            'phone' => '0579854001',
            'email' => uniqid('dashboard-as-of-context-supplier-') . '@example.com',
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
            'invoice_number' => uniqid('SI-DASHBOARD-AS-OF-CONTEXT-'),
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
            'invoice_number' => uniqid('PI-DASHBOARD-AS-OF-CONTEXT-'),
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
