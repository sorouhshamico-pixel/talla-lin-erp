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

class CashFlowDashboardFilterContextTest extends TestCase
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

    public function test_cash_flow_print_view_displays_filter_context_and_filtered_totals(): void
    {
        $user = User::query()->firstOrFail();

        [$firstBranchId, $secondBranchId] = $this->branchIds();
        $firstBranchName = DB::table('branches')->where('id', $firstBranchId)->value('name');

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer();
        $supplier = $this->createSupplier();

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

        $response = $this->actingAs($user)->get(route('reports.cash-flow-dashboard.print', [
            'branch_id' => $firstBranchId,
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="cash-flow-print-filter-context"', false);
        $response->assertSee($firstBranchName);
        $response->assertSee('2026-07-01');
        $response->assertSee('2026-07-31');
        $response->assertSee('3,000.00');
        $response->assertSee('1,000.00');
        $response->assertSee('2,000.00');
        $response->assertDontSee('9,000.00');
        $response->assertDontSee('7,000.00');
    }

    public function test_cash_flow_export_contains_filter_context_and_filtered_totals(): void
    {
        $user = User::query()->firstOrFail();

        [$firstBranchId, $secondBranchId] = $this->branchIds();
        $firstBranchName = DB::table('branches')->where('id', $firstBranchId)->value('name');

        SalesInvoice::query()->delete();
        PurchaseInvoice::query()->delete();

        $customer = $this->createCustomer();
        $supplier = $this->createSupplier();

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

        $response = $this->actingAs($user)->get(route('reports.cash-flow-dashboard.export', [
            'branch_id' => $firstBranchId,
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('الفرع', $content);
        $this->assertStringContainsString($firstBranchName, $content);
        $this->assertStringContainsString('من تاريخ الاستحقاق', $content);
        $this->assertStringContainsString('2026-07-01', $content);
        $this->assertStringContainsString('إلى تاريخ الاستحقاق', $content);
        $this->assertStringContainsString('2026-07-31', $content);
        $this->assertStringContainsString('3000.00', $content);
        $this->assertStringContainsString('1000.00', $content);
        $this->assertStringContainsString('2000.00', $content);
        $this->assertStringNotContainsString('9000.00', $content);
        $this->assertStringNotContainsString('7000.00', $content);
    }

    private function branchIds(): array
    {
        $ids = DB::table('branches')->orderBy('id')->pluck('id')->all();

        while (count($ids) < 2) {
            $columns = Schema::getColumnListing('branches');

            $data = [
                'company_id' => (int) DB::table('companies')->value('id'),
                'name' => 'فرع سياق التدفق ' . (count($ids) + 1),
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
            'name' => 'عميل سياق التدفق النقدي',
            'phone' => '0579854310',
            'email' => uniqid('cash-flow-context-customer-') . '@example.com',
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
            'name' => 'مورد سياق التدفق النقدي',
            'phone' => '0579854311',
            'email' => uniqid('cash-flow-context-supplier-') . '@example.com',
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
            'invoice_number' => uniqid('SI-CASH-FLOW-CONTEXT-'),
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
            'invoice_number' => uniqid('PI-CASH-FLOW-CONTEXT-'),
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
