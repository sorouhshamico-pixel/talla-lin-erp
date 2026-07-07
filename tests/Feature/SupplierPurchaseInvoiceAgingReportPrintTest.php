<?php

namespace Tests\Feature;

use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SupplierPurchaseInvoiceAgingReportPrintTest extends TestCase
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

    public function test_supplier_purchase_invoice_aging_report_displays_print_link_with_current_filters(): void
    {
        $user = User::query()->firstOrFail();

        $supplier = $this->createSupplier([
            'name' => 'مورد زر طباعة أعمار ذمم الموردين',
            'phone' => '0579851831',
        ]);

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.index', [
            'supplier_id' => $supplier->id,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-aging-print-link"', false);
        $response->assertSee(route('reports.supplier-purchase-invoice-aging.print'), false);
        $response->assertSee('supplier_id=' . $supplier->id, false);
        $response->assertSee('aging_bucket=overdue_31_60', false);
    }

    public function test_supplier_purchase_invoice_aging_report_print_contains_summary_and_table(): void
    {
        $user = User::query()->firstOrFail();

        PurchaseInvoice::query()->delete();

        $supplier = $this->createSupplier([
            'name' => 'مورد طباعة أعمار ذمم الموردين',
            'phone' => '0579851832',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PI-SUPPLIER-AGING-PRINT-001',
            'remaining_amount' => 1500,
            'grand_total' => 1500,
            'subtotal' => 1500,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.print'));

        $response->assertOk();
        $response->assertSee('تقرير أعمار ذمم الموردين');
        $response->assertSee('تاريخ التقرير: 2026-07-06');
        $response->assertSee('data-testid="supplier-aging-print-summary"', false);
        $response->assertSee('عدد الموردين');
        $response->assertSee('عدد الفواتير المفتوحة');
        $response->assertSee('إجمالي الذمم المفتوحة');
        $response->assertSee('إجمالي المتأخر');
        $response->assertSee('data-testid="supplier-aging-print-table"', false);
        $response->assertSee('مورد طباعة أعمار ذمم الموردين');
        $response->assertSee('1,500.00');
        $response->assertSee('2026-05-20');
    }

    public function test_supplier_purchase_invoice_aging_report_print_respects_supplier_and_bucket_filters(): void
    {
        $user = User::query()->firstOrFail();

        PurchaseInvoice::query()->delete();

        $selectedSupplier = $this->createSupplier([
            'name' => 'مورد مطابق لفلاتر طباعة أعمار ذمم الموردين',
            'phone' => '0579851833',
        ]);

        $otherSupplier = $this->createSupplier([
            'name' => 'مورد مستبعد من فلاتر طباعة أعمار ذمم الموردين',
            'phone' => '0579851834',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $selectedSupplier->id,
            'invoice_number' => 'PI-SUPPLIER-AGING-PRINT-IN',
            'remaining_amount' => 1500,
            'grand_total' => 1500,
            'subtotal' => 1500,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $selectedSupplier->id,
            'invoice_number' => 'PI-SUPPLIER-AGING-PRINT-NOT-DUE-OUT',
            'remaining_amount' => 1000,
            'grand_total' => 1000,
            'subtotal' => 1000,
            'due_at' => '2026-07-20 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $otherSupplier->id,
            'invoice_number' => 'PI-SUPPLIER-AGING-PRINT-SUPPLIER-OUT',
            'remaining_amount' => 2000,
            'grand_total' => 2000,
            'subtotal' => 2000,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.print', [
            'supplier_id' => $selectedSupplier->id,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();
        $response->assertSee('مورد مطابق لفلاتر طباعة أعمار ذمم الموردين #' . $selectedSupplier->id);
        $response->assertSee('متأخرة 31 إلى 60 يوم');
        $response->assertSee('مورد مطابق لفلاتر طباعة أعمار ذمم الموردين');
        $response->assertSee('1,500.00');
        $response->assertDontSee('مورد مستبعد من فلاتر طباعة أعمار ذمم الموردين');
        $response->assertDontSee('1,000.00');
        $response->assertDontSee('2,000.00');
    }

    public function test_supplier_purchase_invoice_aging_report_print_displays_empty_state(): void
    {
        $user = User::query()->firstOrFail();

        PurchaseInvoice::query()->delete();

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.print'));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-aging-print-empty"', false);
        $response->assertSee('لا توجد ذمم مفتوحة للموردين.');
        $response->assertSee('0.00');
    }

    private function createSupplier(array $overrides = []): Supplier
    {
        $columns = Schema::getColumnListing('suppliers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'مورد اختبار طباعة أعمار ذمم الموردين',
            'phone' => '0579851800',
            'email' => uniqid('supplier-aging-print-') . '@example.com',
            'city' => 'الرياض',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return Supplier::unguarded(fn () => Supplier::query()->create(array_intersect_key($data, array_flip($columns))));
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
            'invoice_number' => uniqid('PI-SUPPLIER-AGING-PRINT-'),
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
