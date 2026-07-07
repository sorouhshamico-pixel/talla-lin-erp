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

class SupplierPurchaseInvoiceAgingReportFiltersTest extends TestCase
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

    public function test_supplier_purchase_invoice_aging_report_displays_filter_controls(): void
    {
        $user = User::query()->firstOrFail();

        $supplier = $this->createSupplier([
            'name' => 'مورد فلتر أعمار ذمم الموردين',
            'phone' => '0579851631',
        ]);

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.index', [
            'supplier_id' => $supplier->id,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-aging-filters"', false);
        $response->assertSee('data-testid="supplier-aging-supplier-select"', false);
        $response->assertSee('data-testid="supplier-aging-bucket-select"', false);
        $response->assertSee('data-testid="supplier-aging-apply-filters"', false);
        $response->assertSee('data-testid="supplier-aging-reset-filters"', false);
        $response->assertSee('مورد فلتر أعمار ذمم الموردين');
        $response->assertSee('value="' . $supplier->id . '" selected', false);
        $response->assertSee('value="overdue_31_60" selected', false);
    }

    public function test_supplier_purchase_invoice_aging_report_filter_controls_keep_results_filtered(): void
    {
        $user = User::query()->firstOrFail();

        PurchaseInvoice::query()->delete();

        $selectedSupplier = $this->createSupplier([
            'name' => 'مورد ظاهر بعد الفلترة',
            'phone' => '0579851632',
        ]);

        $otherSupplier = $this->createSupplier([
            'name' => 'مورد غير ظاهر بعد الفلترة',
            'phone' => '0579851633',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $selectedSupplier->id,
            'invoice_number' => 'PI-SUPPLIER-AGING-FILTER-IN',
            'remaining_amount' => 1500,
            'grand_total' => 1500,
            'subtotal' => 1500,
            'due_at' => '2026-05-20 09:00:00',
        ]);

        $this->createPurchaseInvoice([
            'supplier_id' => $otherSupplier->id,
            'invoice_number' => 'PI-SUPPLIER-AGING-FILTER-OUT',
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
        $response->assertSee('مورد ظاهر بعد الفلترة');
        $response->assertSee('1,500.00 ريال');
        $response->assertDontSee('2,000.00 ريال');
    }

    private function createSupplier(array $overrides = []): Supplier
    {
        $columns = Schema::getColumnListing('suppliers');

        $data = array_merge([
            'company_id' => (int) DB::table('companies')->value('id'),
            'name' => 'مورد اختبار فلاتر أعمار ذمم الموردين',
            'phone' => '0579851600',
            'email' => uniqid('supplier-aging-filter-') . '@example.com',
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
            'invoice_number' => uniqid('PI-SUPPLIER-AGING-FILTER-'),
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
