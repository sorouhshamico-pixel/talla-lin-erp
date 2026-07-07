<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierPurchaseInvoiceAgingReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_supplier_purchase_invoice_aging_report_page_loads(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.index', [
            'supplier_id' => 123,
            'aging_bucket' => 'overdue_31_60',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-purchase-invoice-aging-report"', false);
        $response->assertSee('تقرير أعمار ذمم الموردين');
        $response->assertSee('فلتر المورد: 123');
        $response->assertSee('فلتر شريحة العمر: overdue_31_60');
        $response->assertSee('data-testid="supplier-aging-skeleton"', false);
    }

    public function test_reports_index_displays_supplier_purchase_invoice_aging_report_link(): void
    {
        if (! view()->exists('reports.index')) {
            $this->markTestSkipped('reports.index view does not exist.');
        }

        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-purchase-invoice-aging-report-link"', false);
        $response->assertSee('تقرير أعمار ذمم الموردين');
        $response->assertSee(route('reports.supplier-purchase-invoice-aging.index'), false);
    }
}
