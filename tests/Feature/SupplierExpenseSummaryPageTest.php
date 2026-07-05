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

class SupplierExpenseSummaryPageTest extends TestCase
{
    use RefreshDatabase;

    private int $supplierSequence = 200;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_supplier_show_displays_expense_summary_and_filtered_expenses_link(): void
    {
        $this->assertTrue(Schema::hasColumn('expenses', 'supplier_id'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');
        $categoryId = (int) DB::table('expense_categories')->value('id');

        $supplier = $this->createSupplier($companyId, [
            'name' => 'مورد ملخص صفحة المورد',
            'phone' => '0569800401',
            'email' => 'supplier-expense-summary@example.com',
        ]);

        $otherSupplier = $this->createSupplier($companyId, [
            'name' => 'مورد خارج ملخص صفحة المورد',
            'phone' => '0569800402',
            'email' => 'supplier-expense-summary-other@example.com',
        ]);

        $this->createExpense($companyId, $branchId, $categoryId, $supplier->id, [
            'description' => 'مصروف أول في صفحة المورد',
            'amount' => 320,
        ]);

        $this->createExpense($companyId, $branchId, $categoryId, $supplier->id, [
            'description' => 'مصروف ثاني في صفحة المورد',
            'amount' => 480,
        ]);

        $this->createExpense($companyId, $branchId, $categoryId, $otherSupplier->id, [
            'description' => 'مصروف لا يدخل في ملخص المورد',
            'amount' => 999,
        ]);

        $response = $this->actingAs($user)->get(route('suppliers.show', $supplier));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-expense-summary-card"', false);
        $response->assertSee('ملخص مصروفات المورد');
        $response->assertSee('مورد ملخص صفحة المورد');
        $response->assertSee('data-testid="supplier-expense-summary-count"', false);
        $response->assertSee('>2<', false);
        $response->assertSee('800.00 ريال');
        $response->assertSee('data-testid="supplier-expense-summary-link"', false);
        $response->assertSee('supplier_id=' . $supplier->id, false);
        $response->assertDontSee('999.00 ريال');
    }

    public function test_supplier_show_displays_zero_summary_when_supplier_has_no_expenses(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');

        $supplier = $this->createSupplier($companyId, [
            'name' => 'مورد بدون مصروفات',
            'phone' => '0569800403',
            'email' => 'supplier-no-expenses@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('suppliers.show', $supplier));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-expense-summary-card"', false);
        $response->assertSee('مورد بدون مصروفات');
        $response->assertSee('data-testid="supplier-expense-summary-count"', false);
        $response->assertSee('>0<', false);
        $response->assertSee('0.00 ريال');
        $response->assertSee('supplier_id=' . $supplier->id, false);
    }

    private function createSupplier(int $companyId, array $overrides = []): Supplier
    {
        $this->supplierSequence++;

        $columns = Schema::getColumnListing('suppliers');

        $data = [
            'company_id' => $companyId,
            'name' => 'مورد ملخص صفحة المورد ' . $this->supplierSequence,
            'phone' => '0569800' . str_pad((string) $this->supplierSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'supplier-expense-summary-' . $this->supplierSequence . '@example.com',
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
            'code' => 'EXP-SUP-PAGE-' . uniqid(),
            'description' => 'مصروف اختبار ملخص صفحة المورد',
            'amount' => 500,
            'tax_amount' => 0,
            'payment_method' => 'cash',
            'expense_date' => '2026-07-05',
            'reference_number' => 'EXP-SUP-PAGE-REF',
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
