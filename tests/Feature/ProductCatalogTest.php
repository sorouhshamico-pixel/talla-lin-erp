<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_product_catalog_is_created_successfully(): void
    {
        $this->seed();

        $this->assertDatabaseHas('categories', [
            'name' => 'عبايات',
            'slug' => 'abayas',
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'TL-ABAYA-001',
            'name' => 'عباية لين كلاسيك',
            'type' => 'variable',
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('product_variants', [
            'sku' => 'TL-ABAYA-001-BLK-M',
            'color' => 'أسود',
            'size' => 'M',
            'is_active' => 1,
        ]);

        $category = Category::query()->where('slug', 'abayas')->first();
        $product = Product::query()->where('sku', 'TL-ABAYA-001')->with('variants')->first();

        $this->assertNotNull($category);
        $this->assertNotNull($product);
        $this->assertEquals($category->id, $product->category_id);
        $this->assertCount(2, $product->variants);
    }

    public function test_guest_cannot_view_categories_or_products_pages(): void
    {
        $this->get('/categories')->assertRedirect('/login');
        $this->get('/products')->assertRedirect('/login');
    }

    public function test_owner_can_view_categories_page(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();

        $response = $this->actingAs($admin)->get('/categories');

        $response->assertOk();
        $response->assertSee('التصنيفات');
        $response->assertSee('عبايات');
        $response->assertSee('abayas');
    }

    public function test_owner_can_view_products_page(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();

        $response = $this->actingAs($admin)->get('/products');

        $response->assertOk();
        $response->assertSee('المنتجات');
        $response->assertSee('عباية لين كلاسيك');
        $response->assertSee('TL-ABAYA-001');
        $response->assertSee('TL-ABAYA-001-BLK-M');
    }
}
