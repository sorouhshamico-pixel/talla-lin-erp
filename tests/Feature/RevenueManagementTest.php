<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_revenues_index_and_create_page(): void
    {
        $this->seed();

        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();

        RevenueCategory::query()->create([
            'company_id' => $company->id,
            'name' => 'إيرادات خدمات',
            'slug' => 'service-revenues',
            'is_active' => true,
        ]);

        $indexResponse = $this->actingAs($user)->get(route('revenues.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee('الإيرادات');
        $indexResponse->assertSee('إضافة إيراد');

        $createResponse = $this->actingAs($user)->get(route('revenues.create'));

        $createResponse->assertOk();
        $createResponse->assertSee('إضافة إيراد');
        $createResponse->assertSee('إيرادات خدمات');
    }

    public function test_user_can_create_revenue_and_see_it_in_index(): void
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
            'name' => 'إيرادات عقود',
            'slug' => 'contract-revenues',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('revenues.store'), [
            'branch_id' => $branch->id,
            'revenue_category_id' => $category->id,
            'revenue_date' => '2026-06-27',
            'description' => 'إيراد اختبار مرحلة 12A',
            'amount' => 7500,
            'tax_amount' => 0,
            'collection_method' => 'bank_transfer',
            'collection_status' => 'collected',
            'reference_number' => 'REV-TEST-12A',
            'notes' => 'تم إنشاء هذا الإيراد من اختبار 12A',
        ]);

        $response->assertRedirect(route('revenues.index'));

        $this->assertDatabaseHas('revenues', [
            'branch_id' => $branch->id,
            'revenue_category_id' => $category->id,
            'description' => 'إيراد اختبار مرحلة 12A',
            'amount' => 7500,
            'collection_method' => 'bank_transfer',
            'is_collected' => true,
            'reference_number' => 'REV-TEST-12A',
        ]);

        $revenue = Revenue::query()
            ->where('description', 'إيراد اختبار مرحلة 12A')
            ->firstOrFail();

        $this->assertNotNull($revenue->code);

        $indexResponse = $this->actingAs($user)->get(route('revenues.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee('إيراد اختبار مرحلة 12A');
        $indexResponse->assertSee('تحويل بنكي');
        $indexResponse->assertSee('محصل');
        $indexResponse->assertSee('7,500.00 ريال');
    }

    public function test_revenue_creation_is_rejected_when_category_belongs_to_another_company(): void
    {
        $this->seed();

        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();

        $branch = Branch::query()
            ->where('company_id', $company->id)
            ->orderBy('id')
            ->firstOrFail();

        $otherCompany = Company::query()->create([
            'name_ar' => 'شركة أخرى',
            'is_active' => true,
        ]);

        $otherCompanyCategory = RevenueCategory::query()->create([
            'company_id' => $otherCompany->id,
            'name' => 'تصنيف شركة أخرى',
            'slug' => 'other-company-category',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('revenues.store'), [
            'branch_id' => $branch->id,
            'revenue_category_id' => $otherCompanyCategory->id,
            'revenue_date' => '2026-06-27',
            'description' => 'إيراد بتصنيف شركة أخرى',
            'amount' => 1000,
            'tax_amount' => 0,
            'collection_method' => 'cash',
            'collection_status' => 'collected',
        ]);

        $response->assertSessionHasErrors('revenue_category_id');

        $this->assertDatabaseMissing('revenues', [
            'description' => 'إيراد بتصنيف شركة أخرى',
        ]);
    }
}
