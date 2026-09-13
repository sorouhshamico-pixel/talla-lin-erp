<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SupplierStatementCsvTotalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_statement_csv_includes_purchase_invoice_row_and_totals(): void
    {
        $this->assertTrue(Schema::hasTable('purchase_invoices'));

        $companyId = $this->createCompanyId();
        $branch = $this->createBranch($companyId);
        $warehouse = $this->createWarehouse($companyId, $branch->id);
        $supplier = $this->createSupplier($companyId, $branch->id);
        $user = $this->createUser($companyId, $branch->id);

        $this->insertPurchaseInvoice($companyId, $branch->id, $warehouse->id, $supplier->id, [
            'invoice_number' => 'PINV-SUP-CSV-001',
            'amount' => 1250,
            'invoice_date' => '2026-07-05',
        ]);

        $response = $this->actingAs($user)
            ->get(route('suppliers.statement.export', $supplier));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $response->assertSee('"date","type","description","status","debit","credit","balance"', false);
        $response->assertSee('"2026-07-05","فاتورة شراء","فاتورة شراء رقم PINV-SUP-CSV-001","unpaid","1250.00","0.00","1250.00"', false);
        $response->assertSee('"summary","total_debit","","","1250.00","",""', false);
        $response->assertSee('"summary","total_credit","","","","0.00",""', false);
        $response->assertSee('"summary","balance","","","","","1250.00"', false);
    }

    private function createUser(?int $companyId, ?int $branchId): User
    {
        $columns = Schema::getColumnListing('users');

        $data = [
            'name' => 'Supplier Statement CSV User',
            'email' => 'supplier-statement-csv@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ];

        if (in_array('company_id', $columns, true)) {
            $data['company_id'] = $companyId;
        }

        if (in_array('branch_id', $columns, true)) {
            $data['branch_id'] = $branchId;
        }

        foreach (['role', 'type', 'user_type'] as $field) {
            if (in_array($field, $columns, true)) {
                $data[$field] = 'owner';
            }
        }

        foreach (['is_active', 'active'] as $field) {
            if (in_array($field, $columns, true)) {
                $data[$field] = true;
            }
        }

        if (in_array('created_at', $columns, true)) {
            $data['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true)) {
            $data['updated_at'] = now();
        }

        $data = $this->fillRequiredColumns('users', $data);
        $data = array_intersect_key($data, array_flip($columns));

        return User::unguarded(fn () => User::query()->create($data));
    }

    private function createCompanyId(): ?int
    {
        if (! Schema::hasTable('companies')) {
            return null;
        }

        $existing = DB::table('companies')->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $columns = Schema::getColumnListing('companies');

        $data = [
            'name' => 'شركة اختبار CSV كشف حساب المورد',
            'commercial_name' => 'شركة اختبار CSV كشف حساب المورد',
            'email' => 'supplier-statement-csv-company@example.com',
            'phone' => '0500000000',
            'tax_number' => '300000000000001',
            'vat_number' => '300000000000001',
            'commercial_registration' => '1010000000',
            'address' => 'الرياض',
            'city' => 'الرياض',
            'is_active' => true,
        ];

        if (in_array('created_at', $columns, true)) {
            $data['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true)) {
            $data['updated_at'] = now();
        }

        $data = $this->fillRequiredColumns('companies', $data);
        $data = array_intersect_key($data, array_flip($columns));

        return (int) DB::table('companies')->insertGetId($data);
    }

    private function createBranch(?int $companyId): Branch
    {
        $columns = Schema::getColumnListing('branches');

        $data = [
            'name' => 'فرع اختبار CSV كشف حساب المورد',
            'code' => 'SUP-STMT-CSV',
            'city' => 'الرياض',
            'address' => 'الرياض',
            'phone' => '0500000000',
            'is_active' => true,
        ];

        if ($companyId && in_array('company_id', $columns, true)) {
            $data['company_id'] = $companyId;
        }

        if (in_array('created_at', $columns, true)) {
            $data['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true)) {
            $data['updated_at'] = now();
        }

        $data = $this->fillRequiredColumns('branches', $data);
        $data = array_intersect_key($data, array_flip($columns));

        return Branch::unguarded(fn () => Branch::query()->create($data));
    }

    private function createWarehouse(?int $companyId, int $branchId): Warehouse
    {
        $columns = Schema::getColumnListing('warehouses');

        $data = [
            'name' => 'مستودع اختبار CSV كشف حساب المورد',
            'code' => 'SUP-STMT-CSV-WH',
            'city' => 'الرياض',
            'address' => 'الرياض',
            'is_active' => true,
        ];

        if ($companyId && in_array('company_id', $columns, true)) {
            $data['company_id'] = $companyId;
        }

        if (in_array('branch_id', $columns, true)) {
            $data['branch_id'] = $branchId;
        }

        if (in_array('created_at', $columns, true)) {
            $data['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true)) {
            $data['updated_at'] = now();
        }

        $data = $this->fillRequiredColumns('warehouses', $data);
        $data = array_intersect_key($data, array_flip($columns));

        return Warehouse::unguarded(fn () => Warehouse::query()->create($data));
    }

    private function createSupplier(?int $companyId, ?int $branchId): Supplier
    {
        $columns = Schema::getColumnListing('suppliers');

        $data = [
            'name' => 'مورد CSV كشف الحساب',
            'phone' => '0569800077',
            'email' => 'supplier-statement-csv@example.com',
            'city' => 'الرياض',
            'is_active' => true,
        ];

        if (in_array('company_id', $columns, true)) {
            $data['company_id'] = $companyId;
        }

        if (in_array('branch_id', $columns, true)) {
            $data['branch_id'] = $branchId;
        }

        if (in_array('created_at', $columns, true)) {
            $data['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true)) {
            $data['updated_at'] = now();
        }

        $data = $this->fillRequiredColumns('suppliers', $data);
        $data = array_intersect_key($data, array_flip($columns));

        return Supplier::unguarded(fn () => Supplier::query()->create($data));
    }

    private function insertPurchaseInvoice(?int $companyId, int $branchId, int $warehouseId, int $supplierId, array $overrides): void
    {
        $columns = Schema::getColumnListing('purchase_invoices');

        $data = [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'warehouse_id' => $warehouseId,
            'supplier_id' => $supplierId,
            'user_id' => DB::table('users')->value('id'),
            'invoice_number' => $overrides['invoice_number'],
            'status' => 'received',
            'payment_status' => 'unpaid',
            'currency' => 'SAR',
            'subtotal' => $overrides['amount'],
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => $overrides['amount'],
            'paid_amount' => 0,
            'remaining_amount' => $overrides['amount'],
            'invoice_date' => $overrides['invoice_date'],
            'due_at' => null,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $data = $this->fillRequiredColumns('purchase_invoices', $data);
        $data = array_intersect_key($data, array_flip($columns));

        DB::table('purchase_invoices')->insert($data);
    }

    private function fillRequiredColumns(string $table, array $data): array
    {
        foreach (DB::select("PRAGMA table_info({$table})") as $column) {
            if ((int) $column->pk === 1) {
                continue;
            }

            if ((int) $column->notnull !== 1) {
                continue;
            }

            if ($column->dflt_value !== null) {
                continue;
            }

            if (array_key_exists($column->name, $data)) {
                continue;
            }

            $columnName = strtolower($column->name);
            $columnType = strtoupper((string) $column->type);

            $data[$column->name] = match (true) {
                str_contains($columnName, 'company_id') => $this->createCompanyId(),
                str_contains($columnName, 'branch_id') => 1,
                str_contains($columnName, 'warehouse_id') => 1,
                str_contains($columnName, 'supplier_id') => 1,
                str_contains($columnName, 'user_id') => 1,
                str_contains($columnName, 'email') => $table . '-required@example.com',
                str_contains($columnName, 'phone') => '0500000000',
                str_contains($columnName, 'active') => true,
                str_contains($columnName, 'paid') => false,
                str_contains($columnName, 'role') => 'owner',
                str_contains($columnName, 'type') => 'owner',
                str_contains($columnName, 'date') => now()->toDateString(),
                str_contains($columnType, 'INT') => 1,
                str_contains($columnType, 'REAL') => 1,
                str_contains($columnType, 'NUM') => 1,
                default => 'اختبار',
            };
        }

        return $data;
    }
}
