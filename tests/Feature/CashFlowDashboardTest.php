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

class CashFlowDashboardTest extends TestCase
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

    public function test_cash_flow_dashboard_page_loads(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.cash-flow-dashboard.index'));

        $response->assertOk();
        $response->assertSee('data-testid="cash-flow-dashboard"', false);
        $response->assertSee('لوحة التدفق النقدي المتوقع');
        $response->assertSee('تاريخ التقرير: 2026-07-06');
        $response->assertSee('data-testid="cash-flow-inflow-summary"', false);
        $response->assertSee('data-testid="cash-flow-outflow-summary"', false);
        $response->assertSee('data-testid="cash-flow-net-summary"', false);
        $response->assertSee('data-testid="cash-flow-risk-summary"', false);
        $response->assertSee('data-testid="cash-flow-bucket-comparison"', false);
        $response->assertSee('التدفقات الداخلة المتوقعة');
        $response->assertSee('التدفقات الخارجة المتوقعة');
        $response->assertSee('صافي التدفق النقدي المتوقع');
        $response->assertSee('صافي الضغط النقدي المتأخر');
        $response->assertSee('نسبة تغطية الالتزامات المتوقعة');
        $response->assertSee('التدفق النقدي حسب شرائح الأعمار');
        $response->assertSee('data-testid="cash-flow-customer-aging-link"', false);
        $response->assertSee('data-testid="cash-flow-supplier-aging-link"', false);
        $response->assertSee('data-testid="cash-flow-aging-dashboard-link"', false);
    }

    public function test_cash_flow_dashboard_displays_bucket_cash_flow_totals(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer([
            'name' => 'عميل شرائح التدفق النقدي',
            'phone' => '0579852231',
        ]);

        $supplier = $this->createSupplier([
            'name' => 'مورد شرائح التدفق النقدي',
            'phone' => '0579852232',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $customer->id,
            'invoice_number' => 'SI-CASH-FLOW-BUCKET-001',
            'remaining_amount' => 4500,
            'grand_total' => 4500,
            'subtotal' => 4500,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PI-CASH-FLOW-BUCKET-001',
            'remaining_amount' => 1700,
            'grand_total' => 1700,
            'subtotal' => 1700,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.cash-flow-dashboard.index'));

        $response->assertOk();
        $response->assertSee('data-testid="cash-flow-bucket-comparison"', false);
        $response->assertSee('متأخرة 31 إلى 60');
        $response->assertSee('4,500.00 ريال');
        $response->assertSee('1,700.00 ريال');
        $response->assertSee('2,800.00 ريال');
    }

    public function test_cash_flow_dashboard_displays_risk_cards(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer([
            'name' => 'عميل مخاطر التدفق النقدي',
            'phone' => '0579852233',
        ]);

        $supplier = $this->createSupplier([
            'name' => 'مورد مخاطر التدفق النقدي',
            'phone' => '0579852234',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $customer->id,
            'invoice_number' => 'SI-CASH-FLOW-RISK-001',
            'remaining_amount' => 3000,
            'grand_total' => 3000,
            'subtotal' => 3000,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PI-CASH-FLOW-RISK-001',
            'remaining_amount' => 5000,
            'grand_total' => 5000,
            'subtotal' => 5000,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.cash-flow-dashboard.index'));

        $response->assertOk();
        $response->assertSee('data-testid="cash-flow-risk-summary"', false);
        $response->assertSee('إجمالي التدفقات الداخلة المتأخرة');
        $response->assertSee('إجمالي التدفقات الخارجة المتأخرة');
        $response->assertSee('صافي الضغط النقدي المتأخر');
        $response->assertSee('ضغط نقدي متأخر على الشركة');
        $response->assertSee('نسبة تغطية الالتزامات المتوقعة');
        $response->assertSee('تغطية نقدية متوقعة غير كافية');
        $response->assertSee('3,000.00 ريال');
        $response->assertSee('5,000.00 ريال');
        $response->assertSee('2,000.00 ريال');
        $response->assertSee('60.00%');
    }

    public function test_reports_index_displays_cash_flow_dashboard_link(): void
    {
        if (! view()->exists('reports.index')) {
            $this->markTestSkipped('reports.index view does not exist.');
        }

        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('data-testid="cash-flow-dashboard-link"', false);
        $response->assertSee('لوحة التدفق النقدي المتوقع');
        $response->assertSee(route('reports.cash-flow-dashboard.index'), false);
    }

    private function createCustomer(array $overrides = []): Customer
    {
        $columns = Schema::getColumnListing('customers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'عميل اختبار لوحة التدفق النقدي',
            'phone' => '0579852200',
            'email' => uniqid('cash-flow-customer-') . '@example.com',
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
            'name' => 'مورد اختبار لوحة التدفق النقدي',
            'phone' => '0579852201',
            'email' => uniqid('cash-flow-supplier-') . '@example.com',
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
            'invoice_number' => uniqid('SI-CASH-FLOW-'),
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
            'invoice_number' => uniqid('PI-CASH-FLOW-'),
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
