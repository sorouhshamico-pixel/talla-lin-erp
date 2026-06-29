<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExpenseLargeExportDateFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_large_paid_export_respects_from_date_and_to_date_filters(): void
    {
        $this->actingAsOwner();

        DB::table('expenses')->delete();

        $this->insertExpense([
            'code' => 'EXP-LP-IN-001',
            'description' => 'Large paid inside date range',
            'amount' => 1500,
            'payment_method' => 'cash',
            'expense_date' => '2026-01-10',
            'is_paid' => true,
        ]);

        $this->insertExpense([
            'code' => 'EXP-LP-BEFORE-001',
            'description' => 'Large paid before date range',
            'amount' => 2000,
            'payment_method' => 'cash',
            'expense_date' => '2025-12-31',
            'is_paid' => true,
        ]);

        $this->insertExpense([
            'code' => 'EXP-LP-AFTER-001',
            'description' => 'Large paid after date range',
            'amount' => 2500,
            'payment_method' => 'cash',
            'expense_date' => '2026-02-01',
            'is_paid' => true,
        ]);

        $this->insertExpense([
            'code' => 'EXP-LU-IN-001',
            'description' => 'Large unpaid inside date range',
            'amount' => 3000,
            'payment_method' => 'cash',
            'expense_date' => '2026-01-15',
            'is_paid' => false,
        ]);

        $response = $this->get(route('expenses.export-large-paid', [
            'from_date' => '2026-01-01',
            'to_date' => '2026-01-31',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('EXP-LP-IN-001', $content);

        $this->assertStringNotContainsString('EXP-LP-BEFORE-001', $content);
        $this->assertStringNotContainsString('EXP-LP-AFTER-001', $content);
        $this->assertStringNotContainsString('EXP-LU-IN-001', $content);
    }

    public function test_large_unpaid_export_respects_from_date_and_to_date_filters(): void
    {
        $this->actingAsOwner();

        DB::table('expenses')->delete();

        $this->insertExpense([
            'code' => 'EXP-LU-IN-001',
            'description' => 'Large unpaid inside date range',
            'amount' => 1500,
            'payment_method' => 'cash',
            'expense_date' => '2026-01-10',
            'is_paid' => false,
        ]);

        $this->insertExpense([
            'code' => 'EXP-LU-BEFORE-001',
            'description' => 'Large unpaid before date range',
            'amount' => 2000,
            'payment_method' => 'cash',
            'expense_date' => '2025-12-31',
            'is_paid' => false,
        ]);

        $this->insertExpense([
            'code' => 'EXP-LU-AFTER-001',
            'description' => 'Large unpaid after date range',
            'amount' => 2500,
            'payment_method' => 'cash',
            'expense_date' => '2026-02-01',
            'is_paid' => false,
        ]);

        $this->insertExpense([
            'code' => 'EXP-LP-IN-001',
            'description' => 'Large paid inside date range',
            'amount' => 3000,
            'payment_method' => 'cash',
            'expense_date' => '2026-01-15',
            'is_paid' => true,
        ]);

        $response = $this->get(route('expenses.export-large-unpaid', [
            'from_date' => '2026-01-01',
            'to_date' => '2026-01-31',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('EXP-LU-IN-001', $content);

        $this->assertStringNotContainsString('EXP-LU-BEFORE-001', $content);
        $this->assertStringNotContainsString('EXP-LU-AFTER-001', $content);
        $this->assertStringNotContainsString('EXP-LP-IN-001', $content);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function insertExpense(array $overrides): void
    {
        $now = now();

        DB::table('expenses')->insert([
            'company_id' => DB::table('companies')->value('id'),
            'branch_id' => DB::table('branches')->value('id'),
            'expense_category_id' => DB::table('expense_categories')->value('id'),
            'user_id' => DB::table('users')->value('id'),
            'code' => $overrides['code'],
            'description' => $overrides['description'],
            'amount' => $overrides['amount'],
            'tax_amount' => 0,
            'payment_method' => $overrides['payment_method'],
            'expense_date' => $overrides['expense_date'],
            'reference_number' => null,
            'notes' => null,
            'is_paid' => $overrides['is_paid'],
            'attachment_path' => null,
            'attachment_original_name' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function actingAsOwner(): void
    {
        $user = User::query()->firstOrFail();

        $this->actingAs($user);
    }
}
