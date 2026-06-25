<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ProductVariant;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PurchaseInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PurchaseInvoiceFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_invoice_service_can_create_invoice_with_items_and_totals(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN-WH')->firstOrFail();

        $supplier = Supplier::query()->create([
            'company_id' => $branch->company_id,
            'name' => 'مورد تجربة',
            'phone' => '0550000000',
            'city' => 'الرياض',
            'is_active' => true,
        ]);

        $mediumVariant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-M')->firstOrFail();
        $largeVariant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-L')->firstOrFail();

        $invoice = app(PurchaseInvoiceService::class)->createDraftInvoice(
            supplier: $supplier,
            branch: $branch,
            warehouse: $warehouse,
            user: $admin,
            invoiceNumber: 'PINV-TEST-001',
            notes: 'فاتورة شراء اختبارية.',
            items: [
                [
                    'product_variant_id' => $mediumVariant->id,
                    'quantity' => 5,
                    'unit_cost' => 120,
                    'discount_amount' => 0,
                    'tax_rate' => 15,
                ],
                [
                    'product_variant_id' => $largeVariant->id,
                    'quantity' => 3,
                    'unit_cost' => 120,
                    'discount_amount' => 10,
                    'tax_rate' => 15,
                ],
            ]
        );

        $this->assertEquals('PINV-TEST-001', $invoice->invoice_number);
        $this->assertEquals('draft', $invoice->status);
        $this->assertEquals('unpaid', $invoice->payment_status);

        $this->assertEquals(960.0, (float) $invoice->subtotal);
        $this->assertEquals(10.0, (float) $invoice->discount_total);
        $this->assertEquals(142.5, (float) $invoice->tax_total);
        $this->assertEquals(1092.5, (float) $invoice->grand_total);
        $this->assertEquals(1092.5, (float) $invoice->remaining_amount);

        $this->assertEquals(2, $invoice->items()->count());

        $this->assertDatabaseHas('purchase_invoices', [
            'invoice_number' => 'PINV-TEST-001',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'grand_total' => 1092.50,
        ]);

        $this->assertEquals(1, PurchaseInvoice::query()->count());
        $this->assertEquals(2, PurchaseInvoiceItem::query()->count());
    }

    public function test_purchase_invoice_service_rejects_empty_items(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN-WH')->firstOrFail();

        $supplier = Supplier::query()->create([
            'company_id' => $branch->company_id,
            'name' => 'مورد بدون منتجات',
            'phone' => '0550000001',
            'city' => 'الرياض',
            'is_active' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('لا يمكن إنشاء فاتورة شراء بدون منتجات.');

        app(PurchaseInvoiceService::class)->createDraftInvoice(
            supplier: $supplier,
            branch: $branch,
            warehouse: $warehouse,
            user: $admin,
            invoiceNumber: 'PINV-EMPTY-001',
            items: []
        );
    }

    public function test_purchase_invoice_service_rejects_zero_quantity(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();
        $warehouse = Warehouse::query()->where('code', 'MAIN-WH')->firstOrFail();
        $variant = ProductVariant::query()->where('sku', 'TL-ABAYA-001-BLK-M')->firstOrFail();

        $supplier = Supplier::query()->create([
            'company_id' => $branch->company_id,
            'name' => 'مورد كمية صفر',
            'phone' => '0550000002',
            'city' => 'الرياض',
            'is_active' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('كمية المنتج يجب أن تكون أكبر من صفر.');

        app(PurchaseInvoiceService::class)->createDraftInvoice(
            supplier: $supplier,
            branch: $branch,
            warehouse: $warehouse,
            user: $admin,
            invoiceNumber: 'PINV-ZERO-001',
            items: [
                [
                    'product_variant_id' => $variant->id,
                    'quantity' => 0,
                    'unit_cost' => 120,
                ],
            ]
        );
    }
}
