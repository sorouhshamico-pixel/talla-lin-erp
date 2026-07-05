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

class SupplierUnpaidExpensesSummaryPageTest extends TestCase
{
    use RefreshDatabase;

    private int $supplierSequence = 260;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_supplier_show_displays_unpaid_expenses_summary(): void
    {
        $this->assertTrue(Schema::hasColumn('expenses', 'supplier_id'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');
        $categoryId = (int) DB::table('expense_categories')->value('id');

        $supplier = $this->createSupplier($companyId, [
            'name' => 'مورد ملخص غير المدفوع',
            'phone' => '0569800461',
            'email' => 'supplier-unpaid-summary@example.com',
        ]);

        $this->createExpense($companyId, $branchId, $categoryId, $supplier->id, [
            'description' => 'مصروف غير مدفوع أول',
            'amount' => 275,
            'is_paid' => false,
        ]);

        $this->createExpense($companyId, $branchId, $categoryId, $supplier->id, [
            'description' => 'مصروف غير مدفوع ثاني',
            'amount' => 425,
            'is_paid' => false,
        ]);

        $this->createExpense($companyId, $branchId, $categoryId, $supplier->id, [
            'description' => 'مصروف مدفوع لا يدخل في الملخص',
            'amount' => 900,
            'is_paid' => true,
        ]);

        $response = $this->actingAs($user)->get(route('suppliers.show', $supplier));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-unpaid-expense-summary-card"', false);
        $response->assertSee('ملخص مصروفات المورد غير المدفوعة');
        $response->assertSee('data-testid="supplier-unpaid-expense-summary-count"', false);
        $response->assertSee('>2<', false);
        $response->assertSee('700.00 ريال');
        $response->assertSee('يحتاج متابعة');
        $response->assertSee('data-testid="supplier-unpaid-expense-summary-link"', false);
        $response->assertSee('supplier_id=' . $supplier->id, false);
        $response->assertSee('payment_status=unpaid', false);
    }

    public function test_supplier_show_displays_zero_unpaid_summary_when_all_expenses_are_paid(): void
    {
        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');
        $categoryId = (int) DB::table('expense_categories')->value('id');

        $supplier = $this->createSupplier($companyId, [
            'name' => 'مورد بدون غير مدفوع',
            'phone' => '0569800462',
            'email' => 'supplier-no-unpaid-summary@example.com',
        ]);

        $this->createExpense($companyId, $branchId, $categoryId, $supplier->id, [
            'description' => 'مصروف مدفوع بالكامل',
            'amount' => 510,
            'is_paid' => true,
        ]);

        $response = $this->actingAs($user)->get(route('suppliers.show', $supplier));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-unpaid-expense-summary-card"', false);
        $response->assertSee('>0<', false);
        $response->assertSee('0.00 ريال');
        $response->assertSee('لا توجد التزامات غير مدفوعة');
        $response->assertSee('payment_status=unpaid', false);
    }

    private function createSupplier(int $companyId, array $overrides = []): Supplier
    {
        $this->supplierSequence++;

        $columns = Schema::getColumnListing('suppliers');

        $data = [
            'company_id' => $companyId,
            'name' => 'مورد ملخص غير المدفوع ' . $this->supplierSequence,
            'phone' => '0569800' . str_pad((string) $this->supplierSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'supplier-unpaid-summary-' . $this->supplierSequence . '@example.com',
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
            'code' => 'EXP-SUP-UNP-' . uniqid(),
            'description' => 'مصروف اختبار ملخص غير المدفوع',
            'amount' => 500,
            'tax_amount' => 0,
            'payment_method' => 'cash',
            'expense_date' => '2026-07-05',
            'reference_number' => 'EXP-SUP-UNP-REF',
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
