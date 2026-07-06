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

class SalesInvoiceCollectionFollowUpReportExportTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 1060;

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

    public function test_collection_follow_up_report_displays_export_link_with_current_filters(): void
    {
        $user = User::query()->firstOrFail();

        $customer = $this->createCustomer((int) DB::table('companies')->value('id'), [
            'name' => 'عميل رابط تصدير المتابعة',
            'phone' => '0579801261',
            'email' => 'collection-follow-up-export-link@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-collection-follow-ups.index', [
            'customer_id' => $customer->id,
            'follow_up_from' => '2026-07-01',
            'follow_up_to' => '2026-07-06',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="collection-follow-up-report-export-link"', false);
        $response->assertSee('customer_id=' . $customer->id, false);
        $response->assertSee('follow_up_from=2026-07-01', false);
        $response->assertSee('follow_up_to=2026-07-06', false);
    }

    public function test_collection_follow_up_report_export_respects_filters_and_outputs_csv(): void
    {
        $this->assertTrue(Schema::hasTable('sales_invoice_collection_notes'));

        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->value('id');

        $selectedCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل تصدير متابعة التحصيل',
            'phone' => '0579801262',
            'email' => 'collection-follow-up-export-selected@example.com',
        ]);

        $otherCustomer = $this->createCustomer($companyId, [
            'name' => 'عميل مستبعد من تصدير المتابعة',
            'phone' => '0579801263',
            'email' => 'collection-follow-up-export-other@example.com',
        ]);

        $selectedInvoice = $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-FOLLOW-UP-EXPORT-IN',
            'payment_status' => 'partial',
            'grand_total' => 2500,
            'paid_amount' => 500,
            'remaining_amount' => 2000,
            'due_at' => '2026-07-01 09:00:00',
        ]);

        $outOfDateInvoice = $this->createSalesInvoice($companyId, $branchId, $selectedCustomer->id, [
            'invoice_number' => 'SI-FOLLOW-UP-EXPORT-DATE-OUT',
            'payment_status' => 'partial',
            'grand_total' => 1800,
            'paid_amount' => 300,
            'remaining_amount' => 1500,
            'due_at' => '2026-07-01 09:00:00',
        ]);

        $otherInvoice = $this->createSalesInvoice($companyId, $branchId, $otherCustomer->id, [
            'invoice_number' => 'SI-FOLLOW-UP-EXPORT-CUSTOMER-OUT',
            'payment_status' => 'partial',
            'grand_total' => 1600,
            'paid_amount' => 400,
            'remaining_amount' => 1200,
            'due_at' => '2026-07-01 09:00:00',
        ]);

        $selectedInvoice->collectionNotes()->create([
            'user_id' => $user->id,
            'note' => 'ملاحظة تصدير متابعة التحصيل المطابقة.',
            'follow_up_at' => '2026-07-05',
        ]);

        $outOfDateInvoice->collectionNotes()->create([
            'user_id' => $user->id,
            'note' => 'ملاحظة تصدير خارج التاريخ.',
            'follow_up_at' => '2026-06-20',
        ]);

        $otherInvoice->collectionNotes()->create([
            'user_id' => $user->id,
            'note' => 'ملاحظة تصدير لعميل آخر.',
            'follow_up_at' => '2026-07-05',
        ]);

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-collection-follow-ups.export', [
            'customer_id' => $selectedCustomer->id,
            'follow_up_from' => '2026-07-01',
            'follow_up_to' => '2026-07-06',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('تقرير متابعات تحصيل فواتير المبيعات', $content);
        $this->assertStringContainsString('فلتر العميل', $content);
        $this->assertStringContainsString('عميل تصدير متابعة التحصيل #' . $selectedCustomer->id, $content);
        $this->assertStringContainsString('من تاريخ متابعة', $content);
        $this->assertStringContainsString('2026-07-01', $content);
        $this->assertStringContainsString('إلى تاريخ متابعة', $content);
        $this->assertStringContainsString('2026-07-06', $content);

        $this->assertStringContainsString('SI-FOLLOW-UP-EXPORT-IN', $content);
        $this->assertStringContainsString('ملاحظة تصدير متابعة التحصيل المطابقة.', $content);
        $this->assertStringContainsString('2000.00', $content);
        $this->assertStringContainsString('إجمالي المتابعات', $content);

        $this->assertStringNotContainsString('SI-FOLLOW-UP-EXPORT-DATE-OUT', $content);
        $this->assertStringNotContainsString('ملاحظة تصدير خارج التاريخ.', $content);
        $this->assertStringNotContainsString('SI-FOLLOW-UP-EXPORT-CUSTOMER-OUT', $content);
        $this->assertStringNotContainsString('ملاحظة تصدير لعميل آخر.', $content);
    }

    public function test_collection_follow_up_report_export_keeps_all_labels_when_filters_are_empty(): void
    {
        $user = User::query()->firstOrFail();

        SalesInvoice::query()->delete();

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-collection-follow-ups.export'));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('"فلتر العميل",all', $content);
        $this->assertStringContainsString('"من تاريخ متابعة",all', $content);
        $this->assertStringContainsString('"إلى تاريخ متابعة",all', $content);
        $this->assertStringContainsString('إجمالي المتابعات', $content);
    }

    private function createCustomer(int $companyId, array $overrides = []): Customer
    {
        $this->customerSequence++;

        $columns = Schema::getColumnListing('customers');

        $data = [
            'company_id' => $companyId,
            'name' => 'عميل تصدير متابعة التحصيل ' . $this->customerSequence,
            'phone' => '0579800' . str_pad((string) $this->customerSequence, 4, '0', STR_PAD_LEFT),
            'email' => 'collection-follow-up-export-' . $this->customerSequence . '@example.com',
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
            'invoice_number' => 'SI-FOLLOW-UP-EXPORT-' . uniqid(),
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
