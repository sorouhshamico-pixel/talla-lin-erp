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

class AgingDrilldownBranchFilterTest extends TestCase
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

    public function test_customer_drilldown_respects_branch_filter(): void
    {
        [$firstBranchId, $secondBranchId] = $this->branchIds();

        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $customer = $this->createCustomer(['name' => 'عميل تفاصيل حسب الفرع']);

        $this->createSalesInvoice([
            'branch_id' => $firstBranchId,
            'customer_id' => $customer->id,
            'invoice_number' => 'SI-CUSTOMER-BRANCH-IN',
            'remaining_amount' => 1500,
            'grand_total' => 1500,
            'subtotal' => 1500,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createSalesInvoice([
            'branch_id' => $secondBranchId,
            'customer_id' => $customer->id,
            'invoice_number' => 'SI-CUSTOMER-BRANCH-OUT',
            'remaining_amount' => 9000,
            'grand_total' => 9000,
            'subtotal' => 9000,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.drilldown', [
            'branch_id' => $firstBranchId,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="customer-aging-drilldown-branch-select"', false);
        $response->assertSee('data-testid="customer-aging-drilldown-branch-filter"', false);
        $response->assertSee('فلتر الفرع:');
        $response->assertSee('#' . $firstBranchId);
        $response->assertSee('SI-CUSTOMER-BRANCH-IN');
        $response->assertSee('1,500.00 ريال');
        $response->assertDontSee('SI-CUSTOMER-BRANCH-OUT');
        $response->assertDontSee('9,000.00 ريال');
        $response->assertSee('branch_id=' . $firstBranchId, false);
    }

    public function test_supplier_drilldown_respects_branch_filter(): void
    {
        [$firstBranchId, $secondBranchId] = $this->branchIds();

        $user = User::query()->firstOrFail();

        PurchaseInvoice::query()->delete();

        $supplier = $this->createSupplier(['name' => 'مورد تفاصيل حسب الفرع']);

        $this->createPurchaseInvoice([
            'branch_id' => $firstBranchId,
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PI-SUPPLIER-BRANCH-IN',
            'remaining_amount' => 2500,
            'grand_total' => 2500,
            'subtotal' => 2500,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'branch_id' => $secondBranchId,
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PI-SUPPLIER-BRANCH-OUT',
            'remaining_amount' => 8000,
            'grand_total' => 8000,
            'subtotal' => 8000,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.drilldown', [
            'branch_id' => $firstBranchId,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-aging-drilldown-branch-select"', false);
        $response->assertSee('data-testid="supplier-aging-drilldown-branch-filter"', false);
        $response->assertSee('فلتر الفرع:');
        $response->assertSee('#' . $firstBranchId);
        $response->assertSee('PI-SUPPLIER-BRANCH-IN');
        $response->assertSee('2,500.00 ريال');
        $response->assertDontSee('PI-SUPPLIER-BRANCH-OUT');
        $response->assertDontSee('8,000.00 ريال');
        $response->assertSee('branch_id=' . $firstBranchId, false);
    }

    public function test_customer_and_supplier_drilldown_exports_include_branch_context(): void
    {
        [$firstBranchId, $secondBranchId] = $this->branchIds();

        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer(['name' => 'عميل تصدير تفاصيل حسب الفرع']);
        $supplier = $this->createSupplier(['name' => 'مورد تصدير تفاصيل حسب الفرع']);

        $this->createSalesInvoice([
            'branch_id' => $firstBranchId,
            'customer_id' => $customer->id,
            'invoice_number' => 'SI-CUSTOMER-BRANCH-EXPORT-IN',
            'remaining_amount' => 1500,
            'grand_total' => 1500,
            'subtotal' => 1500,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createSalesInvoice([
            'branch_id' => $secondBranchId,
            'customer_id' => $customer->id,
            'invoice_number' => 'SI-CUSTOMER-BRANCH-EXPORT-OUT',
            'remaining_amount' => 9000,
            'grand_total' => 9000,
            'subtotal' => 9000,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'branch_id' => $firstBranchId,
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PI-SUPPLIER-BRANCH-EXPORT-IN',
            'remaining_amount' => 2500,
            'grand_total' => 2500,
            'subtotal' => 2500,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'branch_id' => $secondBranchId,
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PI-SUPPLIER-BRANCH-EXPORT-OUT',
            'remaining_amount' => 8000,
            'grand_total' => 8000,
            'subtotal' => 8000,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $customerResponse = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.drilldown.export', [
            'branch_id' => $firstBranchId,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $customerResponse->assertOk();

        $customerContent = $customerResponse->streamedContent();

        $this->assertStringContainsString('فلتر الفرع', $customerContent);
        $this->assertStringContainsString('#' . $firstBranchId, $customerContent);
        $this->assertStringContainsString('SI-CUSTOMER-BRANCH-EXPORT-IN', $customerContent);
        $this->assertStringContainsString('1500.00', $customerContent);
        $this->assertStringNotContainsString('SI-CUSTOMER-BRANCH-EXPORT-OUT', $customerContent);
        $this->assertStringNotContainsString('9000.00', $customerContent);

        $supplierResponse = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.drilldown.export', [
            'branch_id' => $firstBranchId,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $supplierResponse->assertOk();

        $supplierContent = $supplierResponse->streamedContent();

        $this->assertStringContainsString('فلتر الفرع', $supplierContent);
        $this->assertStringContainsString('#' . $firstBranchId, $supplierContent);
        $this->assertStringContainsString('PI-SUPPLIER-BRANCH-EXPORT-IN', $supplierContent);
        $this->assertStringContainsString('2500.00', $supplierContent);
        $this->assertStringNotContainsString('PI-SUPPLIER-BRANCH-EXPORT-OUT', $supplierContent);
        $this->assertStringNotContainsString('8000.00', $supplierContent);
    }

    private function branchIds(): array
    {
        $ids = DB::table('branches')->orderBy('id')->pluck('id')->all();

        while (count($ids) < 2) {
            $columns = Schema::getColumnListing('branches');

            $data = [
                'company_id' => (int) DB::table('companies')->value('id'),
                'name' => 'فرع اختبار تفاصيل الأعمار ' . (count($ids) + 1),
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
            'name' => 'عميل اختبار تفاصيل أعمار حسب الفرع',
            'phone' => '0579853800',
            'email' => uniqid('aging-drilldown-branch-customer-') . '@example.com',
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
            'name' => 'مورد اختبار تفاصيل أعمار حسب الفرع',
            'phone' => '0579853801',
            'email' => uniqid('aging-drilldown-branch-supplier-') . '@example.com',
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
            'invoice_number' => uniqid('SI-AGING-DRILLDOWN-BRANCH-'),
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
            'invoice_number' => uniqid('PI-AGING-DRILLDOWN-BRANCH-'),
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
