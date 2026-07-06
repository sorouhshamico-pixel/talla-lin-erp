<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SalesInvoiceCollectionFollowUpReportFilterTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 1030;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-06 10:00:00');

        $this->seed(InitialSetupSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_collection_follow_up_report_displays_filter_controls(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-collection-follow-ups.index'));

        $response->assertOk();
        $response->assertSee('data-testid="collection-follow-up-report-filters-card"', false);
        $response->assertSee('data-testid="collection-follow-up-customer-filter"', false);
        $response->assertSee('data-testid="collection-follow-up-from-filter"', false);
        $response->assertSee('data-testid="collection-follow-up-to-filter"', false);
        $response->assertSee('data-testid="collection-follow-up-apply-filters-button"', false);
        $response->assertSee('data-testid="collection-follow-up-reset-filters-link"', false);
    }

    public function test_collection_follow_up_report_filters_by_customer_and_follow_up_date_range(): void
    {
        $this->assertTrue(Schema::hasTable('sales_invoice_collection_notes'));

        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $selectedCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل فلتر متابعة التحصيل',
            'phone' => '0579801231',
            'email' => 'collection-follow-up-filter-selected@example.com',
        ]);

        $otherCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل مستبعد من متابعة التحصيل',
            'phone' => '0579801232',
            'email' => 'collection-follow-up-filter-other@example.com',
        ]);

        $selectedInvoice = $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-FOLLOW-UP-FILTER-IN',
            'payment_status' => 'partial',
            'grand_total' => 3000,
            'paid_amount' => 1000,
            'remaining_amount' => 2000,
            'due_at' => '2026-07-01 09:00:00',
        ]);

        $selectedOutOfDateInvoice = $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-FOLLOW-UP-FILTER-DATE-OUT',
            'payment_status' => 'partial',
            'grand_total' => 1800,
            'paid_amount' => 300,
            'remaining_amount' => 1500,
            'due_at' => '2026-07-01 09:00:00',
        ]);

        $otherInvoice = $this->createSalesInvoice($companyId, $branchId, $otherCustomer->id, [
            'invoice_number' => 'SI-FOLLOW-UP-FILTER-CUSTOMER-OUT',
            'payment_status' => 'partial',
            'grand_total' => 1600,
            'paid_amount' => 400,
            'remaining_amount' => 1200,
            'due_at' => '2026-07-01 09:00:00',
        ]);

        $selectedInvoice->collectionNotes()->create([
            'user_id' => $user->id,
            'note' => 'ملاحظة متابعة مطابقة للفلتر.',
            'follow_up_at' => '2026-07-05',
        ]);

        $selectedOutOfDateInvoice->collectionNotes()->create([
            'user_id' => $user->id,
            'note' => 'ملاحظة خارج نطاق التاريخ.',
            'follow_up_at' => '2026-06-25',
        ]);

        $otherInvoice->collectionNotes()->create([
            'user_id' => $user->id,
            'note' => 'ملاحظة لعميل آخر.',
            'follow_up_at' => '2026-07-05',
        ]);

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-collection-follow-ups.index', [
            'customer_id' => $selectedCustomer->id,
            'follow_up_from' => '2026-07-01',
            'follow_up_to' => '2026-07-06',
        ]));

        $response->assertOk();
        $response->assertSee('SI-FOLLOW-UP-FILTER-IN');
        $response->assertSee('ملاحظة متابعة مطابقة للفلتر.');
        $response->assertSee('2,000.00 ريال');

        $response->assertDontSee('SI-FOLLOW-UP-FILTER-DATE-OUT');
        $response->assertDontSee('ملاحظة خارج نطاق التاريخ.');
        $response->assertDontSee('SI-FOLLOW-UP-FILTER-CUSTOMER-OUT');
        $response->assertDontSee('ملاحظة لعميل آخر.');

        $response->assertSee('value="' . $selectedCustomer->id . '" selected', false);
        $response->assertSee('value="2026-07-01"', false);
        $response->assertSee('value="2026-07-06"', false);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل فلتر متابعة التحصيل ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'collection-follow-up-filter-' . $this->customerSequence . '@example.com',
            'city' => 'الرياض',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $data = array_merge($data, $overrides);
        $data = array_intersect_key($data, array_flip($columns));

        return Customer::unguarded(fn () => Customer::query()->create($data));
    }

    private function createSalesInvoice(int $companyId, int $branchId, int $customerId, array $overrides = []): SalesInvoice
    {
        $columns = Schema::getColumnListing('sales_invoices');

        $data = [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'customer_id' => $customerId,
            'user_id' => (int) DB::table('users')->value('id'),
            'invoice_number' => 'SI-FOLLOW-UP-FILTER-' . uniqid(),
            'status' => 'issued',
            'payment_status' => 'unpaid',
            'currency' => 'SAR',
            'subtotal' => 500,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 500,
            'paid_amount' => 0,
            'remaining_amount' => 500,
            'issued_at' => '2026-07-01 09:00:00',
            'due_at' => '2026-07-20 09:00:00',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $data = array_merge($data, $overrides);
        $data = array_intersect_key($data, array_flip($columns));

        return SalesInvoice::unguarded(fn () => SalesInvoice::query()->create($data));
    }
}
