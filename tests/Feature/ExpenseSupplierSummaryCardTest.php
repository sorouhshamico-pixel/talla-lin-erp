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

class ExpenseSupplierSummaryCardTest extends TestCase
{
    use RefreshDatabase;

    private int $supplierSequence = 150;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_supplier_summary_card_is_hidden_without_supplier_filter(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('expenses.index'));

        $response->assertOk();
        $response->assertDontSee('data-testid="expense-selected-supplier-summary-card"', false);
    }

    public function test_supplier_summary_card_displays_selected_supplier_totals(): void
    {
        $this->assertTrue(Schema::hasColumn('expenses', 'supplier_id'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');
        $categoryId = (int) DB::table('expense_categories')->value('id');

        $selectedSupplier = $this->createSupplier($companyId, [
            'name' => 'مورد ملخص المصروفات',
            'phone' => '0569800351',
            'email' => 'expense-supplier-summary@example.com',
        ]);

        $otherSupplier = $this->createSupplier($companyId, [
            'name' => 'مورد خارج ملخص المصروفات',
            'phone' => '0569800352',
            'email' => 'expense-supplier-summary-other@example.com',
        ]);

        $this->createExpense($companyId, $branchId, $categoryId, $selectedSupplier->id, [
            'description' => 'مصروف أول داخل ملخص المورد',
            'amount' => 300,
        ]);

        $this->createExpense($companyId, $branchId, $categoryId, $selectedSupplier->id, [
            'description' => 'مصروف ثاني داخل ملخص المورد',
            'amount' => 450,
        ]);

        $this->createExpense($companyId, $branchId, $categoryId, $otherSupplier->id, [
            'description' => 'مصروف خارج ملخص المورد',
            'amount' => 999,
        ]);

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'supplier_id' => $selectedSupplier->id,
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="expense-selected-supplier-summary-card"', false);
        $response->assertSee('ملخص مصروفات المورد المحدد');
        $response->assertSee('مورد ملخص المصروفات');
        $response->assertSee('data-testid="expense-selected-supplier-count"', false);
        $response->assertSee('>2<', false);
        $response->assertSee('750.00 ريال');
        $response->assertSee('مصروف أول داخل ملخص المورد');
        $response->assertSee('مصروف ثاني داخل ملخص المورد');
        $response->assertDontSee('مصروف خارج ملخص المورد');
    }

    private function createSupplier(int $companyId, array $overrides = []): Supplier
    {
        $this->supplierSequence++;

        $columns = Schema::getColumnListing('suppliers');

        $data = [
            'company_id' => $companyId,
            'name' => 'مورد ملخص المصروفات ' . $this->supplierSequence,
            'phone' => '0569800' . str_pad((string) $this->supplierSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'expense-supplier-summary-' . $this->supplierSequence . '@example.com',
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
            'code' => 'EXP-SUP-SUM-' . uniqid(),
            'description' => 'مصروف اختبار ملخص المورد',
            'amount' => 500,
            'tax_amount' => 0,
            'payment_method' => 'cash',
            'expense_date' => '2026-07-05',
            'reference_number' => 'EXP-SUP-SUM-REF',
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
