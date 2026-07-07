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

class SupplierPurchaseInvoiceAgingReportExportTest extends TestCase
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

    public function test_supplier_purchase_invoice_aging_report_displays_export_button_with_current_filters(): void
    {
        $user = User::query()->firstOrFail();

        $supplier = $this->createSupplier([
            'name' => 'مورد زر تصدير أعمار ذمم الموردين',
            'phone' => '0579851731',
        ]);

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.index', [
            'supplier_id' => $supplier->id,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-aging-export-button"', false);
        $response->assertSee(route('reports.supplier-purchase-invoice-aging.export'), false);
        $response->assertSee('supplier_id=' . $supplier->id, false);
        $response->assertSee('aging_bucket=overdue_31_60', false);
    }

    public function test_supplier_purchase_invoice_aging_report_export_contains_header_summary_and_table(): void
    {
        $user = User::query()->firstOrFail();

        PurchaseInvoice::query()->delete();

        $supplier = $this->createSupplier([
            'name' => 'مورد تصدير أعمار ذمم الموردين',
            'phone' => '0579851732',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PI-SUPPLIER-AGING-EXPORT-001',
            'remaining_amount' => 1500,
            'grand_total' => 1500,
            'subtotal' => 1500,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.export'));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('تقرير أعمار ذمم الموردين', $content);
        $this->assertStringContainsString('تاريخ إنشاء التقرير', $content);
        $this->assertStringContainsString('تاريخ التقرير', $content);
        $this->assertStringContainsString('2026-07-06', $content);
        $this->assertStringContainsString('فلتر المورد', $content);
        $this->assertStringContainsString('فلتر شريحة العمر', $content);
        $this->assertStringContainsString('ملخص عام', $content);
        $this->assertStringContainsString('عدد الموردين', $content);
        $this->assertStringContainsString('عدد الفواتير المفتوحة', $content);
        $this->assertStringContainsString('إجمالي الذمم المفتوحة', $content);
        $this->assertStringContainsString('إجمالي المتأخر', $content);
        $this->assertStringContainsString('المورد', $content);
        $this->assertStringContainsString('عدد الفواتير', $content);
        $this->assertStringContainsString('إجمالي المتبقي', $content);
        $this->assertStringContainsString('غير مستحقة بعد', $content);
        $this->assertStringContainsString('متأخرة 1 إلى 30', $content);
        $this->assertStringContainsString('متأخرة 31 إلى 60', $content);
        $this->assertStringContainsString('متأخرة 61 إلى 90', $content);
        $this->assertStringContainsString('أكثر من 90', $content);
        $this->assertStringContainsString('بدون تاريخ استحقاق', $content);
        $this->assertStringContainsString('أقدم استحقاق', $content);
        $this->assertStringContainsString('مورد تصدير أعمار ذمم الموردين', $content);
        $this->assertStringContainsString('1500.00', $content);
        $this->assertStringContainsString('2026-05-20', $content);
    }

    public function test_supplier_purchase_invoice_aging_report_export_respects_supplier_and_bucket_filters(): void
    {
        $user = User::query()->firstOrFail();

        PurchaseInvoice::query()->delete();

        $selectedSupplier = $this->createSupplier([
            'name' => 'مورد مطابق لفلاتر تصدير أعمار ذمم الموردين',
            'phone' => '0579851733',
        ]);

        $otherSupplier = $this->createSupplier([
            'name' => 'مورد مستبعد من فلاتر تصدير أعمار ذمم الموردين',
            'phone' => '0579851734',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $selectedSupplier->id,
            'invoice_number' => 'PI-SUPPLIER-AGING-EXPORT-IN',
            'remaining_amount' => 1500,
            'grand_total' => 1500,
            'subtotal' => 1500,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $selectedSupplier->id,
            'invoice_number' => 'PI-SUPPLIER-AGING-EXPORT-NOT-DUE-OUT',
            'remaining_amount' => 1000,
            'grand_total' => 1000,
            'subtotal' => 1000,
            'due_at' => '2026-07-20 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $otherSupplier->id,
            'invoice_number' => 'PI-SUPPLIER-AGING-EXPORT-SUPPLIER-OUT',
            'remaining_amount' => 2000,
            'grand_total' => 2000,
            'subtotal' => 2000,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.export', [
            'supplier_id' => $selectedSupplier->id,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('مورد مطابق لفلاتر تصدير أعمار ذمم الموردين #' . $selectedSupplier->id, $content);
        $this->assertStringContainsString('متأخرة 31 إلى 60 يوم', $content);
        $this->assertStringContainsString('مورد مطابق لفلاتر تصدير أعمار ذمم الموردين', $content);
        $this->assertStringContainsString('1500.00', $content);
        $this->assertStringNotContainsString('مورد مستبعد من فلاتر تصدير أعمار ذمم الموردين', $content);
        $this->assertStringNotContainsString('1000.00', $content);
        $this->assertStringNotContainsString('2000.00', $content);
    }

    private function createSupplier(array $overrides = []): Supplier
    {
        $columns = Schema::getColumnListing('suppliers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'مورد اختبار تصدير أعمار ذمم الموردين',
            'phone' => '0579851700',
            'email' => uniqid('supplier-aging-export-') . '@example.com',
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
            'invoice_number' => uniqid('PI-SUPPLIER-AGING-EXPORT-'),
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
