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

class AgingDrilldownExportTest extends TestCase
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

    public function test_customer_drilldown_displays_export_link_with_filters(): void
    {
        $user = User::query()->firstOrFail();

        $customer = $this->createCustomer([
            'name' => 'عميل رابط تصدير التفاصيل',
            'phone' => '0579852731',
        ]);

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.drilldown', [
            'customer_id' => $customer->id,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="customer-aging-drilldown-export-link"', false);
        $response->assertSee(route('reports.customer-sales-invoice-aging.drilldown.export'), false);
        $response->assertSee('customer_id=' . $customer->id, false);
        $response->assertSee('aging_bucket=overdue_31_60', false);
    }

    public function test_supplier_drilldown_displays_export_link_with_filters(): void
    {
        $user = User::query()->firstOrFail();

        $supplier = $this->createSupplier([
            'name' => 'مورد رابط تصدير التفاصيل',
            'phone' => '0579852732',
        ]);

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.drilldown', [
            'supplier_id' => $supplier->id,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-aging-drilldown-export-link"', false);
        $response->assertSee(route('reports.supplier-purchase-invoice-aging.drilldown.export'), false);
        $response->assertSee('supplier_id=' . $supplier->id, false);
        $response->assertSee('aging_bucket=overdue_31_60', false);
    }

    public function test_customer_drilldown_export_respects_filters(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $selectedCustomer = $this->createCustomer([
            'name' => 'عميل تصدير مطابق للتفاصيل',
            'phone' => '0579852733',
        ]);

        $otherCustomer = $this->createCustomer([
            'name' => 'عميل تصدير مستبعد من التفاصيل',
            'phone' => '0579852734',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $selectedCustomer->id,
            'invoice_number' => 'SI-CUSTOMER-DRILLDOWN-EXPORT-IN',
            'remaining_amount' => 1500,
            'grand_total' => 1500,
            'subtotal' => 1500,
            'paid_amount' => 0,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $selectedCustomer->id,
            'invoice_number' => 'SI-CUSTOMER-DRILLDOWN-EXPORT-NOT-DUE-OUT',
            'remaining_amount' => 1000,
            'grand_total' => 1000,
            'subtotal' => 1000,
            'paid_amount' => 0,
            'due_at' => '2026-07-20 09:00:00',
        ]);

        $this->createSalesInvoice([
            'customer_id' => $otherCustomer->id,
            'invoice_number' => 'SI-CUSTOMER-DRILLDOWN-EXPORT-CUSTOMER-OUT',
            'remaining_amount' => 2000,
            'grand_total' => 2000,
            'subtotal' => 2000,
            'paid_amount' => 0,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.drilldown.export', [
            'customer_id' => $selectedCustomer->id,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('تفاصيل فواتير العملاء المفتوحة', $content);
        $this->assertStringContainsString('عميل تصدير مطابق للتفاصيل #' . $selectedCustomer->id, $content);
        $this->assertStringContainsString('متأخرة 31 إلى 60 يوم', $content);
        $this->assertStringContainsString('SI-CUSTOMER-DRILLDOWN-EXPORT-IN', $content);
        $this->assertStringContainsString('1500.00', $content);
        $this->assertStringContainsString('2026-05-20', $content);
        $this->assertStringNotContainsString('SI-CUSTOMER-DRILLDOWN-EXPORT-NOT-DUE-OUT', $content);
        $this->assertStringNotContainsString('SI-CUSTOMER-DRILLDOWN-EXPORT-CUSTOMER-OUT', $content);
        $this->assertStringNotContainsString('1000.00', $content);
        $this->assertStringNotContainsString('2000.00', $content);
    }

    public function test_supplier_drilldown_export_respects_filters(): void
    {
        $user = User::query()->firstOrFail();

        PurchaseInvoice::query()->delete();

        $selectedSupplier = $this->createSupplier([
            'name' => 'مورد تصدير مطابق للتفاصيل',
            'phone' => '0579852735',
        ]);

        $otherSupplier = $this->createSupplier([
            'name' => 'مورد تصدير مستبعد من التفاصيل',
            'phone' => '0579852736',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $selectedSupplier->id,
            'invoice_number' => 'PI-SUPPLIER-DRILLDOWN-EXPORT-IN',
            'remaining_amount' => 1500,
            'grand_total' => 1500,
            'subtotal' => 1500,
            'paid_amount' => 0,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $selectedSupplier->id,
            'invoice_number' => 'PI-SUPPLIER-DRILLDOWN-EXPORT-NOT-DUE-OUT',
            'remaining_amount' => 1000,
            'grand_total' => 1000,
            'subtotal' => 1000,
            'paid_amount' => 0,
            'due_at' => '2026-07-20 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $otherSupplier->id,
            'invoice_number' => 'PI-SUPPLIER-DRILLDOWN-EXPORT-SUPPLIER-OUT',
            'remaining_amount' => 2000,
            'grand_total' => 2000,
            'subtotal' => 2000,
            'paid_amount' => 0,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.drilldown.export', [
            'supplier_id' => $selectedSupplier->id,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('تفاصيل فواتير الموردين المفتوحة', $content);
        $this->assertStringContainsString('مورد تصدير مطابق للتفاصيل #' . $selectedSupplier->id, $content);
        $this->assertStringContainsString('متأخرة 31 إلى 60 يوم', $content);
        $this->assertStringContainsString('PI-SUPPLIER-DRILLDOWN-EXPORT-IN', $content);
        $this->assertStringContainsString('1500.00', $content);
        $this->assertStringContainsString('2026-05-20', $content);
        $this->assertStringNotContainsString('PI-SUPPLIER-DRILLDOWN-EXPORT-NOT-DUE-OUT', $content);
        $this->assertStringNotContainsString('PI-SUPPLIER-DRILLDOWN-EXPORT-SUPPLIER-OUT', $content);
        $this->assertStringNotContainsString('1000.00', $content);
        $this->assertStringNotContainsString('2000.00', $content);
    }

    private function createCustomer(array $overrides = []): Customer
    {
        $columns = Schema::getColumnListing('customers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'عميل اختبار تصدير تفاصيل أعمار الذمم',
            'phone' => '0579852700',
            'email' => uniqid('customer-aging-drilldown-export-') . '@example.com',
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
            'name' => 'مورد اختبار تصدير تفاصيل أعمار الذمم',
            'phone' => '0579852701',
            'email' => uniqid('supplier-aging-drilldown-export-') . '@example.com',
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
            'invoice_number' => uniqid('SI-CUSTOMER-DRILLDOWN-EXPORT-'),
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
            'invoice_number' => uniqid('PI-SUPPLIER-DRILLDOWN-EXPORT-'),
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
