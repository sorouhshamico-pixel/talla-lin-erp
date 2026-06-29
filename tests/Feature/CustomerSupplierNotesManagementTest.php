<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PartyNote;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerSupplierNotesManagementTest extends TestCase
{
    use RefreshDatabase;

    private ?int $currentCompanyId = null;
    private ?int $currentBranchId = null;

    private function signIn(): User
    {
        $companyId = $this->createCompanyId();
        $branchId = $this->createBranchId($companyId);

        $this->currentCompanyId = $companyId;
        $this->currentBranchId = $branchId;

        $user = $this->createTestUser($companyId, $branchId);

        $this->actingAs($user);

        return $user;
    }

    private function createTestUser(?int $companyId = null, ?int $branchId = null): User
    {
        $columns = Schema::getColumnListing('users');

        $data = [
            'name' => 'Owner Notes Test',
            'email' => 'owner-notes-test@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ];

        if (in_array('company_id', $columns, true)) {
            $data['company_id'] = $companyId ?? $this->createCompanyId();
        }

        if (in_array('branch_id', $columns, true)) {
            $data['branch_id'] = $branchId ?? $this->createBranchId($data['company_id'] ?? null);
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

        if ($this->currentCompanyId) {
            return $this->currentCompanyId;
        }

        $existing = DB::table('companies')->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $columns = Schema::getColumnListing('companies');

        $data = [
            'name' => 'شركة اختبار الملاحظات',
            'commercial_name' => 'شركة اختبار الملاحظات',
            'email' => 'company-notes-test@example.com',
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

    private function createBranchId(?int $companyId = null): ?int
    {
        if (! Schema::hasTable('branches')) {
            return null;
        }

        if ($this->currentBranchId) {
            return $this->currentBranchId;
        }

        $existing = DB::table('branches')->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $columns = Schema::getColumnListing('branches');

        $data = [
            'name' => 'فرع اختبار الملاحظات',
            'code' => 'NOTES-TEST',
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

        return (int) DB::table('branches')->insertGetId($data);
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
                str_contains($columnName, 'branch_id') => $this->createBranchId($data['company_id'] ?? null),
                str_contains($columnName, 'email') => $table . '-required@example.com',
                str_contains($columnName, 'password') => Hash::make('password'),
                str_contains($columnName, 'phone') => '0500000000',
                str_contains($columnName, 'active') => true,
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

    private function createCustomer(array $overrides = []): Customer
    {
        $columns = Schema::getColumnListing('customers');

        $data = [
            'name' => 'عميل اختبار الملاحظات',
            'phone' => '0559100001',
            'email' => 'customer-notes@example.com',
            'city' => 'الرياض',
            'is_active' => true,
        ];

        if (in_array('company_id', $columns, true)) {
            $data['company_id'] = $this->currentCompanyId ?? $this->createCompanyId();
        }

        if (in_array('branch_id', $columns, true)) {
            $data['branch_id'] = $this->currentBranchId ?? $this->createBranchId($data['company_id'] ?? null);
        }

        if (in_array('created_at', $columns, true)) {
            $data['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true)) {
            $data['updated_at'] = now();
        }

        $data = $this->fillRequiredColumns('customers', $data);
        $data = array_intersect_key($data, array_flip($columns));
        $data = array_merge($data, $overrides);

        return Customer::unguarded(fn () => Customer::query()->create($data));
    }

    private function createSupplier(array $overrides = []): Supplier
    {
        $columns = Schema::getColumnListing('suppliers');

        $data = [
            'name' => 'مورد اختبار الملاحظات',
            'phone' => '0569100001',
            'email' => 'supplier-notes@example.com',
            'city' => 'الرياض',
            'is_active' => true,
        ];

        if (in_array('company_id', $columns, true)) {
            $data['company_id'] = $this->currentCompanyId ?? $this->createCompanyId();
        }

        if (in_array('branch_id', $columns, true)) {
            $data['branch_id'] = $this->currentBranchId ?? $this->createBranchId($data['company_id'] ?? null);
        }

        if (in_array('created_at', $columns, true)) {
            $data['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true)) {
            $data['updated_at'] = now();
        }

        $data = $this->fillRequiredColumns('suppliers', $data);
        $data = array_intersect_key($data, array_flip($columns));
        $data = array_merge($data, $overrides);

        return Supplier::unguarded(fn () => Supplier::query()->create($data));
    }

    public function test_customer_note_can_be_created_and_displayed(): void
    {
        $this->signIn();

        $customer = $this->createCustomer();

        $response = $this->post(route('customers.notes.store', $customer), [
            'note' => 'هذه ملاحظة مهمة للعميل.',
        ]);

        $response->assertRedirect(route('customers.show', $customer));

        $this->assertDatabaseHas('party_notes', [
            'customer_id' => $customer->id,
            'note' => 'هذه ملاحظة مهمة للعميل.',
        ]);

        $showResponse = $this->get(route('customers.show', $customer));

        $showResponse->assertOk();
        $showResponse->assertSee('هذه ملاحظة مهمة للعميل.');
    }

    public function test_supplier_note_can_be_created_and_displayed(): void
    {
        $this->signIn();

        $supplier = $this->createSupplier();

        $response = $this->post(route('suppliers.notes.store', $supplier), [
            'note' => 'هذه ملاحظة مهمة للمورد.',
        ]);

        $response->assertRedirect(route('suppliers.show', $supplier));

        $this->assertDatabaseHas('party_notes', [
            'supplier_id' => $supplier->id,
            'note' => 'هذه ملاحظة مهمة للمورد.',
        ]);

        $showResponse = $this->get(route('suppliers.show', $supplier));

        $showResponse->assertOk();
        $showResponse->assertSee('هذه ملاحظة مهمة للمورد.');
    }

    public function test_customer_note_requires_text(): void
    {
        $this->signIn();

        $customer = $this->createCustomer();

        $response = $this
            ->from(route('customers.show', $customer))
            ->post(route('customers.notes.store', $customer), [
                'note' => '',
            ]);

        $response->assertRedirect(route('customers.show', $customer));
        $response->assertSessionHasErrors('note');
    }

    public function test_supplier_note_requires_text(): void
    {
        $this->signIn();

        $supplier = $this->createSupplier();

        $response = $this
            ->from(route('suppliers.show', $supplier))
            ->post(route('suppliers.notes.store', $supplier), [
                'note' => '',
            ]);

        $response->assertRedirect(route('suppliers.show', $supplier));
        $response->assertSessionHasErrors('note');
    }

    public function test_customer_note_can_be_deleted(): void
    {
        $this->signIn();

        $customer = $this->createCustomer();

        $note = PartyNote::query()->create([
            'customer_id' => $customer->id,
            'note' => 'ملاحظة سيتم حذفها للعميل.',
        ]);

        $response = $this->delete(route('customers.notes.destroy', [$customer, $note]));

        $response->assertRedirect(route('customers.show', $customer));

        $this->assertDatabaseMissing('party_notes', [
            'id' => $note->id,
        ]);
    }

    public function test_supplier_note_can_be_deleted(): void
    {
        $this->signIn();

        $supplier = $this->createSupplier();

        $note = PartyNote::query()->create([
            'supplier_id' => $supplier->id,
            'note' => 'ملاحظة سيتم حذفها للمورد.',
        ]);

        $response = $this->delete(route('suppliers.notes.destroy', [$supplier, $note]));

        $response->assertRedirect(route('suppliers.show', $supplier));

        $this->assertDatabaseMissing('party_notes', [
            'id' => $note->id,
        ]);
    }

    public function test_customer_and_supplier_show_pages_have_note_forms(): void
    {
        $this->signIn();

        $customer = $this->createCustomer();
        $supplier = $this->createSupplier();

        $customerResponse = $this->get(route('customers.show', $customer));
        $supplierResponse = $this->get(route('suppliers.show', $supplier));

        $customerResponse->assertOk();
        $customerResponse->assertSee(route('customers.notes.store', $customer), false);
        $customerResponse->assertSee('data-testid="customers-note-form"', false);

        $supplierResponse->assertOk();
        $supplierResponse->assertSee(route('suppliers.notes.store', $supplier), false);
        $supplierResponse->assertSee('data-testid="suppliers-note-form"', false);
    }
}
