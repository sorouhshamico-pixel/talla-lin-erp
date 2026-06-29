<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExpenseLargeExportFinalCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_large_paid_export_respects_branch_and_category_filters(): void
    {
        $this->actingAsOwner();

        DB::table('expenses')->delete();

        [$branchOne, $branchTwo] = $this->twoBranchIds();
        [$categoryOne, $categoryTwo] = $this->twoCategoryIds();

        $this->insertExpense([
            'code' => 'EXP-LP-MATCH-001',
            'description' => 'Large paid matching branch and category',
            'amount' => 1500,
            'payment_method' => 'cash',
            'branch_id' => $branchOne,
            'expense_category_id' => $categoryOne,
            'is_paid' => true,
        ]);

        $this->insertExpense([
            'code' => 'EXP-LP-OTHER-BRANCH-001',
            'description' => 'Large paid different branch',
            'amount' => 2500,
            'payment_method' => 'cash',
            'branch_id' => $branchTwo,
            'expense_category_id' => $categoryOne,
            'is_paid' => true,
        ]);

        $this->insertExpense([
            'code' => 'EXP-LP-OTHER-CATEGORY-001',
            'description' => 'Large paid different category',
            'amount' => 3500,
            'payment_method' => 'cash',
            'branch_id' => $branchOne,
            'expense_category_id' => $categoryTwo,
            'is_paid' => true,
        ]);

        $this->insertExpense([
            'code' => 'EXP-LU-MATCH-001',
            'description' => 'Large unpaid matching branch and category',
            'amount' => 4500,
            'payment_method' => 'cash',
            'branch_id' => $branchOne,
            'expense_category_id' => $categoryOne,
            'is_paid' => false,
        ]);

        $response = $this->get(route('expenses.export-large-paid', [
            'branch_id' => $branchOne,
            'expense_category_id' => $categoryOne,
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('EXP-LP-MATCH-001', $content);

        $this->assertStringNotContainsString('EXP-LP-OTHER-BRANCH-001', $content);
        $this->assertStringNotContainsString('EXP-LP-OTHER-CATEGORY-001', $content);
        $this->assertStringNotContainsString('EXP-LU-MATCH-001', $content);
    }

    public function test_large_unpaid_export_respects_branch_and_category_filters(): void
    {
        $this->actingAsOwner();

        DB::table('expenses')->delete();

        [$branchOne, $branchTwo] = $this->twoBranchIds();
        [$categoryOne, $categoryTwo] = $this->twoCategoryIds();

        $this->insertExpense([
            'code' => 'EXP-LU-MATCH-001',
            'description' => 'Large unpaid matching branch and category',
            'amount' => 1500,
            'payment_method' => 'cash',
            'branch_id' => $branchOne,
            'expense_category_id' => $categoryOne,
            'is_paid' => false,
        ]);

        $this->insertExpense([
            'code' => 'EXP-LU-OTHER-BRANCH-001',
            'description' => 'Large unpaid different branch',
            'amount' => 2500,
            'payment_method' => 'cash',
            'branch_id' => $branchTwo,
            'expense_category_id' => $categoryOne,
            'is_paid' => false,
        ]);

        $this->insertExpense([
            'code' => 'EXP-LU-OTHER-CATEGORY-001',
            'description' => 'Large unpaid different category',
            'amount' => 3500,
            'payment_method' => 'cash',
            'branch_id' => $branchOne,
            'expense_category_id' => $categoryTwo,
            'is_paid' => false,
        ]);

        $this->insertExpense([
            'code' => 'EXP-LP-MATCH-001',
            'description' => 'Large paid matching branch and category',
            'amount' => 4500,
            'payment_method' => 'cash',
            'branch_id' => $branchOne,
            'expense_category_id' => $categoryOne,
            'is_paid' => true,
        ]);

        $response = $this->get(route('expenses.export-large-unpaid', [
            'branch_id' => $branchOne,
            'expense_category_id' => $categoryOne,
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('EXP-LU-MATCH-001', $content);

        $this->assertStringNotContainsString('EXP-LU-OTHER-BRANCH-001', $content);
        $this->assertStringNotContainsString('EXP-LU-OTHER-CATEGORY-001', $content);
        $this->assertStringNotContainsString('EXP-LP-MATCH-001', $content);
    }

    public function test_large_paid_export_has_expected_csv_headers_and_file_name(): void
    {
        $this->actingAsOwner();

        DB::table('expenses')->delete();

        $this->insertExpense([
            'code' => 'EXP-LP-HEADER-001',
            'description' => 'Large paid header check',
            'amount' => 1500,
            'payment_method' => 'cash',
            'branch_id' => DB::table('branches')->orderBy('id')->value('id'),
            'expense_category_id' => DB::table('expense_categories')->orderBy('id')->value('id'),
            'is_paid' => true,
        ]);

        $response = $this->get(route('expenses.export-large-paid'));

        $response->assertOk();

        $contentDisposition = $response->headers->get('Content-Disposition') ?? '';

        $this->assertStringContainsString('large-paid-expenses-report-', $contentDisposition);
        $this->assertStringContainsString('.csv', $contentDisposition);

        $content = $response->streamedContent();

        $this->assertCsvHeadersExist($content);
        $this->assertStringContainsString('EXP-LP-HEADER-001', $content);
        $this->assertStringContainsString('مدفوع', $content);
    }

    public function test_large_unpaid_export_has_expected_csv_headers_and_file_name(): void
    {
        $this->actingAsOwner();

        DB::table('expenses')->delete();

        $this->insertExpense([
            'code' => 'EXP-LU-HEADER-001',
            'description' => 'Large unpaid header check',
            'amount' => 1500,
            'payment_method' => 'cash',
            'branch_id' => DB::table('branches')->orderBy('id')->value('id'),
            'expense_category_id' => DB::table('expense_categories')->orderBy('id')->value('id'),
            'is_paid' => false,
        ]);

        $response = $this->get(route('expenses.export-large-unpaid'));

        $response->assertOk();

        $contentDisposition = $response->headers->get('Content-Disposition') ?? '';

        $this->assertStringContainsString('large-unpaid-expenses-report-', $contentDisposition);
        $this->assertStringContainsString('.csv', $contentDisposition);

        $content = $response->streamedContent();

        $this->assertCsvHeadersExist($content);
        $this->assertStringContainsString('EXP-LU-HEADER-001', $content);
        $this->assertStringContainsString('غير مدفوع', $content);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function insertExpense(array $overrides): void
    {
        $now = now();

        DB::table('expenses')->insert([
            'company_id' => DB::table('companies')->value('id'),
            'branch_id' => $overrides['branch_id'],
            'expense_category_id' => $overrides['expense_category_id'],
            'user_id' => DB::table('users')->value('id'),
            'code' => $overrides['code'],
            'description' => $overrides['description'],
            'amount' => $overrides['amount'],
            'tax_amount' => 0,
            'payment_method' => $overrides['payment_method'],
            'expense_date' => '2026-01-10',
            'reference_number' => null,
            'notes' => null,
            'is_paid' => $overrides['is_paid'],
            'attachment_path' => null,
            'attachment_original_name' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function twoBranchIds(): array
    {
        $ids = DB::table('branches')
            ->orderBy('id')
            ->limit(2)
            ->pluck('id')
            ->all();

        $this->assertCount(2, $ids, 'InitialSetupSeeder must create at least two branches for this test.');

        return [(int) $ids[0], (int) $ids[1]];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function twoCategoryIds(): array
    {
        $ids = DB::table('expense_categories')
            ->orderBy('id')
            ->limit(2)
            ->pluck('id')
            ->all();

        $this->assertCount(2, $ids, 'InitialSetupSeeder must create at least two expense categories for this test.');

        return [(int) $ids[0], (int) $ids[1]];
    }

    private function assertCsvHeadersExist(string $content): void
    {
        foreach ([
            'الكود',
            'التاريخ',
            'الوصف',
            'الفرع',
            'التصنيف',
            'طريقة الدفع',
            'حالة الدفع',
            'المبلغ',
            'الضريبة',
            'رقم المرجع',
            'المرفق',
        ] as $header) {
            $this->assertStringContainsString($header, $content);
        }
    }

    private function actingAsOwner(): void
    {
        $user = User::query()->firstOrFail();

        $this->actingAs($user);
    }
}
