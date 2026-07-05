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

class ExpenseSupplierIndexDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_expenses_index_displays_supplier_name_for_supplier_linked_expense(): void
    {
        $this->assertTrue(Schema::hasColumn('expenses', 'supplier_id'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');
        $categoryId = (int) DB::table('expense_categories')->value('id');

        $supplier = $this->createSupplier($companyId);

        $this->createExpense($companyId, $branchId, $categoryId, $supplier->id, [
            'description' => 'مصروف مرتبط بمورد يظهر في القائمة',
            'amount' => 875,
            'reference_number' => 'EXP-SUP-IDX-001',
        ]);

        $response = $this->actingAs($user)->get(route('expenses.index'));

        $response->assertOk();
        $response->assertSee('المورد');
        $response->assertSee($supplier->name);
        $response->assertSee('مصروف مرتبط بمورد يظهر في القائمة');
        $response->assertSee('data-testid="expense-supplier-header"', false);
        $response->assertSee('data-testid="expense-supplier-cell"', false);
    }

    public function test_expenses_index_displays_dash_when_expense_has_no_supplier(): void
    {
        $this->assertTrue(Schema::hasColumn('expenses', 'supplier_id'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');
        $categoryId = (int) DB::table('expense_categories')->value('id');

        $this->createExpense($companyId, $branchId, $categoryId, null, [
            'description' => 'مصروف بدون مورد يظهر بشرطة',
            'amount' => 430,
            'reference_number' => 'EXP-NO-SUP-IDX-001',
        ]);

        $response = $this->actingAs($user)->get(route('expenses.index'));

        $response->assertOk();
        $response->assertSee('مصروف بدون مورد يظهر بشرطة');
        $response->assertSee('data-testid="expense-supplier-cell"', false);
        $response->assertSee('<td data-testid="expense-supplier-cell">-</td>', false);
    }

    private function createSupplier(int $companyId): Supplier
    {
        $columns = Schema::getColumnListing('suppliers');

        $data = [
            'company_id' => $companyId,
            'name' => 'مورد ظاهر في قائمة المصروفات',
            'phone' => '0569800241',
            'email' => 'expense-supplier-index@example.com',
            'city' => 'الرياض',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

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
            'code' => 'EXP-SUP-IDX-' . uniqid(),
            'description' => 'مصروف اختبار ظهور المورد',
            'amount' => 500,
            'tax_amount' => 0,
            'payment_method' => 'cash',
            'expense_date' => '2026-07-05',
            'reference_number' => 'EXP-SUP-IDX-REF',
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
