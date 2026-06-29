<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExpenseLargeExportPaymentMethodFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_large_paid_export_respects_payment_method_filter(): void
    {
        $this->actingAsOwner();

        DB::table('expenses')->delete();

        $this->insertExpense([
            'code' => 'EXP-LP-CASH-001',
            'description' => 'Large paid cash expense',
            'amount' => 1500,
            'payment_method' => 'cash',
            'is_paid' => true,
        ]);

        $this->insertExpense([
            'code' => 'EXP-LP-BANK-001',
            'description' => 'Large paid bank transfer expense',
            'amount' => 2500,
            'payment_method' => 'bank_transfer',
            'is_paid' => true,
        ]);

        $this->insertExpense([
            'code' => 'EXP-LU-CASH-001',
            'description' => 'Large unpaid cash expense',
            'amount' => 3000,
            'payment_method' => 'cash',
            'is_paid' => false,
        ]);

        $this->insertExpense([
            'code' => 'EXP-SP-CASH-001',
            'description' => 'Small paid cash expense',
            'amount' => 500,
            'payment_method' => 'cash',
            'is_paid' => true,
        ]);

        $response = $this->get(route('expenses.export-large-paid', [
            'payment_method' => 'cash',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('EXP-LP-CASH-001', $content);

        $this->assertStringNotContainsString('EXP-LP-BANK-001', $content);
        $this->assertStringNotContainsString('EXP-LU-CASH-001', $content);
        $this->assertStringNotContainsString('EXP-SP-CASH-001', $content);
    }

    public function test_large_unpaid_export_respects_payment_method_filter(): void
    {
        $this->actingAsOwner();

        DB::table('expenses')->delete();

        $this->insertExpense([
            'code' => 'EXP-LU-CASH-001',
            'description' => 'Large unpaid cash expense',
            'amount' => 1500,
            'payment_method' => 'cash',
            'is_paid' => false,
        ]);

        $this->insertExpense([
            'code' => 'EXP-LU-BANK-001',
            'description' => 'Large unpaid bank transfer expense',
            'amount' => 2500,
            'payment_method' => 'bank_transfer',
            'is_paid' => false,
        ]);

        $this->insertExpense([
            'code' => 'EXP-LP-CASH-001',
            'description' => 'Large paid cash expense',
            'amount' => 3000,
            'payment_method' => 'cash',
            'is_paid' => true,
        ]);

        $this->insertExpense([
            'code' => 'EXP-SU-CASH-001',
            'description' => 'Small unpaid cash expense',
            'amount' => 500,
            'payment_method' => 'cash',
            'is_paid' => false,
        ]);

        $response = $this->get(route('expenses.export-large-unpaid', [
            'payment_method' => 'cash',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('EXP-LU-CASH-001', $content);

        $this->assertStringNotContainsString('EXP-LU-BANK-001', $content);
        $this->assertStringNotContainsString('EXP-LP-CASH-001', $content);
        $this->assertStringNotContainsString('EXP-SU-CASH-001', $content);
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

    private function actingAsOwner(): void
    {
        $user = User::query()->firstOrFail();

        $this->actingAs($user);
    }
}
