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

class CustomerSupplierContactLogsTest extends TestCase
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
            'name' => 'Owner Contact Logs Test',
            'email' => 'owner-contact-logs-test@example.com',
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
            'name' => 'شركة اختبار سجل التواصل',
            'commercial_name' => 'شركة اختبار سجل التواصل',
            'email' => 'company-contact-logs-test@example.com',
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
            'name' => 'فرع اختبار سجل التواصل',
            'code' => 'CONTACT-LOGS-TEST',
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
            'name' => 'عميل اختبار سجل التواصل',
            'phone' => '0559300001',
            'email' => 'customer-contact-logs@example.com',
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
            'name' => 'مورد اختبار سجل التواصل',
            'phone' => '0569300001',
            'email' => 'supplier-contact-logs@example.com',
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

    public function test_customer_contact_log_can_be_created_and_displayed(): void
    {
        $this->signIn();

        $customer = $this->createCustomer();

        $response = $this->post(route('customers.contact-logs.store', $customer), [
            'contact_type' => 'whatsapp',
            'summary' => 'تم التواصل مع العميل بخصوص عرض السعر.',
            'contacted_at' => '2026-06-29',
            'follow_up_at' => '2026-07-02',
        ]);

        $response->assertRedirect(route('customers.show', $customer));

        $this->assertDatabaseHas('party_contact_logs', [
            'customer_id' => $customer->id,
            'contact_type' => 'whatsapp',
            'summary' => 'تم التواصل مع العميل بخصوص عرض السعر.',
        ]);

        $showResponse = $this->get(route('customers.show', $customer));

        $showResponse->assertOk();
        $showResponse->assertSee('تم التواصل مع العميل بخصوص عرض السعر.');
    }

    public function test_supplier_contact_log_can_be_created_and_displayed(): void
    {
        $this->signIn();

        $supplier = $this->createSupplier();

        $response = $this->post(route('suppliers.contact-logs.store', $supplier), [
            'contact_type' => 'call',
            'summary' => 'تم التواصل مع المورد لتأكيد الأسعار.',
            'contacted_at' => '2026-06-29',
            'follow_up_at' => '2026-07-03',
        ]);

        $response->assertRedirect(route('suppliers.show', $supplier));

        $this->assertDatabaseHas('party_contact_logs', [
            'supplier_id' => $supplier->id,
            'contact_type' => 'call',
            'summary' => 'تم التواصل مع المورد لتأكيد الأسعار.',
        ]);

        $showResponse = $this->get(route('suppliers.show', $supplier));

        $showResponse->assertOk();
        $showResponse->assertSee('تم التواصل مع المورد لتأكيد الأسعار.');
    }

    public function test_contact_log_summary_is_required(): void
    {
        $this->signIn();

        $customer = $this->createCustomer();

        $response = $this
            ->from(route('customers.show', $customer))
            ->post(route('customers.contact-logs.store', $customer), [
                'contact_type' => 'call',
                'summary' => '',
                'contacted_at' => '2026-06-29',
            ]);

        $response->assertRedirect(route('customers.show', $customer));
        $response->assertSessionHasErrors('summary');
    }

    public function test_customer_contact_log_can_be_deleted(): void
    {
        $this->signIn();

        $customer = $this->createCustomer();

        $contactLog = PartyContactLog::query()->create([
            'customer_id' => $customer->id,
            'contact_type' => 'email',
            'summary' => 'سجل سيتم حذفه للعميل.',
            'contacted_at' => '2026-06-29',
        ]);

        $response = $this->delete(route('customers.contact-logs.destroy', [$customer, $contactLog]));

        $response->assertRedirect(route('customers.show', $customer));

        $this->assertDatabaseMissing('party_contact_logs', [
            'id' => $contactLog->id,
        ]);
    }

    public function test_supplier_contact_log_can_be_deleted(): void
    {
        $this->signIn();

        $supplier = $this->createSupplier();

        $contactLog = PartyContactLog::query()->create([
            'supplier_id' => $supplier->id,
            'contact_type' => 'meeting',
            'summary' => 'سجل سيتم حذفه للمورد.',
            'contacted_at' => '2026-06-29',
        ]);

        $response = $this->delete(route('suppliers.contact-logs.destroy', [$supplier, $contactLog]));

        $response->assertRedirect(route('suppliers.show', $supplier));

        $this->assertDatabaseMissing('party_contact_logs', [
            'id' => $contactLog->id,
        ]);
    }

    public function test_customer_and_supplier_show_pages_have_contact_log_forms(): void
    {
        $this->signIn();

        $customer = $this->createCustomer();
        $supplier = $this->createSupplier();

        $customerResponse = $this->get(route('customers.show', $customer));
        $supplierResponse = $this->get(route('suppliers.show', $supplier));

        $customerResponse->assertOk();
        $customerResponse->assertSee(route('customers.contact-logs.store', $customer), false);
        $customerResponse->assertSee('data-testid="customers-contact-log-form"', false);

        $supplierResponse->assertOk();
        $supplierResponse->assertSee(route('suppliers.contact-logs.store', $supplier), false);
        $supplierResponse->assertSee('data-testid="suppliers-contact-log-form"', false);
    }
}
