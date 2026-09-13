<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportIndexAggregatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_index_sales_purchases_and_inventory_totals_are_correct(): void
    {
        $this->seed(InitialSetupSeeder::class);

        $user = User::query()->firstOrFail();
        $companyId = (int) DB::table('companies')->value('id');
        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');
        $warehouseId = (int) DB::table('warehouses')->where('branch_id', $branchId)->value('id');
        $productId = (int) DB::table('products')->value('id');
        $customerId = (int) DB::table('customers')->value('id');
        $supplierId = (int) DB::table('suppliers')->value('id');

        // Start from a clean slate so the aggregates are fully predictable.
        DB::table('sales_invoices')->delete();
        DB::table('purchase_invoices')->delete();
        DB::table('inventory_balances')->delete();

        DB::table('sales_invoices')->insert([
            [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'customer_id' => $customerId,
                'user_id' => $user->id,
                'invoice_number' => 'REPORT-AGG-SI-001',
                'status' => 'issued',
                'payment_status' => 'partial',
                'currency' => 'SAR',
                'subtotal' => 1000,
                'discount_total' => 50,
                'tax_total' => 142.5,
                'grand_total' => 1092.5,
                'paid_amount' => 400,
                'remaining_amount' => 692.5,
                'issued_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'customer_id' => $customerId,
                'user_id' => $user->id,
                'invoice_number' => 'REPORT-AGG-SI-002',
                'status' => 'paid',
                'payment_status' => 'paid',
                'currency' => 'SAR',
                'subtotal' => 2000,
                'discount_total' => 0,
                'tax_total' => 300,
                'grand_total' => 2300,
                'paid_amount' => 2300,
                'remaining_amount' => 0,
                'issued_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('purchase_invoices')->insert([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'warehouse_id' => $warehouseId,
            'supplier_id' => $supplierId,
            'user_id' => $user->id,
            'invoice_number' => 'REPORT-AGG-PI-001',
            'status' => 'received',
            'payment_status' => 'partial',
            'currency' => 'SAR',
            'subtotal' => 500,
            'discount_total' => 20,
            'tax_total' => 72,
            'grand_total' => 552,
            'paid_amount' => 200,
            'remaining_amount' => 352,
            'invoice_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $variantOneId = (int) DB::table('product_variants')->insertGetId([
            'product_id' => $productId,
            'sku' => 'REPORT-AGG-VAR-1',
            'sale_price' => 100,
            'cost_price' => 60,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $variantTwoId = (int) DB::table('product_variants')->insertGetId([
            'product_id' => $productId,
            'sku' => 'REPORT-AGG-VAR-2',
            'sale_price' => 50,
            'cost_price' => 20,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_balances')->insert([
            [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'product_variant_id' => $variantOneId,
                'quantity_on_hand' => 10,
                'quantity_reserved' => 2,
                'reorder_level' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'product_variant_id' => $variantTwoId,
                'quantity_on_hand' => 5,
                'quantity_reserved' => 4,
                // available (5-4=1) <= reorder_level (2): this row is low stock.
                'reorder_level' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();

        $sales = $response->viewData('sales');
        $purchases = $response->viewData('purchases');
        $inventory = $response->viewData('inventory');

        $this->assertSame(2, $sales['count']);
        $this->assertSame(3000.0, $sales['subtotal']);
        $this->assertSame(50.0, $sales['discount_total']);
        $this->assertSame(442.5, $sales['tax_total']);
        $this->assertSame(3392.5, $sales['grand_total']);
        $this->assertSame(2700.0, $sales['paid_amount']);
        $this->assertSame(692.5, $sales['remaining_amount']);

        $this->assertSame(1, $purchases['count']);
        $this->assertSame(500.0, $purchases['subtotal']);
        $this->assertSame(20.0, $purchases['discount_total']);
        $this->assertSame(72.0, $purchases['tax_total']);
        $this->assertSame(552.0, $purchases['grand_total']);
        $this->assertSame(200.0, $purchases['paid_amount']);
        $this->assertSame(352.0, $purchases['remaining_amount']);

        $this->assertSame(1, $inventory['products_count']);
        $this->assertSame(2, $inventory['variants_count']);
        $this->assertSame(15.0, $inventory['quantity_on_hand']);
        $this->assertSame(6.0, $inventory['quantity_reserved']);
        $this->assertSame(9.0, $inventory['available_quantity']);
        $this->assertSame(700.0, $inventory['cost_value']); // 10*60 + 5*20
        $this->assertSame(1250.0, $inventory['sale_value']); // 10*100 + 5*50
        $this->assertSame(1, $inventory['low_stock_count']);
    }
}
