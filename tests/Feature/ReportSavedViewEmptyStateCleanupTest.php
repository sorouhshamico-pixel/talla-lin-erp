<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewEmptyStateCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_sales_invoice_saved_views_empty_state_and_help_text_are_consistent(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-aging.index'));

        $response->assertOk();
        $response->assertSee('data-testid="report-saved-view-help-text"', false);
        $response->assertSee('استخدم العروض المحفوظة للرجوع سريعًا إلى نفس الفلاتر');
        $response->assertSee('data-testid="sales-invoice-aging-saved-views-empty"', false);
        $response->assertSee('لا توجد عروض محفوظة لهذا التقرير حتى الآن. اضبط الفلاتر ثم استخدم نموذج حفظ العرض لإنشاء عرض سريع الاستخدام لاحقًا.');
    }

    public function test_customer_aging_saved_views_empty_state_and_help_text_are_consistent(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.index'));

        $response->assertOk();
        $response->assertSee('data-testid="report-saved-view-help-text"', false);
        $response->assertSee('data-testid="customer-aging-saved-views-empty"', false);
        $response->assertSee('لا توجد عروض محفوظة لهذا التقرير حتى الآن. اضبط الفلاتر ثم استخدم نموذج حفظ العرض لإنشاء عرض سريع الاستخدام لاحقًا.');
    }

    public function test_supplier_drilldown_saved_views_empty_state_and_help_text_are_consistent(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.drilldown'));

        $response->assertOk();
        $response->assertSee('data-testid="report-saved-view-help-text"', false);
        $response->assertSee('data-testid="supplier-aging-drilldown-saved-views-empty"', false);
        $response->assertSee('لا توجد عروض محفوظة لهذه التفاصيل حتى الآن. اضبط الفلاتر ثم استخدم نموذج حفظ العرض لإنشاء عرض سريع الاستخدام لاحقًا.');
    }
}
