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

class ExpenseSupplierFilterTest extends TestCase
{
    use RefreshDatabase;

    private int $supplierSequence = 90;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_expenses_index_shows_supplier_filter(): void
    {
        $this->assertTrue(Schema::hasColumn('expenses', 'supplier_id'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');

        $supplier = $this->createSupplier($companyId, [
            'name' => 'مورد ظاهر في فلتر المصروفات',
            'phone' => '0569800291',
            'email' => 'expense-supplier-filter-visible@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('expenses.index'));

        $response->assertOk();
        $response->assertSee('المورد');
        $response->assertSee('كل الموردين');
        $response->assertSee($supplier->name);
        $response->assertSee('data-testid="expense-supplier-filter"', false);
    }

    public function test_expenses_index_filters_by_supplier(): void
    {
        $this->assertTrue(Schema::hasColumn('expenses', 'supplier_id'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');
        $categoryId = (int) DB::table('expense_categories')->value('id');

        $selectedSupplier = $this->createSupplier($companyId, [
            'name' => 'مورد مصروفات محدد للفلتر',
            'phone' => '0569800292',
            'email' => 'expense-supplier-filter-selected@example.com',
        ]);

        $hiddenSupplier = $this->createSupplier($companyId, [
            'name' => 'مورد مصروفات مخفي من الفلتر',
            'phone' => '0569800293',
            'email' => 'expense-supplier-filter-hidden@example.com',
        ]);

        $this->createExpense($companyId, $branchId, $categoryId, $selectedSupplier->id, [
            'description' => 'مصروف يظهر عند فلترة المورد',
            'amount' => 640,
            'reference_number' => 'EXP-SUP-FILTER-IN',
        ]);

        $this->createExpense($companyId, $branchId, $categoryId, $hiddenSupplier->id, [
            'description' => 'مصروف لا يظهر عند فلترة المورد',
            'amount' => 920,
            'reference_number' => 'EXP-SUP-FILTER-OUT',
        ]);

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'supplier_id' => $selectedSupplier->id,
        ]));

        $response->assertOk();
        $response->assertSee('مصروف يظهر عند فلترة المورد');
        $response->assertSee($selectedSupplier->name);
        $response->assertSee($hiddenSupplier->name);
        $response->assertDontSee('مصروف لا يظهر عند فلترة المورد');
        $response->assertSee('supplier_id=' . $selectedSupplier->id, false);
    }

    private function createSupplier(int $companyId, array $overrides = []): Supplier
    {
        $this->supplierSequence++;

        $columns = Schema::getColumnListing('suppliers');

        $data = [
            'company_id' => $companyId,
            'name' => 'مورد فلتر المصروفات ' . $this->supplierSequence,
            'phone' => '0569800' . str_pad((string) $this->supplierSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'expense-supplier-filter-' . $this->supplierSequence . '@example.com',
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
            'code' => 'EXP-SUP-FILTER-' . uniqid(),
            'description' => 'مصروف اختبار فلتر المورد',
            'amount' => 500,
            'tax_amount' => 0,
            'payment_method' => 'cash',
            'expense_date' => '2026-07-05',
            'reference_number' => 'EXP-SUP-FILTER-REF',
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
