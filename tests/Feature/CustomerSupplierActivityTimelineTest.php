<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PartyAttachment;
use App\Models\PartyContactLog;
use App\Models\PartyNote;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerSupplierActivityTimelineTest extends TestCase
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
            'name' => 'Owner Activity Timeline Test',
            'email' => 'owner-activity-timeline-test@example.com',
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
            'name' => 'شركة اختبار خط النشاط',
            'commercial_name' => 'شركة اختبار خط النشاط',
            'email' => 'company-activity-timeline-test@example.com',
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
            'name' => 'فرع اختبار خط النشاط',
            'code' => 'ACTIVITY-TIMELINE',
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
            'name' => 'عميل خط النشاط',
            'phone' => '0559500001',
            'email' => 'customer-activity-timeline@example.com',
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
            'name' => 'مورد خط النشاط',
            'phone' => '0569500001',
            'email' => 'supplier-activity-timeline@example.com',
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

    public function test_customer_activity_timeline_lists_notes_attachments_and_contact_logs(): void
    {
        $this->signIn();

        $customer = $this->createCustomer(['name' => 'عميل نشاط شامل']);

        PartyNote::query()->create([
            'customer_id' => $customer->id,
            'note' => 'ملاحظة خاصة بخط نشاط العميل.',
        ]);

        PartyAttachment::query()->create([
            'customer_id' => $customer->id,
            'original_name' => 'customer-activity-file.pdf',
            'path' => 'party-attachments/customers/customer-activity-file.pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048,
        ]);

        PartyContactLog::query()->create([
            'customer_id' => $customer->id,
            'contact_type' => 'whatsapp',
            'summary' => 'سجل تواصل خاص بخط نشاط العميل.',
            'contacted_at' => '2026-06-29',
            'follow_up_at' => '2026-07-02',
        ]);

        $response = $this->get(route('customers.activity-timeline.index', $customer));

        $response->assertOk();
        $response->assertSee('خط نشاط العميل');
        $response->assertSee('عميل نشاط شامل');
        $response->assertSee('ملاحظة خاصة بخط نشاط العميل.');
        $response->assertSee('customer-activity-file.pdf');
        $response->assertSee('سجل تواصل خاص بخط نشاط العميل.');
        $response->assertSee('data-testid="activity-timeline-card"', false);
    }

    public function test_supplier_activity_timeline_lists_notes_attachments_and_contact_logs(): void
    {
        $this->signIn();

        $supplier = $this->createSupplier(['name' => 'مورد نشاط شامل']);

        PartyNote::query()->create([
            'supplier_id' => $supplier->id,
            'note' => 'ملاحظة خاصة بخط نشاط المورد.',
        ]);

        PartyAttachment::query()->create([
            'supplier_id' => $supplier->id,
            'original_name' => 'supplier-activity-file.pdf',
            'path' => 'party-attachments/suppliers/supplier-activity-file.pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048,
        ]);

        PartyContactLog::query()->create([
            'supplier_id' => $supplier->id,
            'contact_type' => 'call',
            'summary' => 'سجل تواصل خاص بخط نشاط المورد.',
            'contacted_at' => '2026-06-29',
            'follow_up_at' => '2026-07-03',
        ]);

        $response = $this->get(route('suppliers.activity-timeline.index', $supplier));

        $response->assertOk();
        $response->assertSee('خط نشاط المورد');
        $response->assertSee('مورد نشاط شامل');
        $response->assertSee('ملاحظة خاصة بخط نشاط المورد.');
        $response->assertSee('supplier-activity-file.pdf');
        $response->assertSee('سجل تواصل خاص بخط نشاط المورد.');
        $response->assertSee('data-testid="activity-timeline-card"', false);
    }

    public function test_empty_activity_timeline_shows_empty_state(): void
    {
        $this->signIn();

        $customer = $this->createCustomer();

        $response = $this->get(route('customers.activity-timeline.index', $customer));

        $response->assertOk();
        $response->assertSee('لا توجد أنشطة مسجلة بعد.');
        $response->assertSee('data-testid="activity-timeline-empty"', false);
    }

    public function test_customer_and_supplier_show_pages_link_to_activity_timeline(): void
    {
        $this->signIn();

        $customer = $this->createCustomer();
        $supplier = $this->createSupplier();

        $customerResponse = $this->get(route('customers.show', $customer));
        $supplierResponse = $this->get(route('suppliers.show', $supplier));

        $customerResponse->assertOk();
        $customerResponse->assertSee(route('customers.activity-timeline.index', $customer), false);
        $customerResponse->assertSee('data-testid="customers-activity-timeline-link"', false);

        $supplierResponse->assertOk();
        $supplierResponse->assertSee(route('suppliers.activity-timeline.index', $supplier), false);
        $supplierResponse->assertSee('data-testid="suppliers-activity-timeline-link"', false);
    }
}
