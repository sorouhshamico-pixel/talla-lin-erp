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

class CashFlowDashboardDateRangeFilterTest extends TestCase
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

    public function test_cash_flow_dashboard_displays_branch_and_date_range_filters(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.cash-flow-dashboard.index'));

        $response->assertOk();
        $response->assertSee('data-testid="cash-flow-dashboard-filters"', false);
        $response->assertSee('data-testid="cash-flow-branch-select"', false);
        $response->assertSee('data-testid="cash-flow-date-from-input"', false);
        $response->assertSee('data-testid="cash-flow-date-to-input"', false);
        $response->assertSee('data-testid="cash-flow-apply-filters"', false);
        $response->assertSee('data-testid="cash-flow-reset-filters"', false);
    }

    public function test_cash_flow_dashboard_filters_by_due_date_range(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer(['name' => 'عميل فلتر فترة التدفق']);
        $supplier = $this->createSupplier(['name' => 'مورد فلتر فترة التدفق']);

        $this->createSalesInvoice([
            'customer_id' => $customer->id,
            'invoice_number' => 'SI-CASH-FLOW-DATE-IN',
            'remaining_amount' => 3000,
            'grand_total' => 3000,
            'subtotal' => 3000,
            'due_at' => '2026-07-10 09:00:00',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $customer->id,
            'invoice_number' => 'SI-CASH-FLOW-DATE-OUT',
            'remaining_amount' => 9000,
            'grand_total' => 9000,
            'subtotal' => 9000,
            'due_at' => '2026-08-10 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PI-CASH-FLOW-DATE-IN',
            'remaining_amount' => 1000,
            'grand_total' => 1000,
            'subtotal' => 1000,
            'due_at' => '2026-07-15 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PI-CASH-FLOW-DATE-OUT',
            'remaining_amount' => 7000,
            'grand_total' => 7000,
            'subtotal' => 7000,
            'due_at' => '2026-08-15 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.cash-flow-dashboard.index', [
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ]));

        $response->assertOk();
        $response->assertSee('value="2026-07-01"', false);
        $response->assertSee('value="2026-07-31"', false);
        $response->assertSee('تاريخ التقرير: 2026-07-31');
        $response->assertSee('3,000.00 ريال');
        $response->assertSee('1,000.00 ريال');
        $response->assertSee('2,000.00 ريال');
        $response->assertDontSee('9,000.00 ريال');
        $response->assertDontSee('7,000.00 ريال');
    }

    public function test_cash_flow_dashboard_filters_by_branch_and_preserves_filters_on_actions(): void
    {
        $user = User::query()->firstOrFail();

        [$firstBranchId, $secondBranchId] = $this->branchIds();

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer(['name' => 'عميل فلتر فرع التدفق']);
        $supplier = $this->createSupplier(['name' => 'مورد فلتر فرع التدفق']);

        $this->createSalesInvoice([
            'branch_id' => $firstBranchId,
            'customer_id' => $customer->id,
            'remaining_amount' => 3000,
            'grand_total' => 3000,
            'subtotal' => 3000,
            'due_at' => '2026-07-10 09:00:00',
        ]);

        $this->createSalesInvoice([
            'branch_id' => $secondBranchId,
            'customer_id' => $customer->id,
            'remaining_amount' => 9000,
            'grand_total' => 9000,
            'subtotal' => 9000,
            'due_at' => '2026-07-10 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'branch_id' => $firstBranchId,
            'supplier_id' => $supplier->id,
            'remaining_amount' => 1000,
            'grand_total' => 1000,
            'subtotal' => 1000,
            'due_at' => '2026-07-15 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'branch_id' => $secondBranchId,
            'supplier_id' => $supplier->id,
            'remaining_amount' => 7000,
            'grand_total' => 7000,
            'subtotal' => 7000,
            'due_at' => '2026-07-15 09:00:00',
        ]);

        $params = [
            'branch_id' => $firstBranchId,
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ];

        $response = $this->actingAs($user)->get(route('reports.cash-flow-dashboard.index', $params));

        $response->assertOk();
        $response->assertSee('3,000.00 ريال');
        $response->assertSee('1,000.00 ريال');
        $response->assertDontSee('9,000.00 ريال');
        $response->assertDontSee('7,000.00 ريال');

        $response->assertSee(e(route('reports.cash-flow-dashboard.export', $params)), false);
        $response->assertSee(e(route('reports.cash-flow-dashboard.print', $params)), false);
        $response->assertSee('branch_id=' . $firstBranchId, false);
        $response->assertSee('as_of_date=2026-07-31', false);
    }

    private function branchIds(): array
    {
        $ids = DB::table('branches')->orderBy('id')->pluck('id')->all();

        while (count($ids) < 2) {
            $columns = Schema::getColumnListing('branches');

            $data = [
                'company_id' => (int) DB::table('companies')->value('id'),
                'name' => 'فرع اختبار فلاتر التدفق ' . (count($ids) + 1),
                'code' => 'BR-' . uniqid(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $ids[] = DB::table('branches')->insertGetId(array_intersect_key($data, array_flip($columns)));
        }

        return array_slice($ids, 0, 2);
    }

    private function createCustomer(array $overrides = []): Customer
    {
        $columns = Schema::getColumnListing('customers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'عميل اختبار فلاتر التدفق',
            'phone' => '0579854300',
            'email' => uniqid('cash-flow-filter-customer-') . '@example.com',
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
            'name' => 'مورد اختبار فلاتر التدفق',
            'phone' => '0579854301',
            'email' => uniqid('cash-flow-filter-supplier-') . '@example.com',
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
            'invoice_number' => uniqid('SI-CASH-FLOW-FILTER-'),
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
            'invoice_number' => uniqid('PI-CASH-FLOW-FILTER-'),
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
