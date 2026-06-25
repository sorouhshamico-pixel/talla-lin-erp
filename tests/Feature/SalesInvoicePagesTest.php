<?php

namespace Tests\Feature;

use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoicePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_customers_or_sales_invoices_pages(): void
    {
        $this->get('/customers')->assertRedirect('/login');
        $this->get('/sales-invoices')->assertRedirect('/login');
    }

    public function test_owner_can_view_customers_page(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();

        $response = $this->actingAs($admin)->get('/customers');

        $response->assertOk();
        $response->assertSee('العملاء');
        $response->assertSee('عميلة تجربة');
        $response->assertSee('0500000000');
    }

    public function test_owner_can_view_sales_invoices_page(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();

        $response = $this->actingAs($admin)->get('/sales-invoices');

        $response->assertOk();
        $response->assertSee('فواتير البيع');
        $response->assertSee('INV-DEMO-001');
        $response->assertSee('عميلة تجربة');
        $response->assertSee('851.00');
    }

    public function test_owner_can_view_sales_invoice_details_page(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $invoice = SalesInvoice::query()->where('invoice_number', 'INV-DEMO-001')->firstOrFail();

        $response = $this->actingAs($admin)->get('/sales-invoices/' . $invoice->id);

        $response->assertOk();
        $response->assertSee('تفاصيل فاتورة البيع');
        $response->assertSee('INV-DEMO-001');
        $response->assertSee('عميلة تجربة');
        $response->assertSee('TL-ABAYA-001-BLK-M');
        $response->assertSee('851.00');
    }
}
