<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PartyTag;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerSupplierClassificationTest extends TestCase
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
            'name' => 'Owner Classification Test',
            'email' => 'owner-classification-test@example.com',
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
            'name' => 'شركة اختبار التصنيفات',
            'commercial_name' => 'شركة اختبار التصنيفات',
            'email' => 'company-classification-test@example.com',
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
            'name' => 'فرع اختبار التصنيفات',
            'code' => 'CLASSIFICATION-TEST',
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
                str_contains($columnName, 'user_id') => 1,
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
            'name' => 'عميل التصنيف',
            'phone' => '0559900001',
            'email' => 'customer-classification@example.com',
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
            'name' => 'مورد التصنيف',
            'phone' => '0569900001',
            'email' => 'supplier-classification@example.com',
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

    public function test_party_tags_can_be_created_and_listed(): void
    {
        $this->signIn();

        $response = $this->post(route('party-tags.store'), [
            'name' => 'عميل مهم',
            'applies_to' => 'customer',
            'description' => 'تصنيف للعملاء المهمين',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('party-tags.index'));

        $this->assertDatabaseHas('party_tags', [
            'name' => 'عميل مهم',
            'applies_to' => 'customer',
            'description' => 'تصنيف للعملاء المهمين',
            'is_active' => true,
        ]);

        $indexResponse = $this->get(route('party-tags.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee('تصنيفات العملاء والموردين');
        $indexResponse->assertSee('عميل مهم');
        $indexResponse->assertSee('data-testid="party-tags-table"', false);
    }

    public function test_customer_and_supplier_classifications_can_be_assigned(): void
    {
        $this->signIn();

        $customerTag = PartyTag::query()->create([
            'name' => 'عميل محتمل',
            'slug' => 'customer-prospect',
            'applies_to' => 'customer',
            'is_active' => true,
        ]);

        $supplierTag = PartyTag::query()->create([
            'name' => 'مورد رئيسي',
            'slug' => 'main-supplier',
            'applies_to' => 'supplier',
            'is_active' => true,
        ]);

        $customer = $this->createCustomer();
        $supplier = $this->createSupplier();

        $customerResponse = $this->post(route('customers.classification.update', $customer), [
            'party_tag_id' => $customerTag->id,
        ]);

        $supplierResponse = $this->post(route('suppliers.classification.update', $supplier), [
            'party_tag_id' => $supplierTag->id,
        ]);

        $customerResponse->assertRedirect(route('customers.show', $customer));
        $supplierResponse->assertRedirect(route('suppliers.show', $supplier));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'party_tag_id' => $customerTag->id,
        ]);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'party_tag_id' => $supplierTag->id,
        ]);
    }

    public function test_customer_and_supplier_show_pages_display_classification_cards(): void
    {
        $this->signIn();

        $sharedTag = PartyTag::query()->create([
            'name' => 'يحتاج متابعة',
            'slug' => 'needs-follow-up',
            'applies_to' => 'both',
            'is_active' => true,
        ]);

        $customer = $this->createCustomer(['party_tag_id' => $sharedTag->id]);
        $supplier = $this->createSupplier(['party_tag_id' => $sharedTag->id]);

        $customerResponse = $this->get(route('customers.show', $customer));
        $supplierResponse = $this->get(route('suppliers.show', $supplier));

        $customerResponse->assertOk();
        $customerResponse->assertSee('تصنيف العميل');
        $customerResponse->assertSee('يحتاج متابعة');
        $customerResponse->assertSee('data-testid="customers-classification-card"', false);
        $customerResponse->assertSee(route('customers.classification.update', $customer), false);

        $supplierResponse->assertOk();
        $supplierResponse->assertSee('تصنيف المورد');
        $supplierResponse->assertSee('يحتاج متابعة');
        $supplierResponse->assertSee('data-testid="suppliers-classification-card"', false);
        $supplierResponse->assertSee(route('suppliers.classification.update', $supplier), false);
    }

    public function test_party_tag_show_lists_related_customers_and_suppliers(): void
    {
        $this->signIn();

        $tag = PartyTag::query()->create([
            'name' => 'تصنيف مشترك',
            'slug' => 'shared-tag',
            'applies_to' => 'both',
            'is_active' => true,
        ]);

        $customer = $this->createCustomer([
            'name' => 'عميل مرتبط بالتصنيف',
            'party_tag_id' => $tag->id,
        ]);

        $supplier = $this->createSupplier([
            'name' => 'مورد مرتبط بالتصنيف',
            'party_tag_id' => $tag->id,
        ]);

        $response = $this->get(route('party-tags.show', $tag));

        $response->assertOk();
        $response->assertSee('تفاصيل التصنيف');
        $response->assertSee('تصنيف مشترك');
        $response->assertSee('عميل مرتبط بالتصنيف');
        $response->assertSee('مورد مرتبط بالتصنيف');
        $response->assertSee(route('customers.show', $customer), false);
        $response->assertSee(route('suppliers.show', $supplier), false);
    }

    public function test_party_tag_can_be_toggled_active(): void
    {
        $this->signIn();

        $tag = PartyTag::query()->create([
            'name' => 'تصنيف مؤقت',
            'slug' => 'temporary-tag',
            'applies_to' => 'both',
            'is_active' => true,
        ]);

        $response = $this->post(route('party-tags.toggle-active', $tag));

        $response->assertRedirect(route('party-tags.index'));

        $this->assertFalse($tag->fresh()->is_active);
    }
}
