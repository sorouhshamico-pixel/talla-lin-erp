<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PurchaseInvoiceService;
use App\Services\SalesInvoiceService;
use Illuminate\Database\Seeder;

class InitialSetupSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->firstOrCreate(
            [
                'name_ar' => 'طلة لين',
            ],
            [
                'name_en' => 'Talla Lin',
                'country' => 'SA',
                'city' => 'الرياض',
                'currency' => 'SAR',
                'timezone' => 'Asia/Riyadh',
                'is_active' => true,
            ]
        );

        $mainBranch = Branch::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'code' => 'MAIN',
            ],
            [
                'name' => 'الفرع الرئيسي',
                'type' => 'main',
                'city' => 'الرياض',
                'is_main' => true,
                'is_active' => true,
            ]
        );

        $onlineBranch = Branch::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'code' => 'ONLINE',
            ],
            [
                'name' => 'المتجر الإلكتروني',
                'type' => 'online',
                'city' => 'الرياض',
                'is_main' => false,
                'is_active' => true,
            ]
        );

        $mainWarehouse = Warehouse::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'code' => 'MAIN-WH',
            ],
            [
                'branch_id' => $mainBranch->id,
                'name' => 'المستودع الرئيسي',
                'type' => 'main',
                'city' => 'الرياض',
                'is_main' => true,
                'is_active' => true,
            ]
        );

        $owner = User::query()->updateOrCreate(
            [
                'email' => 'admin@tallalin.local',
            ],
            [
                'name' => 'مدير النظام',
                'password' => 'password',
                'role' => 'owner',
                'current_branch_id' => $mainBranch->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $owner->branches()->sync([
            $mainBranch->id => [
                'company_id' => $company->id,
                'role' => 'owner',
                'is_primary' => true,
                'can_access' => true,
            ],
            $onlineBranch->id => [
                'company_id' => $company->id,
                'role' => 'owner',
                'is_primary' => false,
                'can_access' => true,
            ],
        ]);

        $category = Category::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'slug' => 'abayas',
            ],
            [
                'name' => 'عبايات',
                'description' => 'تصنيف مخصص لمنتجات العبايات.',
                'is_active' => true,
            ]
        );

        $product = Product::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'sku' => 'TL-ABAYA-001',
            ],
            [
                'category_id' => $category->id,
                'name' => 'عباية لين كلاسيك',
                'description' => 'منتج تجريبي أولي لبناء نظام المنتجات.',
                'type' => 'variable',
                'sale_price' => 250.00,
                'cost_price' => 120.00,
                'tax_rate' => 15.00,
                'track_inventory' => true,
                'is_active' => true,
            ]
        );

        $mediumVariant = ProductVariant::query()->firstOrCreate(
            [
                'sku' => 'TL-ABAYA-001-BLK-M',
            ],
            [
                'product_id' => $product->id,
                'color' => 'أسود',
                'size' => 'M',
                'sale_price' => 250.00,
                'cost_price' => 120.00,
                'is_active' => true,
            ]
        );

        $largeVariant = ProductVariant::query()->firstOrCreate(
            [
                'sku' => 'TL-ABAYA-001-BLK-L',
            ],
            [
                'product_id' => $product->id,
                'color' => 'أسود',
                'size' => 'L',
                'sale_price' => 250.00,
                'cost_price' => 120.00,
                'is_active' => true,
            ]
        );

        $this->createOpeningInventory(
            company: $company,
            branch: $mainBranch,
            warehouse: $mainWarehouse,
            product: $product,
            variant: $mediumVariant,
            quantity: 12,
            reserved: 2,
            reorderLevel: 3,
            unitCost: 120,
            referenceNumber: 'OPENING-MAIN-WH-M'
        );

        $this->createOpeningInventory(
            company: $company,
            branch: $mainBranch,
            warehouse: $mainWarehouse,
            product: $product,
            variant: $largeVariant,
            quantity: 8,
            reserved: 1,
            reorderLevel: 3,
            unitCost: 120,
            referenceNumber: 'OPENING-MAIN-WH-L'
        );

        $customer = Customer::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'phone' => '0500000000',
            ],
            [
                'name' => 'عميلة تجربة',
                'email' => 'customer@example.local',
                'city' => 'الرياض',
                'address' => 'عنوان تجريبي داخل الرياض',
                'is_active' => true,
            ]
        );

        if (! SalesInvoice::query()->where('invoice_number', 'INV-DEMO-001')->exists()) {
            app(SalesInvoiceService::class)->createDraftInvoice(
                customer: $customer,
                branch: $mainBranch,
                user: $owner,
                invoiceNumber: 'INV-DEMO-001',
                notes: 'فاتورة تجريبية أولية.',
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
        }

        $supplier = Supplier::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'phone' => '0559000000',
            ],
            [
                'name' => 'مورد تجربة',
                'email' => 'supplier@example.local',
                'city' => 'الرياض',
                'address' => 'عنوان مورد تجريبي داخل الرياض',
                'is_active' => true,
            ]
        );

        if (! PurchaseInvoice::query()->where('invoice_number', 'PINV-DEMO-001')->exists()) {
            app(PurchaseInvoiceService::class)->createDraftInvoice(
                supplier: $supplier,
                branch: $mainBranch,
                warehouse: $mainWarehouse,
                user: $owner,
                invoiceNumber: 'PINV-DEMO-001',
                notes: 'فاتورة شراء تجريبية أولية.',
                items: [
                    [
                        'product_variant_id' => $mediumVariant->id,
                        'quantity' => 2,
                        'unit_cost' => 120,
                        'discount_amount' => 0,
                        'tax_rate' => 15,
                    ],
                    [
                        'product_variant_id' => $largeVariant->id,
                        'quantity' => 1,
                        'unit_cost' => 120,
                        'discount_amount' => 0,
                        'tax_rate' => 15,
                    ],
                ]
            );
        }
    }

    private function createOpeningInventory(
        Company $company,
        Branch $branch,
        Warehouse $warehouse,
        Product $product,
        ProductVariant $variant,
        int $quantity,
        int $reserved,
        int $reorderLevel,
        float $unitCost,
        string $referenceNumber
    ): void {
        InventoryBalance::query()->updateOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'product_variant_id' => $variant->id,
            ],
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'product_id' => $product->id,
                'quantity_on_hand' => $quantity,
                'quantity_reserved' => $reserved,
                'reorder_level' => $reorderLevel,
            ]
        );

        InventoryMovement::query()->firstOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'product_variant_id' => $variant->id,
                'type' => 'opening_balance',
                'reference_number' => $referenceNumber,
            ],
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'product_id' => $product->id,
                'direction' => 'in',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'reference_type' => 'system',
                'notes' => 'رصيد افتتاحي تجريبي للمخزون.',
                'occurred_at' => now(),
            ]
        );
    }
}
