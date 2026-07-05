<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SupplierExpenseExportLinksTest extends TestCase
{
    use RefreshDatabase;

    private int $supplierSequence = 320;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_supplier_show_displays_supplier_expense_export_links(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');

        $supplier = $this->createSupplier($companyId, [
            'name' => 'مورد روابط تصدير المصروفات',
            'phone' => '0569800521',
            'email' => 'supplier-expense-export-links@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('suppliers.show', $supplier));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-expense-export-links-card"', false);
        $response->assertSee('تصدير مصروفات المورد');
        $response->assertSee('data-testid="supplier-expense-export-all-link"', false);
        $response->assertSee('data-testid="supplier-expense-export-unpaid-link"', false);
        $response->assertSee('data-testid="supplier-expense-export-paid-link"', false);
        $response->assertSee('/expenses/export?supplier_id=' . $supplier->id, false);
        $response->assertSee('supplier_id=' . $supplier->id . '&amp;payment_status=unpaid', false);
        $response->assertSee('supplier_id=' . $supplier->id . '&amp;payment_status=paid', false);
    }

    private function createSupplier(int $companyId, array $overrides = []): Supplier
    {
        $this->supplierSequence++;

        $columns = Schema::getColumnListing('suppliers');

        $data = [
            'company_id' => $companyId,
            'name' => 'مورد روابط تصدير المصروفات ' . $this->supplierSequence,
            'phone' => '0569800' . str_pad((string) $this->supplierSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'supplier-expense-export-links-' . $this->supplierSequence . '@example.com',
            'city' => 'الرياض',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $data = array_merge($data, $overrides);
        $data = array_intersect_key($data, array_flip($columns));

        return Supplier::unguarded(fn () => Supplier::query()->create($data));
    }
}
