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

class MainDashboardBranchContextExportPrintTest extends TestCase
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

    public function test_financial_summary_export_includes_branch_context_and_respects_branch_filter(): void
    {
        [$firstBranchId, $secondBranchId] = $this->branchIds();

        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer(['name' => 'عميل تصدير حسب الفرع']);
        $supplier = $this->createSupplier(['name' => 'مورد تصدير حسب الفرع']);

        $this->createSalesInvoice([
            'branch_id' => $firstBranchId,
            'customer_id' => $customer->id,
            'remaining_amount' => 5000,
            'grand_total' => 5000,
            'subtotal' => 5000,
            'due_at' => '2026-05-01 09:00:00',
        ]);

        $this->createSalesInvoice([
            'branch_id' => $secondBranchId,
            'customer_id' => $customer->id,
            'remaining_amount' => 9000,
            'grand_total' => 9000,
            'subtotal' => 9000,
            'due_at' => '2026-05-01 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'branch_id' => $firstBranchId,
            'supplier_id' => $supplier->id,
            'remaining_amount' => 3000,
            'grand_total' => 3000,
            'subtotal' => 3000,
            'due_at' => '2026-05-01 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.financial-summary.export', [
            'branch_id' => $firstBranchId,
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('فلتر الفرع', $content);
        $this->assertStringContainsString('#' . $firstBranchId, $content);
        $this->assertStringContainsString('5000.00', $content);
        $this->assertStringContainsString('3000.00', $content);
        $this->assertStringContainsString('2000.00', $content);
        $this->assertStringNotContainsString('9000.00', $content);
    }

    public function test_financial_summary_print_includes_branch_context_and_respects_branch_filter(): void
    {
        [$firstBranchId, $secondBranchId] = $this->branchIds();

        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer(['name' => 'عميل طباعة حسب الفرع']);

        $this->createSalesInvoice([
            'branch_id' => $firstBranchId,
            'customer_id' => $customer->id,
            'remaining_amount' => 5000,
            'grand_total' => 5000,
            'subtotal' => 5000,
            'due_at' => '2026-05-01 09:00:00',
        ]);

        $this->createSalesInvoice([
            'branch_id' => $secondBranchId,
            'customer_id' => $customer->id,
            'remaining_amount' => 9000,
            'grand_total' => 9000,
            'subtotal' => 9000,
            'due_at' => '2026-05-01 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.financial-summary.print', [
            'branch_id' => $firstBranchId,
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="main-dashboard-financial-print-branch-label"', false);
        $response->assertSee('فلتر الفرع:');
        $response->assertSee('#' . $firstBranchId);
        $response->assertSee('5,000.00 ريال');
        $response->assertDontSee('9,000.00 ريال');
    }

    public function test_top_overdue_exports_include_branch_context_and_respect_branch_filter(): void
    {
        [$firstBranchId, $secondBranchId] = $this->branchIds();

        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer(['name' => 'عميل متأخر فرع محدد']);
        $supplier = $this->createSupplier(['name' => 'مورد متأخر فرع محدد']);

        $this->createSalesInvoice([
            'branch_id' => $firstBranchId,
            'customer_id' => $customer->id,
            'remaining_amount' => 5000,
            'grand_total' => 5000,
            'subtotal' => 5000,
            'due_at' => '2026-05-01 09:00:00',
        ]);

        $this->createSalesInvoice([
            'branch_id' => $secondBranchId,
            'customer_id' => $customer->id,
            'remaining_amount' => 9000,
            'grand_total' => 9000,
            'subtotal' => 9000,
            'due_at' => '2026-05-01 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'branch_id' => $firstBranchId,
            'supplier_id' => $supplier->id,
            'remaining_amount' => 7000,
            'grand_total' => 7000,
            'subtotal' => 7000,
            'due_at' => '2026-05-01 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'branch_id' => $secondBranchId,
            'supplier_id' => $supplier->id,
            'remaining_amount' => 12000,
            'grand_total' => 12000,
            'subtotal' => 12000,
            'due_at' => '2026-05-01 09:00:00',
        ]);

        $customerResponse = $this->actingAs($user)->get(route('dashboard.top-overdue-customers.export', [
            'branch_id' => $firstBranchId,
        ]));

        $customerResponse->assertOk();

        $customerContent = $customerResponse->streamedContent();

        $this->assertStringContainsString('فلتر الفرع', $customerContent);
        $this->assertStringContainsString('#' . $firstBranchId, $customerContent);
        $this->assertStringContainsString('عميل متأخر فرع محدد', $customerContent);
        $this->assertStringContainsString('5000.00', $customerContent);
        $this->assertStringNotContainsString('9000.00', $customerContent);

        $supplierResponse = $this->actingAs($user)->get(route('dashboard.top-overdue-suppliers.export', [
            'branch_id' => $firstBranchId,
        ]));

        $supplierResponse->assertOk();

        $supplierContent = $supplierResponse->streamedContent();

        $this->assertStringContainsString('فلتر الفرع', $supplierContent);
        $this->assertStringContainsString('#' . $firstBranchId, $supplierContent);
        $this->assertStringContainsString('مورد متأخر فرع محدد', $supplierContent);
        $this->assertStringContainsString('7000.00', $supplierContent);
        $this->assertStringNotContainsString('12000.00', $supplierContent);
    }

    public function test_top_overdue_print_includes_branch_context_and_respects_branch_filter(): void
    {
        [$firstBranchId, $secondBranchId] = $this->branchIds();

        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer(['name' => 'عميل طباعة متأخر فرع محدد']);

        $this->createSalesInvoice([
            'branch_id' => $firstBranchId,
            'customer_id' => $customer->id,
            'remaining_amount' => 5000,
            'grand_total' => 5000,
            'subtotal' => 5000,
            'due_at' => '2026-05-01 09:00:00',
        ]);

        $this->createSalesInvoice([
            'branch_id' => $secondBranchId,
            'customer_id' => $customer->id,
            'remaining_amount' => 9000,
            'grand_total' => 9000,
            'subtotal' => 9000,
            'due_at' => '2026-05-01 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.top-overdue.print', [
            'branch_id' => $firstBranchId,
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="main-dashboard-top-overdue-print-branch-label"', false);
        $response->assertSee('فلتر الفرع:');
        $response->assertSee('#' . $firstBranchId);
        $response->assertSee('عميل طباعة متأخر فرع محدد');
        $response->assertSee('5,000.00 ريال');
        $response->assertDontSee('9,000.00 ريال');
    }

    private function branchIds(): array
    {
        $ids = DB::table('branches')->orderBy('id')->pluck('id')->all();

        while (count($ids) < 2) {
            $columns = Schema::getColumnListing('branches');

            $data = [
                'company_id' => (int) DB::table('companies')->value('id'),
                'name' => 'فرع اختبار سياق التصدير والطباعة ' . (count($ids) + 1),
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
            'name' => 'عميل اختبار سياق الفرع',
            'phone' => '0579853700',
            'email' => uniqid('branch-context-customer-') . '@example.com',
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
            'name' => 'مورد اختبار سياق الفرع',
            'phone' => '0579853701',
            'email' => uniqid('branch-context-supplier-') . '@example.com',
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
            'invoice_number' => uniqid('SI-BRANCH-CONTEXT-'),
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
            'invoice_number' => uniqid('PI-BRANCH-CONTEXT-'),
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
