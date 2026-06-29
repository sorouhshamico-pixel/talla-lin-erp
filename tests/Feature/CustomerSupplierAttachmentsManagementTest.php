<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PartyAttachment;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerSupplierAttachmentsManagementTest extends TestCase
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
            'name' => 'Owner Attachments Test',
            'email' => 'owner-attachments-test@example.com',
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
            'name' => 'شركة اختبار المرفقات',
            'commercial_name' => 'شركة اختبار المرفقات',
            'email' => 'company-attachments-test@example.com',
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
            'name' => 'فرع اختبار المرفقات',
            'code' => 'ATTACHMENTS-TEST',
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
            'name' => 'عميل اختبار المرفقات',
            'phone' => '0559200001',
            'email' => 'customer-attachments@example.com',
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
            'name' => 'مورد اختبار المرفقات',
            'phone' => '0569200001',
            'email' => 'supplier-attachments@example.com',
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

    public function test_customer_attachment_can_be_uploaded_and_displayed(): void
    {
        Storage::fake('local');

        $this->signIn();

        $customer = $this->createCustomer();

        $response = $this->post(route('customers.attachments.store', $customer), [
            'attachment' => UploadedFile::fake()->create('customer-contract.pdf', 20, 'application/pdf'),
        ]);

        $response->assertRedirect(route('customers.show', $customer));

        $this->assertDatabaseHas('party_attachments', [
            'customer_id' => $customer->id,
            'original_name' => 'customer-contract.pdf',
        ]);

        $attachment = PartyAttachment::query()->where('customer_id', $customer->id)->firstOrFail();

        Storage::disk('local')->assertExists($attachment->path);

        $showResponse = $this->get(route('customers.show', $customer));

        $showResponse->assertOk();
        $showResponse->assertSee('customer-contract.pdf');
    }

    public function test_supplier_attachment_can_be_uploaded_and_displayed(): void
    {
        Storage::fake('local');

        $this->signIn();

        $supplier = $this->createSupplier();

        $response = $this->post(route('suppliers.attachments.store', $supplier), [
            'attachment' => UploadedFile::fake()->create('supplier-profile.pdf', 20, 'application/pdf'),
        ]);

        $response->assertRedirect(route('suppliers.show', $supplier));

        $this->assertDatabaseHas('party_attachments', [
            'supplier_id' => $supplier->id,
            'original_name' => 'supplier-profile.pdf',
        ]);

        $attachment = PartyAttachment::query()->where('supplier_id', $supplier->id)->firstOrFail();

        Storage::disk('local')->assertExists($attachment->path);

        $showResponse = $this->get(route('suppliers.show', $supplier));

        $showResponse->assertOk();
        $showResponse->assertSee('supplier-profile.pdf');
    }

    public function test_customer_attachment_can_be_downloaded(): void
    {
        Storage::fake('local');

        $this->signIn();

        $customer = $this->createCustomer();

        Storage::disk('local')->put('party-attachments/customers/test-file.txt', 'customer attachment content');

        $attachment = PartyAttachment::query()->create([
            'customer_id' => $customer->id,
            'original_name' => 'customer-file.txt',
            'path' => 'party-attachments/customers/test-file.txt',
            'mime_type' => 'text/plain',
            'size' => 27,
        ]);

        $response = $this->get(route('customers.attachments.download', [$customer, $attachment]));

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }

    public function test_supplier_attachment_can_be_downloaded(): void
    {
        Storage::fake('local');

        $this->signIn();

        $supplier = $this->createSupplier();

        Storage::disk('local')->put('party-attachments/suppliers/test-file.txt', 'supplier attachment content');

        $attachment = PartyAttachment::query()->create([
            'supplier_id' => $supplier->id,
            'original_name' => 'supplier-file.txt',
            'path' => 'party-attachments/suppliers/test-file.txt',
            'mime_type' => 'text/plain',
            'size' => 27,
        ]);

        $response = $this->get(route('suppliers.attachments.download', [$supplier, $attachment]));

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }

    public function test_customer_attachment_can_be_deleted(): void
    {
        Storage::fake('local');

        $this->signIn();

        $customer = $this->createCustomer();

        Storage::disk('local')->put('party-attachments/customers/delete-file.txt', 'delete me');

        $attachment = PartyAttachment::query()->create([
            'customer_id' => $customer->id,
            'original_name' => 'delete-customer-file.txt',
            'path' => 'party-attachments/customers/delete-file.txt',
            'mime_type' => 'text/plain',
            'size' => 9,
        ]);

        $response = $this->delete(route('customers.attachments.destroy', [$customer, $attachment]));

        $response->assertRedirect(route('customers.show', $customer));

        $this->assertDatabaseMissing('party_attachments', [
            'id' => $attachment->id,
        ]);

        Storage::disk('local')->assertMissing('party-attachments/customers/delete-file.txt');
    }

    public function test_supplier_attachment_can_be_deleted(): void
    {
        Storage::fake('local');

        $this->signIn();

        $supplier = $this->createSupplier();

        Storage::disk('local')->put('party-attachments/suppliers/delete-file.txt', 'delete me');

        $attachment = PartyAttachment::query()->create([
            'supplier_id' => $supplier->id,
            'original_name' => 'delete-supplier-file.txt',
            'path' => 'party-attachments/suppliers/delete-file.txt',
            'mime_type' => 'text/plain',
            'size' => 9,
        ]);

        $response = $this->delete(route('suppliers.attachments.destroy', [$supplier, $attachment]));

        $response->assertRedirect(route('suppliers.show', $supplier));

        $this->assertDatabaseMissing('party_attachments', [
            'id' => $attachment->id,
        ]);

        Storage::disk('local')->assertMissing('party-attachments/suppliers/delete-file.txt');
    }

    public function test_customer_and_supplier_show_pages_have_attachment_forms(): void
    {
        $this->signIn();

        $customer = $this->createCustomer();
        $supplier = $this->createSupplier();

        $customerResponse = $this->get(route('customers.show', $customer));
        $supplierResponse = $this->get(route('suppliers.show', $supplier));

        $customerResponse->assertOk();
        $customerResponse->assertSee(route('customers.attachments.store', $customer), false);
        $customerResponse->assertSee('data-testid="customers-attachment-form"', false);

        $supplierResponse->assertOk();
        $supplierResponse->assertSee(route('suppliers.attachments.store', $supplier), false);
        $supplierResponse->assertSee('data-testid="suppliers-attachment-form"', false);
    }
}
