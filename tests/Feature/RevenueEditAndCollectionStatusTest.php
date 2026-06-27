<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueEditAndCollectionStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_open_edit_revenue_page_and_update_revenue(): void
    {
        $this->seed();

        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();

        $branch = Branch::query()
            ->where('company_id', $company->id)
            ->orderBy('id')
            ->firstOrFail();

        $oldCategory = RevenueCategory::query()->create([
            'company_id' => $company->id,
            'name' => 'إيرادات قبل التعديل',
            'slug' => 'before-update-revenues',
            'is_active' => true,
        ]);

        $newCategory = RevenueCategory::query()->create([
            'company_id' => $company->id,
            'name' => 'إيرادات بعد التعديل',
            'slug' => 'after-update-revenues',
            'is_active' => true,
        ]);

        $revenue = Revenue::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'revenue_category_id' => $oldCategory->id,
            'code' => 'REV-EDIT-001',
            'revenue_date' => '2026-06-27',
            'description' => 'إيراد قبل التعديل',
            'amount' => 3000,
            'tax_amount' => 0,
            'collection_method' => 'cash',
            'is_collected' => true,
            'reference_number' => 'OLD-REF',
            'notes' => 'ملاحظات قديمة',
        ]);

        $editResponse = $this->actingAs($user)->get(route('revenues.edit', $revenue));

        $editResponse->assertOk();
        $editResponse->assertSee('تعديل إيراد');
        $editResponse->assertSee('إيراد قبل التعديل');
        $editResponse->assertSee('إيرادات بعد التعديل');

        $updateResponse = $this->actingAs($user)->put(route('revenues.update', $revenue), [
            'branch_id' => $branch->id,
            'revenue_category_id' => $newCategory->id,
            'revenue_date' => '2026-06-28',
            'description' => 'إيراد بعد التعديل',
            'amount' => 4500,
            'tax_amount' => 200,
            'collection_method' => 'bank_transfer',
            'collection_status' => 'uncollected',
            'reference_number' => 'NEW-REF',
            'notes' => 'ملاحظات محدثة',
        ]);

        $updateResponse->assertRedirect(route('revenues.index'));

        $this->assertDatabaseHas('revenues', [
            'id' => $revenue->id,
            'revenue_category_id' => $newCategory->id,
            'description' => 'إيراد بعد التعديل',
            'amount' => 4500,
            'tax_amount' => 200,
            'collection_method' => 'bank_transfer',
            'is_collected' => false,
            'reference_number' => 'NEW-REF',
            'notes' => 'ملاحظات محدثة',
        ]);

        $indexResponse = $this->actingAs($user)->get(route('revenues.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee('إيراد بعد التعديل');
        $indexResponse->assertSee('تحويل بنكي');
        $indexResponse->assertSee('غير محصل');
        $indexResponse->assertSee('4,500.00 ريال');
    }

    public function test_user_can_toggle_revenue_collection_status_from_index(): void
    {
        $this->seed();

        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();

        $branch = Branch::query()
            ->where('company_id', $company->id)
            ->orderBy('id')
            ->firstOrFail();

        $category = RevenueCategory::query()->create([
            'company_id' => $company->id,
            'name' => 'إيرادات قابلة للتحصيل',
            'slug' => 'toggle-collection-revenues',
            'is_active' => true,
        ]);

        $revenue = Revenue::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'revenue_category_id' => $category->id,
            'code' => 'REV-TOGGLE-001',
            'revenue_date' => '2026-06-27',
            'description' => 'إيراد لاختبار التحصيل',
            'amount' => 2500,
            'tax_amount' => 0,
            'collection_method' => 'cash',
            'is_collected' => true,
        ]);

        $indexResponse = $this->actingAs($user)->get(route('revenues.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee('revenue-edit-link-' . $revenue->id, false);
        $indexResponse->assertSee('revenue-toggle-collection-button-' . $revenue->id, false);
        $indexResponse->assertSee('تعليم كغير محصل');

        $uncollectResponse = $this->actingAs($user)->patch(route('revenues.toggle-collection', $revenue));

        $uncollectResponse->assertRedirect(route('revenues.index'));

        $this->assertDatabaseHas('revenues', [
            'id' => $revenue->id,
            'is_collected' => false,
        ]);

        $collectResponse = $this->actingAs($user)->patch(route('revenues.toggle-collection', $revenue->fresh()));

        $collectResponse->assertRedirect(route('revenues.index'));

        $this->assertDatabaseHas('revenues', [
            'id' => $revenue->id,
            'is_collected' => true,
        ]);
    }
}
