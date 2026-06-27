<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueArchiveRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_archived_revenues_with_archive_status_filter(): void
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
            'name' => 'إيرادات مؤرشفة قابلة للعرض',
            'slug' => 'viewable-archived-revenues',
            'is_active' => true,
        ]);

        $activeRevenue = $this->createRevenue($company, $branch, $category, 'إيراد نشط لا يظهر في المؤرشفة');
        $archivedRevenue = $this->createRevenue($company, $branch, $category, 'إيراد مؤرشف ظاهر في الفلتر', now());

        $defaultResponse = $this->actingAs($user)->get(route('revenues.index'));

        $defaultResponse->assertOk();
        $defaultResponse->assertSee('إيراد نشط لا يظهر في المؤرشفة');
        $defaultResponse->assertDontSee('إيراد مؤرشف ظاهر في الفلتر');

        $archivedResponse = $this->actingAs($user)->get(route('revenues.index', [
            'archive_status' => 'archived',
        ]));

        $archivedResponse->assertOk();
        $archivedResponse->assertSee('revenue-archive-status-filter', false);
        $archivedResponse->assertSee('يتم الآن عرض الإيرادات المؤرشفة فقط');
        $archivedResponse->assertSee('إيراد مؤرشف ظاهر في الفلتر');
        $archivedResponse->assertSee('revenue-restore-button-' . $archivedRevenue->id, false);
        $archivedResponse->assertDontSee('إيراد نشط لا يظهر في المؤرشفة');
    }

    public function test_user_can_restore_archived_revenue_and_it_returns_to_active_list(): void
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
            'name' => 'إيرادات قابلة للاستعادة',
            'slug' => 'restorable-revenues',
            'is_active' => true,
        ]);

        $revenue = $this->createRevenue($company, $branch, $category, 'إيراد سيتم استعادته', now());

        $restoreResponse = $this->actingAs($user)->patch(route('revenues.restore', $revenue));

        $restoreResponse->assertRedirect(route('revenues.index', ['archive_status' => 'archived']));

        $this->assertDatabaseHas('revenues', [
            'id' => $revenue->id,
            'description' => 'إيراد سيتم استعادته',
            'archived_at' => null,
        ]);

        $activeResponse = $this->actingAs($user)->get(route('revenues.index'));

        $activeResponse->assertOk();
        $activeResponse->assertSee('إيراد سيتم استعادته');
        $activeResponse->assertSee('revenue-archive-button-' . $revenue->id, false);

        $archivedResponse = $this->actingAs($user)->get(route('revenues.index', [
            'archive_status' => 'archived',
        ]));

        $archivedResponse->assertOk();
        $archivedResponse->assertDontSee('إيراد سيتم استعادته');
    }

    private function createRevenue(
        Company $company,
        Branch $branch,
        RevenueCategory $category,
        string $description,
        mixed $archivedAt = null
    ): Revenue {
        return Revenue::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'revenue_category_id' => $category->id,
            'code' => 'REV-RESTORE-' . uniqid(),
            'revenue_date' => '2026-06-27',
            'description' => $description,
            'amount' => 4200,
            'tax_amount' => 0,
            'collection_method' => 'cash',
            'is_collected' => true,
            'archived_at' => $archivedAt,
        ]);
    }
}
