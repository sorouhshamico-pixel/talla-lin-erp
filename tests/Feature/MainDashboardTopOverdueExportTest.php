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

class MainDashboardTopOverdueExportTest extends TestCase
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

    public function test_main_dashboard_displays_top_overdue_export_links(): void
    {
        if (! Route::has('dashboard')) {
            $this->markTestSkipped('dashboard route does not exist.');
        }

        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('data-testid="main-dashboard-top-overdue-customers-export-link"', false);
        $response->assertSee('data-testid="main-dashboard-top-overdue-suppliers-export-link"', false);
        $response->assertSee(route('dashboard.top-overdue-customers.export'), false);
        $response->assertSee(route('dashboard.top-overdue-suppliers.export'), false);
        $response->assertSee('تصدير العملاء CSV');
        $response->assertSee('تصدير الموردين CSV');
    }

    public function test_top_overdue_customers_export_contains_customer_rows(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer([
            'name' => 'عميل تصدير ودجت المتأخرات',
            'phone' => '0579853431',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $customer->id,
            'invoice_number' => 'SI-TOP-CUSTOMERS-EXPORT-001',
            'remaining_amount' => 5000,
            'grand_total' => 5000,
            'subtotal' => 5000,
            'paid_amount' => 0,
            'due_at' => '2026-05-01 09:00:00',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $customer->id,
            'invoice_number' => 'SI-TOP-CUSTOMERS-EXPORT-002',
            'remaining_amount' => 1500,
            'grand_total' => 1500,
            'subtotal' => 1500,
            'paid_amount' => 0,
            'due_at' => '2026-06-01 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.top-overdue-customers.export'));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('أكبر العملاء المتأخرين', $content);
        $this->assertStringContainsString('عميل تصدير ودجت المتأخرات', $content);
        $this->assertStringContainsString('2', $content);
        $this->assertStringContainsString('6500.00', $content);
        $this->assertStringContainsString('2026-05-01', $content);
        $this->assertStringContainsString('66', $content);
    }

    public function test_top_overdue_suppliers_export_contains_supplier_rows(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $supplier = $this->createSupplier([
            'name' => 'مورد تصدير ودجت المتأخرات',
            'phone' => '0579853432',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PI-TOP-SUPPLIERS-EXPORT-001',
            'remaining_amount' => 7000,
            'grand_total' => 7000,
            'subtotal' => 7000,
            'paid_amount' => 0,
            'due_at' => '2026-05-01 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PI-TOP-SUPPLIERS-EXPORT-002',
            'remaining_amount' => 2500,
            'grand_total' => 2500,
            'subtotal' => 2500,
            'paid_amount' => 0,
            'due_at' => '2026-06-01 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.top-overdue-suppliers.export'));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('أكبر الموردين المتأخرين', $content);
        $this->assertStringContainsString('مورد تصدير ودجت المتأخرات', $content);
        $this->assertStringContainsString('2', $content);
        $this->assertStringContainsString('9500.00', $content);
        $this->assertStringContainsString('2026-05-01', $content);
        $this->assertStringContainsString('66', $content);
    }

    private function createCustomer(array $overrides = []): Customer
    {
        $columns = Schema::getColumnListing('customers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'عميل اختبار تصدير ودجت المتأخرات',
            'phone' => '0579853400',
            'email' => uniqid('main-dashboard-top-overdue-export-customer-') . '@example.com',
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
            'name' => 'مورد اختبار تصدير ودجت المتأخرات',
            'phone' => '0579853401',
            'email' => uniqid('main-dashboard-top-overdue-export-supplier-') . '@example.com',
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
            'invoice_number' => uniqid('SI-TOP-OVERDUE-EXPORT-'),
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
            'invoice_number' => uniqid('PI-TOP-OVERDUE-EXPORT-'),
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
