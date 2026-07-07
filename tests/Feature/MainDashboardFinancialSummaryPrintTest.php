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

class MainDashboardFinancialSummaryPrintTest extends TestCase
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

    public function test_main_dashboard_displays_financial_summary_print_link(): void
    {
        if (! Route::has('dashboard')) {
            $this->markTestSkipped('dashboard route does not exist.');
        }

        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('data-testid="main-dashboard-financial-print-link"', false);
        $response->assertSee(route('dashboard.financial-summary.print'), false);
        $response->assertSee('طباعة الملخص');
    }

    public function test_main_dashboard_financial_summary_print_contains_summary_and_risk_indicators(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer([
            'name' => 'عميل طباعة ملخص الصفحة الرئيسية',
            'phone' => '0579853131',
        ]);

        $supplier = $this->createSupplier([
            'name' => 'مورد طباعة ملخص الصفحة الرئيسية',
            'phone' => '0579853132',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $customer->id,
            'invoice_number' => 'SI-MAIN-PRINT-001',
            'remaining_amount' => 1000,
            'grand_total' => 1000,
            'subtotal' => 1000,
            'paid_amount' => 0,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PI-MAIN-PRINT-001',
            'remaining_amount' => 3000,
            'grand_total' => 3000,
            'subtotal' => 3000,
            'paid_amount' => 0,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.financial-summary.print'));

        $response->assertOk();
        $response->assertSee('الملخص المالي السريع للوحة التحكم');
        $response->assertSee('تاريخ التقرير: 2026-07-06 10:00:00');
        $response->assertSee('data-testid="main-dashboard-financial-print-button"', false);
        $response->assertSee('data-testid="main-dashboard-financial-print-customer-summary"', false);
        $response->assertSee('data-testid="main-dashboard-financial-print-supplier-summary"', false);
        $response->assertSee('data-testid="main-dashboard-financial-print-cash-flow-summary"', false);
        $response->assertSee('data-testid="main-dashboard-financial-print-risk-summary"', false);
        $response->assertSee('ذمم العملاء المفتوحة');
        $response->assertSee('1,000.00 ريال');
        $response->assertSee('التزامات الموردين المفتوحة');
        $response->assertSee('3,000.00 ريال');
        $response->assertSee('صافي التدفق النقدي المتوقع');
        $response->assertSee('-2,000.00 ريال');
        $response->assertSee('صافي الضغط النقدي المتأخر');
        $response->assertSee('2,000.00 ريال');
        $response->assertSee('33.33%');
        $response->assertSee('تغطية نقدية متوقعة غير كافية');
        $response->assertSee('يتطلب متابعة نقدية');
    }

    private function createCustomer(array $overrides = []): Customer
    {
        $columns = Schema::getColumnListing('customers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'عميل اختبار طباعة ملخص الصفحة الرئيسية',
            'phone' => '0579853100',
            'email' => uniqid('main-dashboard-print-customer-') . '@example.com',
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
            'name' => 'مورد اختبار طباعة ملخص الصفحة الرئيسية',
            'phone' => '0579853101',
            'email' => uniqid('main-dashboard-print-supplier-') . '@example.com',
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
            'invoice_number' => uniqid('SI-MAIN-PRINT-'),
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
            'invoice_number' => uniqid('PI-MAIN-PRINT-'),
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
