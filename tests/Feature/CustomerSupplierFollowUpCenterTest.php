<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PartyContactLog;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerSupplierFollowUpCenterTest extends TestCase
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
            'name' => 'Owner Follow Up Center Test',
            'email' => 'owner-follow-up-center-test@example.com',
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
            'name' => 'شركة اختبار مركز المتابعات',
            'commercial_name' => 'شركة اختبار مركز المتابعات',
            'email' => 'company-follow-up-center-test@example.com',
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
            'name' => 'فرع اختبار مركز المتابعات',
            'code' => 'FOLLOW-UP-CENTER',
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
            'name' => 'عميل مركز المتابعات',
            'phone' => '0559400001',
            'email' => 'customer-follow-up-center@example.com',
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
            'name' => 'مورد مركز المتابعات',
            'phone' => '0569400001',
            'email' => 'supplier-follow-up-center@example.com',
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

    public function test_follow_up_center_lists_due_customer_and_supplier_follow_ups(): void
    {
        $this->signIn();

        $customer = $this->createCustomer(['name' => 'عميل متابعة مستحقة']);
        $supplier = $this->createSupplier(['name' => 'مورد متابعة مستحقة']);

        PartyContactLog::query()->create([
            'customer_id' => $customer->id,
            'contact_type' => 'call',
            'summary' => 'متابعة مستحقة للعميل',
            'contacted_at' => now()->subDays(3)->toDateString(),
            'follow_up_at' => now()->subDay()->toDateString(),
        ]);

        PartyContactLog::query()->create([
            'supplier_id' => $supplier->id,
            'contact_type' => 'whatsapp',
            'summary' => 'متابعة مستحقة للمورد',
            'contacted_at' => now()->subDays(2)->toDateString(),
            'follow_up_at' => now()->toDateString(),
        ]);

        $response = $this->get(route('party-follow-ups.index'));

        $response->assertOk();
        $response->assertSee('مركز المتابعات');
        $response->assertSee('عميل متابعة مستحقة');
        $response->assertSee('مورد متابعة مستحقة');
        $response->assertSee('متابعة مستحقة للعميل');
        $response->assertSee('متابعة مستحقة للمورد');
    }

    public function test_follow_up_center_can_filter_upcoming_follow_ups(): void
    {
        $this->signIn();

        $customer = $this->createCustomer(['name' => 'عميل متابعة قادمة']);
        $supplier = $this->createSupplier(['name' => 'مورد متابعة قديمة']);

        PartyContactLog::query()->create([
            'customer_id' => $customer->id,
            'contact_type' => 'email',
            'summary' => 'متابعة قادمة للعميل',
            'contacted_at' => now()->toDateString(),
            'follow_up_at' => now()->addDays(5)->toDateString(),
        ]);

        PartyContactLog::query()->create([
            'supplier_id' => $supplier->id,
            'contact_type' => 'meeting',
            'summary' => 'متابعة متأخرة للمورد',
            'contacted_at' => now()->subDays(4)->toDateString(),
            'follow_up_at' => now()->subDay()->toDateString(),
        ]);

        $response = $this->get(route('party-follow-ups.index', ['status' => 'upcoming']));

        $response->assertOk();
        $response->assertSee('متابعة قادمة للعميل');
        $response->assertDontSee('متابعة متأخرة للمورد');
    }

    public function test_follow_up_center_can_search_by_party_name_or_summary(): void
    {
        $this->signIn();

        $customer = $this->createCustomer(['name' => 'عميل بحث خاص']);
        $supplier = $this->createSupplier(['name' => 'مورد خارج البحث']);

        PartyContactLog::query()->create([
            'customer_id' => $customer->id,
            'contact_type' => 'call',
            'summary' => 'ملخص بحث خاص',
            'contacted_at' => now()->toDateString(),
            'follow_up_at' => now()->toDateString(),
        ]);

        PartyContactLog::query()->create([
            'supplier_id' => $supplier->id,
            'contact_type' => 'call',
            'summary' => 'ملخص غير مطلوب',
            'contacted_at' => now()->toDateString(),
            'follow_up_at' => now()->toDateString(),
        ]);

        $response = $this->get(route('party-follow-ups.index', [
            'status' => 'all',
            'q' => 'بحث خاص',
        ]));

        $response->assertOk();
        $response->assertSee('عميل بحث خاص');
        $response->assertSee('ملخص بحث خاص');
        $response->assertDontSee('مورد خارج البحث');
    }

    public function test_customer_and_supplier_indexes_link_to_follow_up_center(): void
    {
        $this->signIn();

        $customerResponse = $this->get(route('customers.index'));
        $supplierResponse = $this->get(route('suppliers.index'));

        $customerResponse->assertOk();
        $customerResponse->assertSee(route('party-follow-ups.index'), false);
        $customerResponse->assertSee('data-testid="party-follow-ups-link"', false);

        $supplierResponse->assertOk();
        $supplierResponse->assertSee(route('party-follow-ups.index'), false);
        $supplierResponse->assertSee('data-testid="party-follow-ups-link"', false);
    }
}
