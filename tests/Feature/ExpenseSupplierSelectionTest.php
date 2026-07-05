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

class ExpenseSupplierSelectionTest extends TestCase
{
    use RefreshDatabase;

    private int $supplierSequence = 70;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_expense_create_and_edit_forms_show_supplier_select(): void
    {
        $this->assertTrue(Schema::hasColumn('expenses', 'supplier_id'));

        $user = User::query()->firstOrFail();
        $branchId = (int) DB::table('branches')->value('id');
        $categoryId = (int) DB::table('expense_categories')->value('id');
        $companyId = (int) DB::table('companies')->value('id');

        $supplier = $this->createSupplier($companyId);
        $expense = $this->createExpense($companyId, $branchId, $categoryId, $supplier->id, [
            'description' => 'مصروف لاختبار حقل المورد',
        ]);

        $createResponse = $this->actingAs($user)->get(route('expenses.create'));

        $createResponse->assertOk();
        $createResponse->assertSee('المورد');
        $createResponse->assertSee('بدون مورد محدد');
        $createResponse->assertSee($supplier->name);
        $createResponse->assertSee('data-testid="expense-supplier-select"', false);

        $editResponse = $this->actingAs($user)->get(route('expenses.edit', $expense));

        $editResponse->assertOk();
        $editResponse->assertSee('المورد');
        $editResponse->assertSee($supplier->name);
        $editResponse->assertSee('data-testid="expense-supplier-select"', false);
    }

    public function test_expense_can_be_saved_with_supplier(): void
    {
        $this->assertTrue(Schema::hasColumn('expenses', 'supplier_id'));

        $user = User::query()->firstOrFail();
        $branchId = (int) DB::table('branches')->value('id');
        $categoryId = (int) DB::table('expense_categories')->value('id');
        $companyId = (int) DB::table('companies')->value('id');

        $supplier = $this->createSupplier($companyId);

        $this->actingAs($user)
            ->post(route('expenses.store'), [
                'branch_id' => $branchId,
                'expense_category_id' => $categoryId,
                'supplier_id' => $supplier->id,
                'description' => 'مصروف محفوظ مع مورد',
                'amount' => 750,
                'tax_amount' => 0,
                'payment_method' => 'bank_transfer',
                'is_paid' => '0',
                'expense_date' => '2026-07-05',
                'reference_number' => 'EXP-SUP-STORE-001',
                'notes' => 'اختبار حفظ المورد مع المصروف',
            ])
            ->assertRedirect(route('expenses.index'));

        $expense = Expense::query()
            ->where('description', 'مصروف محفوظ مع مورد')
            ->firstOrFail();

        $this->assertSame($supplier->id, $expense->supplier_id);
        $this->assertFalse($expense->is_paid);
    }

    public function test_expense_supplier_can_be_updated(): void
    {
        $this->assertTrue(Schema::hasColumn('expenses', 'supplier_id'));

        $user = User::query()->firstOrFail();
        $branchId = (int) DB::table('branches')->value('id');
        $categoryId = (int) DB::table('expense_categories')->value('id');
        $companyId = (int) DB::table('companies')->value('id');

        $oldSupplier = $this->createSupplier($companyId, [
            'name' => 'مورد قديم للمصروف',
            'email' => 'old-expense-supplier@example.com',
            'phone' => '0569800171',
        ]);

        $newSupplier = $this->createSupplier($companyId, [
            'name' => 'مورد جديد للمصروف',
            'email' => 'new-expense-supplier@example.com',
            'phone' => '0569800172',
        ]);

        $expense = $this->createExpense($companyId, $branchId, $categoryId, $oldSupplier->id, [
            'description' => 'مصروف قبل تعديل المورد',
            'amount' => 450,
            'is_paid' => true,
        ]);

        $this->actingAs($user)
            ->patch(route('expenses.update', $expense), [
                'branch_id' => $branchId,
                'expense_category_id' => $categoryId,
                'supplier_id' => $newSupplier->id,
                'description' => 'مصروف بعد تعديل المورد',
                'amount' => 450,
                'tax_amount' => 0,
                'payment_method' => 'cash',
                'is_paid' => '1',
                'expense_date' => '2026-07-06',
                'reference_number' => 'EXP-SUP-UPD-001',
                'notes' => 'اختبار تعديل مورد المصروف',
            ])
            ->assertRedirect(route('expenses.index'));

        $expense->refresh();

        $this->assertSame($newSupplier->id, $expense->supplier_id);
        $this->assertSame('مصروف بعد تعديل المورد', $expense->description);
    }

    private function createSupplier(int $companyId, array $overrides = []): Supplier
    {
        $this->supplierSequence++;

        $columns = Schema::getColumnListing('suppliers');

        $data = [
            'company_id' => $companyId,
            'name' => 'مورد اختيار المصروف ' . $this->supplierSequence,
            'phone' => '0569800' . str_pad((string) $this->supplierSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'expense-supplier-selection-' . $this->supplierSequence . '@example.com',
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
            'code' => 'EXP-SUP-SEL-' . uniqid(),
            'description' => 'مصروف اختيار مورد',
            'amount' => 500,
            'tax_amount' => 0,
            'payment_method' => 'cash',
            'expense_date' => '2026-07-05',
            'reference_number' => 'EXP-SUP-SEL-REF',
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
