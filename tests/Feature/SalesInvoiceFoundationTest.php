<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\User;
use App\Services\SalesInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class SalesInvoiceFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_invoice_service_can_create_invoice_with_items_and_totals(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();

        $customer = Customer::query()->create([
            'company_id' => $branch->company_id,
            'name' => 'عميلة تجربة',
            'phone' => '0510000000',
            'city' => 'الرياض',
            'is_active' => true,
        ]);

        $mediumVariant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-M')->firstOrFail();
        $largeVariant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-L')->firstOrFail();

        $service = new SalesInvoiceService();

        $invoice = $service->createDraftInvoice(
            customer: $customer,
            branch: $branch,
            user: $admin,
            invoiceNumber: 'INV-TEST-001',
            notes: 'فاتورة اختبارية.',
            items: [
                [
                    'product_variant_id' => $mediumVariant->id,
                    'quantity' => 2,
                    'unit_price' => 250,
                    'discount_amount' => 0,
                    'tax_rate' => 15,
                ],
                [
                    'product_variant_id' => $largeVariant->id,
                    'quantity' => 1,
                    'unit_price' => 250,
                    'discount_amount' => 10,
                    'tax_rate' => 15,
                ],
            ]
        );

        $this->assertEquals('INV-TEST-001', $invoice->invoice_number);
        $this->assertEquals('draft', $invoice->status);
        $this->assertEquals('unpaid', $invoice->payment_status);

        $this->assertEquals(750.0, (float) $invoice->subtotal);
        $this->assertEquals(10.0, (float) $invoice->discount_total);
        $this->assertEquals(111.0, (float) $invoice->tax_total);
        $this->assertEquals(851.0, (float) $invoice->grand_total);
        $this->assertEquals(851.0, (float) $invoice->remaining_amount);

        $this->assertEquals(2, $invoice->items()->count());

        $this->assertDatabaseHas('sales_invoices', [
            'invoice_number' => 'INV-TEST-001',
            'customer_id' => $customer->id,
            'branch_id' => $branch->id,
            'user_id' => $admin->id,
            'grand_total' => 851.00,
        ]);

        $this->assertDatabaseHas('sales_invoices', [
            'invoice_number' => 'INV-TEST-001',
            'grand_total' => 851.00,
        ]);

        $this->assertEquals(2, $invoice->items()->count());
    }

    public function test_sales_invoice_service_rejects_empty_items(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();

        $customer = Customer::query()->create([
            'company_id' => $branch->company_id,
            'name' => 'عميلة بدون منتجات',
            'phone' => '0510000001',
            'city' => 'الرياض',
            'is_active' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('لا يمكن إنشاء فاتورة بدون منتجات.');

        app(SalesInvoiceService::class)->createDraftInvoice(
            customer: $customer,
            branch: $branch,
            user: $admin,
            invoiceNumber: 'INV-EMPTY-001',
            items: []
        );
    }

    public function test_sales_invoice_service_rejects_zero_quantity(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();
        $variant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-M')->firstOrFail();

        $customer = Customer::query()->create([
            'company_id' => $branch->company_id,
            'name' => 'عميلة كمية صفر',
            'phone' => '0510000002',
            'city' => 'الرياض',
            'is_active' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('كمية المنتج يجب أن تكون أكبر من صفر.');

        app(SalesInvoiceService::class)->createDraftInvoice(
            customer: $customer,
            branch: $branch,
            user: $admin,
            invoiceNumber: 'INV-ZERO-001',
            items: [
                [
                    'product_variant_id' => $variant->id,
                    'quantity' => 0,
                    'unit_price' => 250,
                ],
            ]
        );
    }
}
