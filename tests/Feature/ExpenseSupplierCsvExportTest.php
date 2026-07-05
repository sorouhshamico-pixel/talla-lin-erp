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

class ExpenseSupplierCsvExportTest extends TestCase
{
    use RefreshDatabase;

    private int $supplierSequence = 120;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_expenses_csv_export_includes_supplier_column_and_supplier_name(): void
    {
        $this->assertTrue(Schema::hasColumn('expenses', 'supplier_id'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');
        $categoryId = (int) DB::table('expense_categories')->value('id');

        $supplier = $this->createSupplier($companyId, [
            'name' => 'مورد ظاهر في تصدير المصروفات',
            'phone' => '0569800321',
            'email' => 'expense-supplier-csv-visible@example.com',
        ]);

        $this->createExpense($companyId, $branchId, $categoryId, $supplier->id, [
            'description' => 'مصروف يظهر في تصدير المورد',
            'amount' => 780,
            'reference_number' => 'EXP-SUP-CSV-001',
        ]);

        $response = $this->actingAs($user)->get('/expenses/export');

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('المورد', $content);
        $this->assertStringContainsString('مورد ظاهر في تصدير المصروفات', $content);
        $this->assertStringContainsString('مصروف يظهر في تصدير المورد', $content);
        $this->assertStringContainsString('EXP-SUP-CSV-001', $content);
    }

    public function test_expenses_csv_export_respects_supplier_filter(): void
    {
        $this->assertTrue(Schema::hasColumn('expenses', 'supplier_id'));

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');
        $categoryId = (int) DB::table('expense_categories')->value('id');

        $selectedSupplier = $this->createSupplier($companyId, [
            'name' => 'مورد CSV محدد',
            'phone' => '0569800322',
            'email' => 'expense-supplier-csv-selected@example.com',
        ]);

        $hiddenSupplier = $this->createSupplier($companyId, [
            'name' => 'مورد CSV مخفي',
            'phone' => '0569800323',
            'email' => 'expense-supplier-csv-hidden@example.com',
        ]);

        $this->createExpense($companyId, $branchId, $categoryId, $selectedSupplier->id, [
            'description' => 'مصروف CSV يظهر مع فلتر المورد',
            'amount' => 1180,
            'reference_number' => 'EXP-SUP-CSV-IN',
        ]);

        $this->createExpense($companyId, $branchId, $categoryId, $hiddenSupplier->id, [
            'description' => 'مصروف CSV لا يظهر مع فلتر المورد',
            'amount' => 1320,
            'reference_number' => 'EXP-SUP-CSV-OUT',
        ]);

        $response = $this->actingAs($user)->get('/expenses/export?supplier_id=' . $selectedSupplier->id);

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('المورد', $content);
        $this->assertStringContainsString('مورد CSV محدد', $content);
        $this->assertStringContainsString('مصروف CSV يظهر مع فلتر المورد', $content);
        $this->assertStringContainsString('EXP-SUP-CSV-IN', $content);
        $this->assertStringNotContainsString('مصروف CSV لا يظهر مع فلتر المورد', $content);
        $this->assertStringNotContainsString('EXP-SUP-CSV-OUT', $content);
        $this->assertStringNotContainsString('مورد CSV مخفي', $content);
    }

    private function createSupplier(int $companyId, array $overrides = []): Supplier
    {
        $this->supplierSequence++;

        $columns = Schema::getColumnListing('suppliers');

        $data = [
            'company_id' => $companyId,
            'name' => 'مورد تصدير المصروفات ' . $this->supplierSequence,
            'phone' => '0569800' . str_pad((string) $this->supplierSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'expense-supplier-csv-' . $this->supplierSequence . '@example.com',
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
            'code' => 'EXP-SUP-CSV-' . uniqid(),
            'description' => 'مصروف اختبار تصدير المورد',
            'amount' => 500,
            'tax_amount' => 0,
            'payment_method' => 'cash',
            'expense_date' => '2026-07-05',
            'reference_number' => 'EXP-SUP-CSV-REF',
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
