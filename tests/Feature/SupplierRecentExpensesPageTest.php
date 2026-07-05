<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SupplierRecentExpensesPageTest extends TestCase
{
    use RefreshDatabase;

    private int $supplierSequence = 230;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_supplier_show_displays_recent_supplier_expenses(): void
    {
        $this->assertTrue(Schema::hasColumn('expenses', 'supplier_id'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');
        $categoryId = (int) DB::table('expense_categories')->value('id');

        $supplier = $this->createSupplier($companyId, [
            'name' => 'مورد آخر المصروفات',
            'phone' => '0569800431',
            'email' => 'supplier-recent-expenses@example.com',
        ]);

        $otherSupplier = $this->createSupplier($companyId, [
            'name' => 'مورد مصروفات غير ظاهرة',
            'phone' => '0569800432',
            'email' => 'supplier-recent-expenses-other@example.com',
        ]);

        $expense = $this->createExpense($companyId, $branchId, $categoryId, $supplier->id, [
            'description' => 'مصروف حديث ظاهر في صفحة المورد',
            'amount' => 615,
            'expense_date' => '2026-07-05',
            'is_paid' => false,
        ]);

        $this->createExpense($companyId, $branchId, $categoryId, $otherSupplier->id, [
            'description' => 'مصروف لمورد آخر لا يظهر',
            'amount' => 890,
            'expense_date' => '2026-07-06',
        ]);

        $response = $this->actingAs($user)->get(route('suppliers.show', $supplier));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-recent-expenses-card"', false);
        $response->assertSee('آخر مصروفات المورد');
        $response->assertSee('مصروف حديث ظاهر في صفحة المورد');
        $response->assertSee('615.00 ريال');
        $response->assertSee('غير مدفوع');
        $response->assertSee('2026-07-05');
        $response->assertSee(route('expenses.edit', $expense), false);
        $response->assertDontSee('مصروف لمورد آخر لا يظهر');
    }

    public function test_supplier_show_displays_empty_recent_expenses_message(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');

        $supplier = $this->createSupplier($companyId, [
            'name' => 'مورد بدون آخر مصروفات',
            'phone' => '0569800433',
            'email' => 'supplier-no-recent-expenses@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('suppliers.show', $supplier));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-recent-expenses-card"', false);
        $response->assertSee('لا توجد مصروفات مرتبطة بهذا المورد بعد.');
    }

    private function createSupplier(int $companyId, array $overrides = []): Supplier
    {
        $this->supplierSequence++;

        $columns = Schema::getColumnListing('suppliers');

        $data = [
            'company_id' => $companyId,
            'name' => 'مورد آخر المصروفات ' . $this->supplierSequence,
            'phone' => '0569800' . str_pad((string) $this->supplierSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'supplier-recent-expenses-' . $this->supplierSequence . '@example.com',
            'city' => 'الرياض',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $data = array_merge($data, $overrides);
        $data = array_intersect_key($data, array_flip($columns));

        return Supplier::unguarded(fn () => Supplier::query()->create($data));
    }

    private function createExpense(int $companyId, int $branchId, int $categoryId, ?int $supplierId, array $overrides = []): Expense
    {
        $columns = Schema::getColumnListing('expenses');

        $data = [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'expense_category_id' => $categoryId,
            'supplier_id' => $supplierId,
            'user_id' => (int) DB::table('users')->value('id'),
            'code' => 'EXP-SUP-REC-' . uniqid(),
            'description' => 'مصروف اختبار آخر مصروفات المورد',
            'amount' => 500,
            'tax_amount' => 0,
            'payment_method' => 'cash',
            'expense_date' => '2026-07-05',
            'reference_number' => 'EXP-SUP-REC-REF',
            'notes' => null,
            'is_paid' => false,
            'attachment_path' => null,
            'attachment_original_name' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $data = array_merge($data, $overrides);
        $data = array_intersect_key($data, array_flip($columns));

        return Expense::unguarded(fn () => Expense::query()->create($data));
    }
}
