<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SupplierEditUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): User
    {
        $user = $this->createTestUser();

        $this->actingAs($user);

        return $user;
    }

    private function createTestUser(): User
    {
        $columns = Schema::getColumnListing('users');

        $companyId = in_array('company_id', $columns, true)
            ? $this->createCompanyId()
            : null;

        $branchId = in_array('branch_id', $columns, true)
            ? $this->createBranchId($companyId)
            : null;

        $data = [
            'name' => 'Owner Test User',
            'email' => 'owner-supplier-edit-test@example.com',
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

        foreach (DB::select('PRAGMA table_info(users)') as $column) {
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

            $data[$column->name] = match (true) {
                str_contains($column->name, 'email') => 'required-user@example.com',
                str_contains($column->name, 'password') => Hash::make('password'),
                str_contains($column->name, 'active') => true,
                str_contains($column->name, 'role') => 'owner',
                str_contains($column->name, 'type') => 'owner',
                str_contains($column->name, 'date') => now()->toDateString(),
                str_contains(strtoupper($column->type), 'INT') => 1,
                default => 'اختبار',
            };
        }

        $data = array_intersect_key($data, array_flip($columns));

        return User::unguarded(fn () => User::query()->create($data));
    }

    private function createCompanyId(): ?int
    {
        if (! Schema::hasTable('companies')) {
            return null;
        }

        $columns = Schema::getColumnListing('companies');

        $existing = DB::table('companies')->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $data = [
            'name' => 'شركة اختبار الموردين',
            'commercial_name' => 'شركة اختبار الموردين',
            'email' => 'company-supplier-test@example.com',
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

        foreach (DB::select('PRAGMA table_info(companies)') as $column) {
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

            $data[$column->name] = match (true) {
                str_contains($column->name, 'email') => 'company-required@example.com',
                str_contains($column->name, 'phone') => '0500000000',
                str_contains($column->name, 'active') => true,
                str_contains($column->name, 'date') => now()->toDateString(),
                str_contains(strtoupper($column->type), 'INT') => 1,
                default => 'اختبار',
            };
        }

        $data = array_intersect_key($data, array_flip($columns));

        return (int) DB::table('companies')->insertGetId($data);
    }

    private function createBranchId(?int $companyId = null): ?int
    {
        if (! Schema::hasTable('branches')) {
            return null;
        }

        $columns = Schema::getColumnListing('branches');

        $existing = DB::table('branches')->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $data = [
            'name' => 'فرع اختبار الموردين',
            'code' => 'SUP-EDIT-TEST',
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

        foreach (DB::select('PRAGMA table_info(branches)') as $column) {
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

            $data[$column->name] = match (true) {
                str_contains($column->name, 'company_id') => $companyId ?? $this->createCompanyId(),
                str_contains($column->name, 'email') => 'branch-required@example.com',
                str_contains($column->name, 'phone') => '0500000000',
                str_contains($column->name, 'active') => true,
                str_contains($column->name, 'date') => now()->toDateString(),
                str_contains(strtoupper($column->type), 'INT') => 1,
                default => 'اختبار',
            };
        }

        $data = array_intersect_key($data, array_flip($columns));

        return (int) DB::table('branches')->insertGetId($data);
    }

    private function createSupplier(array $overrides = []): Supplier
    {
        $columns = Schema::getColumnListing('suppliers');

        $data = [
            'name' => 'مورد تجريبي',
            'contact_name' => 'مسؤول المورد',
            'contact_person' => 'مسؤول المورد',
            'phone' => '0500000000',
            'email' => 'supplier@example.com',
            'vat_number' => '300000000000003',
            'tax_number' => '300000000000003',
            'commercial_registration' => '1010000000',
            'address' => 'الرياض',
            'city' => 'الرياض',
            'notes' => 'مورد خاص بالاختبار',
            'is_active' => true,
        ];

        if (in_array('company_id', $columns, true)) {
            $data['company_id'] = $this->createCompanyId();
        }

        if (in_array('branch_id', $columns, true)) {
            $data['branch_id'] = $this->createBranchId($data['company_id'] ?? null);
        }

        $data = array_intersect_key($data, array_flip($columns));
        $data = array_merge($data, $overrides);

        return Supplier::unguarded(fn () => Supplier::query()->create($data));
    }

    private function updatePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'مورد تجريبي محدث',
            'contact_name' => 'مسؤول محدث',
            'contact_person' => 'مسؤول محدث',
            'phone' => '0511111111',
            'email' => 'supplier-updated@example.com',
            'vat_number' => '300000000000004',
            'tax_number' => '300000000000004',
            'commercial_registration' => '1010000001',
            'address' => 'الرياض - حي القيروان',
            'city' => 'الرياض',
            'notes' => 'تم تحديث بيانات المورد',
            'is_active' => '0',
        ], $overrides);
    }

    public function test_supplier_edit_page_can_be_opened(): void
    {
        $this->signIn();

        $supplier = $this->createSupplier();

        $response = $this->get(route('suppliers.edit', $supplier));

        $response->assertOk();
        $response->assertSee($supplier->name);
        $response->assertSee(route('suppliers.update', $supplier), false);
    }

    public function test_supplier_can_be_updated(): void
    {
        $this->signIn();

        $supplier = $this->createSupplier();

        $response = $this->put(route('suppliers.update', $supplier), $this->updatePayload());

        $response->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'مورد تجريبي محدث',
            'is_active' => false,
        ]);
    }

    public function test_supplier_name_is_required_when_updating(): void
    {
        $this->signIn();

        $supplier = $this->createSupplier();

        $response = $this
            ->from(route('suppliers.edit', $supplier))
            ->put(route('suppliers.update', $supplier), $this->updatePayload([
                'name' => '',
            ]));

        $response->assertRedirect(route('suppliers.edit', $supplier));
        $response->assertSessionHasErrors('name');
    }

    public function test_suppliers_index_has_edit_link(): void
    {
        $this->signIn();

        $supplier = $this->createSupplier();

        $response = $this->get(route('suppliers.index'));

        $response->assertOk();
        $response->assertSee(route('suppliers.edit', $supplier), false);
    }
}
