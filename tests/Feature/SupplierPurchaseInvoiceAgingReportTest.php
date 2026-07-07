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

class SupplierPurchaseInvoiceAgingReportTest extends TestCase
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

    public function test_supplier_purchase_invoice_aging_report_displays_summary_and_table(): void
    {
        $user = User::query()->firstOrFail();

        PurchaseInvoice::query()->delete();

        $supplier = $this->createSupplier([
            'name' => 'مورد تقرير أعمار ذمم الموردين',
            'phone' => '0579851531',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PI-SUPPLIER-AGING-001',
            'remaining_amount' => 1500,
            'grand_total' => 1500,
            'subtotal' => 1500,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.index'));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-purchase-invoice-aging-report"', false);
        $response->assertSee('تقرير أعمار ذمم الموردين');
        $response->assertSee('data-testid="supplier-aging-summary"', false);
        $response->assertSee('عدد الموردين');
        $response->assertSee('عدد الفواتير المفتوحة');
        $response->assertSee('إجمالي الذمم المفتوحة');
        $response->assertSee('إجمالي المتأخر');
        $response->assertSee('data-testid="supplier-aging-table"', false);
        $response->assertSee('مورد تقرير أعمار ذمم الموردين');
        $response->assertSee('1,500.00 ريال');
        $response->assertSee('2026-05-20');
    }

    public function test_supplier_purchase_invoice_aging_report_respects_supplier_and_bucket_filters(): void
    {
        $user = User::query()->firstOrFail();

        PurchaseInvoice::query()->delete();

        $selectedSupplier = $this->createSupplier([
            'name' => 'مورد مطابق لفلاتر أعمار ذمم الموردين',
            'phone' => '0579851532',
        ]);

        $otherSupplier = $this->createSupplier([
            'name' => 'مورد مستبعد من فلاتر أعمار ذمم الموردين',
            'phone' => '0579851533',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $selectedSupplier->id,
            'invoice_number' => 'PI-SUPPLIER-AGING-IN',
            'remaining_amount' => 1500,
            'grand_total' => 1500,
            'subtotal' => 1500,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $selectedSupplier->id,
            'invoice_number' => 'PI-SUPPLIER-AGING-NOT-DUE-OUT',
            'remaining_amount' => 1000,
            'grand_total' => 1000,
            'subtotal' => 1000,
            'due_at' => '2026-07-20 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $otherSupplier->id,
            'invoice_number' => 'PI-SUPPLIER-AGING-SUPPLIER-OUT',
            'remaining_amount' => 2000,
            'grand_total' => 2000,
            'subtotal' => 2000,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.index', [
            'supplier_id' => $selectedSupplier->id,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();
        $response->assertSee('مورد مطابق لفلاتر أعمار ذمم الموردين #' . $selectedSupplier->id);
        $response->assertSee('متأخرة 31 إلى 60 يوم');
        $response->assertSee('مورد مطابق لفلاتر أعمار ذمم الموردين');
        $response->assertSee('1,500.00 ريال');
        $response->assertDontSee('مورد مستبعد من فلاتر أعمار ذمم الموردين');
        $response->assertDontSee('1,000.00 ريال');
        $response->assertDontSee('2,000.00 ريال');
    }

    public function test_supplier_purchase_invoice_aging_report_displays_empty_state(): void
    {
        $user = User::query()->firstOrFail();

        PurchaseInvoice::query()->delete();

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.index'));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-aging-empty"', false);
        $response->assertSee('لا توجد ذمم مفتوحة للموردين.');
        $response->assertSee('0.00 ريال');
    }

    public function test_reports_index_displays_supplier_purchase_invoice_aging_report_link(): void
    {
        if (! view()->exists('reports.index')) {
            $this->markTestSkipped('reports.index view does not exist.');
        }

        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-purchase-invoice-aging-report-link"', false);
        $response->assertSee('تقرير أعمار ذمم الموردين');
        $response->assertSee(route('reports.supplier-purchase-invoice-aging.index'), false);
    }

    private function createSupplier(array $overrides = []): Supplier
    {
        $columns = Schema::getColumnListing('suppliers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'مورد اختبار أعمار ذمم الموردين',
            'phone' => '0579851500',
            'email' => uniqid('supplier-aging-') . '@example.com',
            'city' => 'الرياض',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return Supplier::unguarded(fn () => Supplier::query()->create(array_intersect_key($data, array_flip($columns))));
    }

    private function validPurchaseInvoiceStatus(): string
    {
        $tableSql = DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'purchase_invoices'");
        $sql = $tableSql ? (string) $tableSql->sql : '';

        $patterns = [
            '/(?:^|,)\\s*["`]?status["`]?\\s+[^,]*?check\\s*\\(\\s*["`]?status["`]?\\s+in\\s*\\(([^\\)]+)\\)\\s*\\)/is',
            '/check\\s*\\(\\s*["`]?status["`]?\\s+in\\s*\\(([^\\)]+)\\)\\s*\\)/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $sql, $matches)) {
                preg_match_all('/[\'"]([^\'"]+)[\'"]/', $matches[1], $values);

                if (! empty($values[1][0])) {
                    return $values[1][0];
                }
            }
        }

        foreach (glob(database_path('migrations/*.php')) as $file) {
            $migration = file_get_contents($file);

            if (! str_contains($migration, 'purchase_invoices')) {
                continue;
            }

            if (preg_match('/enum\\s*\\(\\s*[\'"]status[\'"]\\s*,\\s*\\[([^\\]]+)\\]/is', $migration, $matches)) {
                preg_match_all('/[\'"]([^\'"]+)[\'"]/', $matches[1], $values);

                if (! empty($values[1][0])) {
                    return $values[1][0];
                }
            }
        }

        return 'draft';
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
            'invoice_number' => uniqid('PI-SUPPLIER-AGING-'),
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
}
