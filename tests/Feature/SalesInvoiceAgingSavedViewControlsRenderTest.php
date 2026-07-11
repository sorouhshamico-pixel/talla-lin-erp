<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoiceAgingSavedViewControlsRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_invoice_aging_report_renders_saved_view_controls_from_config_partial(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-aging.index'));

        $response->assertOk();

        $response->assertSee('data-testid="sales-invoice-aging-saved-views-selector"', false);
        $response->assertSee('data-testid="sales-invoice-aging-save-view-card"', false);
        $response->assertSee('data-testid="sales-invoice-aging-save-view-form"', false);

        $response->assertSee('data-testid="sales-invoice-aging-saved-view-name-input"', false);
        $response->assertSee('data-testid="sales-invoice-aging-saved-view-default-checkbox"', false);
        $response->assertSee('data-testid="sales-invoice-aging-save-view-button"', false);

        $response->assertSee('العروض المحفوظة');
        $response->assertSee('حفظ عرض التقرير');
        $response->assertSee('اسم العرض المحفوظ');
        $response->assertSee('تعيين كعرض افتراضي لهذا التقرير');
        $response->assertSee('حفظ العرض');

        $response->assertSee('name="customer_id"', false);
        $response->assertSee('name="payment_status"', false);
        $response->assertSee('name="aging_bucket"', false);
    }
}
