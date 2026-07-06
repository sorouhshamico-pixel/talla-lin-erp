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

class SalesInvoiceCollectionFollowUpReportTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 1000;

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

    public function test_collection_follow_up_report_displays_due_notes_only_for_open_invoices(): void
    {
        $this->assertTrue(Schema::hasTable('sales_invoice_collection_notes'));

        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $customer = $this->createCustomer($companyId, [
            'name' => 'عميل تقرير متابعة التحصيل',
            'phone' => '0579801201',
            'email' => 'collection-follow-up-report@example.com',
        ]);

        $dueInvoice = $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-FOLLOW-UP-DUE-IN',
            'payment_status' => 'partial',
            'grand_total' => 2500,
            'paid_amount' => 500,
            'remaining_amount' => 2000,
            'due_at' => '2026-07-01 09:00:00',
        ]);

        $upcomingInvoice = $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-FOLLOW-UP-UPCOMING-OUT',
            'payment_status' => 'partial',
            'grand_total' => 1600,
            'paid_amount' => 600,
            'remaining_amount' => 1000,
            'due_at' => '2026-07-20 09:00:00',
        ]);

        $paidInvoice = $this->createSalesInvoice($companyId, $branchId, $customer->id, [
            'invoice_number' => 'SI-FOLLOW-UP-PAID-OUT',
            'payment_status' => 'paid',
            'grand_total' => 900,
            'paid_amount' => 900,
            'remaining_amount' => 0,
            'due_at' => '2026-07-01 09:00:00',
        ]);

        $dueInvoice->collectionNotes()->create([
            'user_id' => $user->id,
            'note' => 'متابعة مستحقة مع العميل اليوم.',
            'follow_up_at' => '2026-07-06',
        ]);

        $upcomingInvoice->collectionNotes()->create([
            'user_id' => $user->id,
            'note' => 'متابعة قادمة بعد أسبوع.',
            'follow_up_at' => '2026-07-15',
        ]);

        $paidInvoice->collectionNotes()->create([
            'user_id' => $user->id,
            'note' => 'هذه الملاحظة لا تظهر لأن الفاتورة مسددة.',
            'follow_up_at' => '2026-07-06',
        ]);

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-collection-follow-ups.index'));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-collection-follow-up-report-page"', false);
        $response->assertSee('تقرير متابعات تحصيل فواتير المبيعات');
        $response->assertSee('data-testid="collection-follow-up-summary-card"', false);

        $response->assertSee('SI-FOLLOW-UP-DUE-IN');
        $response->assertSee('متابعة مستحقة مع العميل اليوم.');
        $response->assertSee('2,000.00 ريال');

        $response->assertDontSee('SI-FOLLOW-UP-UPCOMING-OUT');
        $response->assertDontSee('متابعة قادمة بعد أسبوع.');
        $response->assertDontSee('SI-FOLLOW-UP-PAID-OUT');
        $response->assertDontSee('هذه الملاحظة لا تظهر لأن الفاتورة مسددة.');

        $response->assertSee('data-testid="collection-follow-up-due-count"', false);
        $response->assertSee('data-testid="collection-follow-up-upcoming-count"', false);
        $response->assertSee('data-testid="collection-follow-up-invoices-count"', false);
        $response->assertSee(route('reports.sales-invoice-collections.index'), false);
    }

    public function test_reports_index_displays_collection_follow_up_report_link(): void
    {
        if (! view()->exists('reports.index')) {
            $this->markTestSkipped('reports.index view does not exist.');
        }

        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-collection-follow-up-report-link"', false);
        $response->assertSee('تقرير متابعات تحصيل فواتير المبيعات');
        $response->assertSee(route('reports.sales-invoice-collection-follow-ups.index'), false);
    }

    public function test_collection_follow_up_report_displays_empty_state(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-collection-follow-ups.index'));

        $response->assertOk();
        $response->assertSee('data-testid="collection-follow-up-notes-empty"', false);
        $response->assertSee('لا توجد متابعات تحصيل مستحقة.');
        $response->assertSee('0.00 ريال');
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل تقرير متابعة التحصيل ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'collection-follow-up-report-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-FOLLOW-UP-' . uniqid(),
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
