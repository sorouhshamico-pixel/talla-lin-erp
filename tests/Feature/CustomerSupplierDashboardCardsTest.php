<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PartyContactLog;
use App\Models\PartyTag;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PartyDashboardSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerSupplierDashboardCardsTest extends TestCase
{
    use RefreshDatabase;

    private ?int $currentCompanyId = null;
    private ?int $currentBranchId = null;

    private function signIn(string $role = 'owner'): User
    {
        $companyId = $this->createCompanyId();
        $branchId = $this->createBranchId($companyId);

        $this->currentCompanyId = $companyId;
        $this->currentBranchId = $branchId;

        $user = $this->createTestUser($companyId, $branchId, $role);

        $this->actingAs($user);

        return $user;
    }

    private function createTestUser(?int $companyId = null, ?int $branchId = null, string $role = 'owner'): User
    {
        $columns = Schema::getColumnListing('users');

        $data = [
            'name' => 'Owner Party Dashboard Test ' . uniqid(),
            'email' => 'owner-party-dashboard-' . uniqid() . '@example.com',
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
                $data[$field] = $role;
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
            'name' => 'شركة اختبار لوحة العملاء والموردين',
            'commercial_name' => 'شركة اختبار لوحة العملاء والموردين',
            'email' => 'company-party-dashboard-test@example.com',
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
            'name' => 'فرع اختبار لوحة العملاء والموردين',
            'code' => 'PARTY-DASHBOARD',
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
                str_contains($columnName, 'email') => $table . '-required-' . uniqid() . '@example.com',
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
            'name' => 'عميل لوحة العملاء والموردين ' . uniqid(),
            'phone' => '055' . random_int(1000000, 9999999),
            'email' => 'customer-party-dashboard-' . uniqid() . '@example.com',
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
            'name' => 'مورد لوحة العملاء والموردين ' . uniqid(),
            'phone' => '056' . random_int(1000000, 9999999),
            'email' => 'supplier-party-dashboard-' . uniqid() . '@example.com',
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

    public function test_party_dashboard_page_displays_summary_cards_and_quick_links(): void
    {
        $this->signIn('owner');

        $this->createCustomer([
            'name' => 'عميل نشط للوحة',
            'is_active' => true,
        ]);

        $this->createCustomer([
            'name' => 'عميل غير نشط للوحة',
            'is_active' => false,
        ]);

        $this->createSupplier([
            'name' => 'مورد نشط للوحة',
            'is_active' => true,
        ]);

        PartyTag::query()->create([
            'name' => 'تصنيف للوحة',
            'slug' => 'dashboard-tag',
            'applies_to' => 'both',
            'is_active' => true,
        ]);

        $response = $this->get(route('party-dashboard.index'));

        $response->assertOk();
        $response->assertSee('لوحة العملاء والموردين');
        $response->assertSee('data-testid="party-dashboard-stats-card"', false);
        $response->assertSee('data-testid="party-dashboard-customers-total"', false);
        $response->assertSee('data-testid="party-dashboard-customers-active"', false);
        $response->assertSee('data-testid="party-dashboard-suppliers-total"', false);
        $response->assertSee('data-testid="party-dashboard-tags-total"', false);
        $response->assertSee(route('customers.index'), false);
        $response->assertSee(route('suppliers.index'), false);
        $response->assertSee(route('party-follow-ups.index'), false);
        $response->assertSee(route('party-tags.index'), false);
        $response->assertSee(route('party-duplicates.index'), false);
        $response->assertSee(route('party-permissions.index'), false);
    }

    public function test_party_dashboard_summary_service_counts_follow_ups_and_duplicates(): void
    {
        $this->signIn('owner');

        $customerA = $this->createCustomer([
            'name' => 'عميل تكرار لوحة أول',
            'phone' => '0559200001',
            'email' => 'dashboard-duplicate@example.com',
        ]);

        $this->createCustomer([
            'name' => 'عميل تكرار لوحة ثاني',
            'phone' => '+966559200001',
            'email' => 'DASHBOARD-DUPLICATE@example.com',
        ]);

        PartyContactLog::query()->create([
            'customer_id' => $customerA->id,
            'contact_type' => 'call',
            'summary' => 'متابعة مستحقة للوحة',
            'contacted_at' => now()->subDays(2)->toDateString(),
            'follow_up_at' => now()->subDay()->toDateString(),
        ]);

        PartyContactLog::query()->create([
            'customer_id' => $customerA->id,
            'contact_type' => 'whatsapp',
            'summary' => 'متابعة قادمة للوحة',
            'contacted_at' => now()->toDateString(),
            'follow_up_at' => now()->addDays(3)->toDateString(),
        ]);

        PartyContactLog::query()->create([
            'customer_id' => $customerA->id,
            'contact_type' => 'email',
            'summary' => 'متابعة مكتملة للوحة',
            'contacted_at' => now()->subDays(4)->toDateString(),
            'follow_up_at' => now()->subDays(3)->toDateString(),
            'follow_up_completed_at' => now(),
            'follow_up_result' => 'تمت',
        ]);

        $summary = app(PartyDashboardSummaryService::class)->summary();

        $this->assertGreaterThanOrEqual(2, $summary['customers_total']);
        $this->assertGreaterThanOrEqual(1, $summary['follow_ups_due']);
        $this->assertGreaterThanOrEqual(1, $summary['follow_ups_upcoming']);
        $this->assertGreaterThanOrEqual(1, $summary['follow_ups_completed']);
        $this->assertGreaterThanOrEqual(2, $summary['duplicate_groups_total']);
    }

    public function test_viewer_can_access_party_dashboard_but_guest_cannot(): void
    {
        $this->signIn('viewer');

        $this->get(route('party-dashboard.index'))->assertOk();

        auth()->logout();

        $this->get(route('party-dashboard.index'))->assertRedirect();
    }

    public function test_customer_and_supplier_index_pages_link_to_party_dashboard(): void
    {
        $this->signIn('owner');

        $customersResponse = $this->get(route('customers.index'));
        $suppliersResponse = $this->get(route('suppliers.index'));

        $customersResponse->assertOk();
        $customersResponse->assertSee(route('party-dashboard.index'), false);
        $customersResponse->assertSee('data-testid="customers-dashboard-link"', false);

        $suppliersResponse->assertOk();
        $suppliersResponse->assertSee(route('party-dashboard.index'), false);
        $suppliersResponse->assertSee('data-testid="suppliers-dashboard-link"', false);
    }
}
