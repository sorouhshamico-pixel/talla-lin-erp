<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\RevenueCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_and_create_revenue_category(): void
    {
        $this->seed();

        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('revenue-categories.index'));

        $response->assertOk();
        $response->assertSee('تصنيفات الإيرادات');
        $response->assertSee('إضافة تصنيف إيراد');

        $storeResponse = $this->actingAs($user)->post(route('revenue-categories.store'), [
            'name' => 'إيرادات مشاريع',
            'slug' => 'project-revenues',
            'description' => 'إيرادات ناتجة عن عقود ومشاريع العملاء',
        ]);

        $storeResponse->assertRedirect(route('revenue-categories.index'));

        $this->assertDatabaseHas('revenue_categories', [
            'company_id' => $company->id,
            'name' => 'إيرادات مشاريع',
            'slug' => 'project-revenues',
            'description' => 'إيرادات ناتجة عن عقود ومشاريع العملاء',
            'is_active' => true,
        ]);

        $indexResponse = $this->actingAs($user)->get(route('revenue-categories.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee('إيرادات مشاريع');
        $indexResponse->assertSee('project-revenues');
        $indexResponse->assertSee('مفعل');
    }

    public function test_revenue_category_slug_must_be_unique_per_company(): void
    {
        $this->seed();

        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();

        RevenueCategory::query()->create([
            'company_id' => $company->id,
            'name' => 'إيرادات عقود',
            'slug' => 'contract-revenues',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->from(route('revenue-categories.index'))
            ->post(route('revenue-categories.store'), [
                'name' => 'إيرادات عقود مكررة',
                'slug' => 'contract-revenues',
                'description' => 'تصنيف مكرر',
            ]);

        $response->assertRedirect(route('revenue-categories.index'));
        $response->assertSessionHasErrors('slug');

        $this->assertSame(
            1,
            RevenueCategory::query()->where('slug', 'contract-revenues')->count()
        );
    }

    public function test_user_can_update_revenue_category_without_duplicate_slug(): void
    {
        $this->seed();

        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();

        $category = RevenueCategory::query()->create([
            'company_id' => $company->id,
            'name' => 'إيرادات قديمة',
            'slug' => 'old-revenues',
            'description' => 'وصف قديم',
            'is_active' => true,
        ]);

        $editResponse = $this->actingAs($user)->get(route('revenue-categories.edit', $category));

        $editResponse->assertOk();
        $editResponse->assertSee('تعديل تصنيف إيراد');
        $editResponse->assertSee('إيرادات قديمة');

        $updateResponse = $this->actingAs($user)->put(route('revenue-categories.update', $category), [
            'name' => 'إيرادات خدمات محدثة',
            'slug' => 'updated-service-revenues',
            'description' => 'وصف محدث للتصنيف',
        ]);

        $updateResponse->assertRedirect(route('revenue-categories.index'));

        $this->assertDatabaseHas('revenue_categories', [
            'id' => $category->id,
            'name' => 'إيرادات خدمات محدثة',
            'slug' => 'updated-service-revenues',
            'description' => 'وصف محدث للتصنيف',
        ]);
    }

    public function test_user_can_toggle_revenue_category_active_status(): void
    {
        $this->seed();

        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();

        $category = RevenueCategory::query()->create([
            'company_id' => $company->id,
            'name' => 'إيرادات قابلة للتعطيل',
            'slug' => 'toggle-revenues',
            'is_active' => true,
        ]);

        $disableResponse = $this->actingAs($user)->patch(route('revenue-categories.toggle', $category));

        $disableResponse->assertRedirect(route('revenue-categories.index'));

        $this->assertDatabaseHas('revenue_categories', [
            'id' => $category->id,
            'is_active' => false,
        ]);

        $enableResponse = $this->actingAs($user)->patch(route('revenue-categories.toggle', $category->fresh()));

        $enableResponse->assertRedirect(route('revenue-categories.index'));

        $this->assertDatabaseHas('revenue_categories', [
            'id' => $category->id,
            'is_active' => true,
        ]);
    }

    public function test_revenues_index_links_to_revenue_categories_page(): void
    {
        $this->seed();

        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('revenues.index'));

        $response->assertOk();
        $response->assertSee('تصنيفات الإيرادات');
        $response->assertSee('revenue-categories-link', false);
    }
}
