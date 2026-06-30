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

class CustomerSupplierFollowUpCompletionTest extends TestCase
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
            'name' => 'Owner Follow Up Completion Test',
            'email' => 'owner-follow-up-completion-test@example.com',
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
            'name' => 'شركة اختبار إكمال المتابعات',
            'commercial_name' => 'شركة اختبار إكمال المتابعات',
            'email' => 'company-follow-up-completion-test@example.com',
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
            'name' => 'فرع اختبار إكمال المتابعات',
            'code' => 'FOLLOW-UP-COMPLETE',
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
            'name' => 'عميل إكمال المتابعة',
            'phone' => '0559600001',
            'email' => 'customer-follow-up-completion@example.com',
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
            'name' => 'مورد إكمال المتابعة',
            'phone' => '0569600001',
            'email' => 'supplier-follow-up-completion@example.com',
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

    public function test_due_follow_up_can_be_completed_with_result(): void
    {
        $this->signIn();

        $customer = $this->createCustomer();

        $contactLog = PartyContactLog::query()->create([
            'customer_id' => $customer->id,
            'contact_type' => 'call',
            'summary' => 'متابعة سيتم إنهاؤها.',
            'contacted_at' => now()->subDays(2)->toDateString(),
            'follow_up_at' => now()->subDay()->toDateString(),
        ]);

        $response = $this->post(route('party-follow-ups.complete', $contactLog), [
            'follow_up_result' => 'تم التواصل وتم إغلاق المتابعة.',
        ]);

        $response->assertRedirect(route('party-follow-ups.index', ['status' => 'due']));

        $this->assertDatabaseHas('party_contact_logs', [
            'id' => $contactLog->id,
            'follow_up_result' => 'تم التواصل وتم إغلاق المتابعة.',
        ]);

        $this->assertNotNull($contactLog->fresh()->follow_up_completed_at);
    }

    public function test_completed_follow_up_is_hidden_from_due_filter_and_visible_in_completed_filter(): void
    {
        $this->signIn();

        $customer = $this->createCustomer(['name' => 'عميل متابعة مكتملة']);

        $contactLog = PartyContactLog::query()->create([
            'customer_id' => $customer->id,
            'contact_type' => 'whatsapp',
            'summary' => 'متابعة مكتملة لا تظهر في المستحقة.',
            'contacted_at' => now()->subDays(2)->toDateString(),
            'follow_up_at' => now()->subDay()->toDateString(),
            'follow_up_completed_at' => now(),
            'follow_up_result' => 'نتيجة متابعة مكتملة.',
        ]);

        $dueResponse = $this->get(route('party-follow-ups.index', ['status' => 'due']));

        $dueResponse->assertOk();
        $dueResponse->assertDontSee('متابعة مكتملة لا تظهر في المستحقة.');

        $completedResponse = $this->get(route('party-follow-ups.index', ['status' => 'completed']));

        $completedResponse->assertOk();
        $completedResponse->assertSee('متابعة مكتملة لا تظهر في المستحقة.');
        $completedResponse->assertSee('نتيجة متابعة مكتملة.');
        $completedResponse->assertSee('data-testid="follow-up-status-completed-' . $contactLog->id . '"', false);
    }

    public function test_follow_up_can_be_rescheduled_to_upcoming(): void
    {
        $this->signIn();

        $supplier = $this->createSupplier();

        $contactLog = PartyContactLog::query()->create([
            'supplier_id' => $supplier->id,
            'contact_type' => 'email',
            'summary' => 'متابعة سيتم تأجيلها.',
            'contacted_at' => now()->subDays(2)->toDateString(),
            'follow_up_at' => now()->subDay()->toDateString(),
        ]);

        $newDate = now()->addDays(7)->toDateString();

        $response = $this->post(route('party-follow-ups.reschedule', $contactLog), [
            'follow_up_at' => $newDate,
            'follow_up_result' => 'تم التأجيل لحين وصول الرد.',
        ]);

        $response->assertRedirect(route('party-follow-ups.index', ['status' => 'upcoming']));

        $fresh = $contactLog->fresh();

        $this->assertSame($newDate, $fresh->follow_up_at->format('Y-m-d'));
        $this->assertSame('تم التأجيل لحين وصول الرد.', $fresh->follow_up_result);
        $this->assertNull($fresh->follow_up_completed_at);
    }

    public function test_completed_follow_up_can_be_rescheduled_and_reopened(): void
    {
        $this->signIn();

        $supplier = $this->createSupplier();

        $contactLog = PartyContactLog::query()->create([
            'supplier_id' => $supplier->id,
            'contact_type' => 'meeting',
            'summary' => 'متابعة مكتملة سيتم فتحها مرة أخرى.',
            'contacted_at' => now()->subDays(3)->toDateString(),
            'follow_up_at' => now()->subDay()->toDateString(),
            'follow_up_completed_at' => now(),
            'follow_up_result' => 'كانت مكتملة.',
        ]);

        $newDate = now()->addDays(3)->toDateString();

        $response = $this->post(route('party-follow-ups.reschedule', $contactLog), [
            'follow_up_at' => $newDate,
            'follow_up_result' => 'تمت إعادة فتح المتابعة.',
        ]);

        $response->assertRedirect(route('party-follow-ups.index', ['status' => 'upcoming']));

        $fresh = $contactLog->fresh();

        $this->assertNull($fresh->follow_up_completed_at);
        $this->assertSame($newDate, $fresh->follow_up_at->format('Y-m-d'));
        $this->assertSame('تمت إعادة فتح المتابعة.', $fresh->follow_up_result);
    }

    public function test_follow_up_center_shows_complete_and_reschedule_forms(): void
    {
        $this->signIn();

        $customer = $this->createCustomer();

        $contactLog = PartyContactLog::query()->create([
            'customer_id' => $customer->id,
            'contact_type' => 'call',
            'summary' => 'متابعة لاختبار النماذج.',
            'contacted_at' => now()->subDays(2)->toDateString(),
            'follow_up_at' => now()->toDateString(),
        ]);

        $response = $this->get(route('party-follow-ups.index'));

        $response->assertOk();
        $response->assertSee(route('party-follow-ups.complete', $contactLog), false);
        $response->assertSee(route('party-follow-ups.reschedule', $contactLog), false);
        $response->assertSee('data-testid="follow-up-complete-form-' . $contactLog->id . '"', false);
        $response->assertSee('data-testid="follow-up-reschedule-form-' . $contactLog->id . '"', false);
    }
}
